"""
Math Solver Microservice - FastAPI Application

A standalone Python microservice that solves mathematical problems using
SymPy, NumPy, and SciPy, then provides educational explanations via OpenAI.
"""

import os
import time
import logging
from typing import Dict, Any, Optional
import asyncio

from fastapi import FastAPI, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
import uvicorn

# Load environment variables from .env file
from dotenv import load_dotenv
load_dotenv()

from services.problem_parser import ProblemParser
from services.openai_service import OpenAIService
from services.image_processor import ImageProcessor
from services.solution_formatter import SolutionFormatter

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Global services
problem_parser: Optional[ProblemParser] = None
openai_service: Optional[OpenAIService] = None
image_processor: Optional[ImageProcessor] = None
solution_formatter: Optional[SolutionFormatter] = None

# Create FastAPI app
app = FastAPI(
    title="Math Solver Microservice",
    description="A comprehensive mathematical problem solver with AI explanations",
    version="1.0.0"
)

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Configure properly for production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.on_event("startup")
async def startup_event():
    """Initialize services on startup"""
    global problem_parser, openai_service, image_processor, solution_formatter
    
    logger.info("Starting Math Solver Microservice...")
    
    try:
        # Initialize services
        openai_service = OpenAIService()
        problem_parser = ProblemParser()
        image_processor = ImageProcessor(openai_service)
        solution_formatter = SolutionFormatter()
        
        logger.info("All services initialized successfully")
        
    except Exception as e:
        logger.error(f"Failed to initialize services: {e}")
        raise

@app.on_event("shutdown")
async def shutdown_event():
    """Cleanup on shutdown"""
    logger.info("Shutting down Math Solver Microservice...")

# Exception handlers
@app.exception_handler(Exception)
async def global_exception_handler(request: Request, exc: Exception):
    """Global exception handler"""
    logger.error(f"Unhandled exception: {exc}", exc_info=True)
    return JSONResponse(
        status_code=500,
        content={
            "success": False,
            "error": {
                "message": "Internal server error",
                "type": "internal_error"
            },
            "timestamp": time.time()
        }
    )

# Health check endpoint
@app.get("/health")
async def health_check():
    """Health check endpoint"""
    try:
        # Check service statuses
        solvers_status = {
            "algebra": True,
            "calculus": True,
            "geometry": True,
            "statistics": True,
            "arithmetic": True
        }
        
        services_status = {
            "openai": openai_service.is_available() if openai_service else False,
            "tesseract": image_processor._check_tesseract_availability() if image_processor else False,
            "problem_parser": problem_parser is not None,
            "image_processor": image_processor is not None,
            "solution_formatter": solution_formatter is not None
        }
        
        return {
            "success": True,
            "status": "healthy",
            "services": {
                "solvers": solvers_status,
                "external_services": services_status
            },
            "version": "1.0.0"
        }
        
    except Exception as e:
        logger.error(f"Health check failed: {e}")
        raise HTTPException(status_code=500, detail="Health check failed")

# Solve endpoint (solution + steps only)
@app.post("/solve")
async def solve_problem(request: Dict[str, Any]):
    """Solve mathematical problem and return solution with steps"""
    start_time = time.time()
    
    try:
        if not problem_parser:
            raise HTTPException(status_code=503, detail="Problem parser not available")
        
        # Extract request data
        problem_text = request.get('problem_text')
        problem_image = request.get('problem_image')
        image_type = request.get('image_type', 'auto')
        subject_area = request.get('subject_area')
        timeout_ms = request.get('timeout_ms', 30000)
        
        # Process image if provided
        if problem_image:
            image_result = image_processor.process_image(
                problem_image,
                image_type
            )
            
            if not image_result['success']:
                raise HTTPException(
                    status_code=400,
                    detail=f"Image processing failed: {image_result.get('error', 'Unknown error')}"
                )
            
            problem_text = image_result['text']
        
        if not problem_text:
            raise HTTPException(status_code=400, detail="No problem text available")
        
        # Classify and solve the problem
        result = problem_parser.classify_and_solve(
            problem_text,
            subject_area,
            timeout=timeout_ms / 1000
        )
        
        if not result['success']:
            raise HTTPException(
                status_code=422,
                detail=f"Problem solving failed: {result.get('error', 'Unknown error')}"
            )
        
        # Format response
        processing_time = time.time() - start_time
        solution = result['solution']
        
        # The solution is already a dictionary from the SolverRegistry
        solution_dict = {
            "answer": solution.get('result', ''),
            "method": solution.get('method', 'unknown'),
            "confidence": solution.get('confidence', 1.0),
            "steps": solution.get('steps', []),
            "verification": solution.get('verification', ''),
            "metadata": solution.get('metadata', {})
        }
        
        return {
            "success": True,
            "problem_text": problem_text,
            "classification": result['classification'],
            "solution": solution_dict,
            "explanation": None,
            "metadata": {
                "solver_used": result['solver_used'],
                "processing_time": processing_time,
                "timestamp": time.strftime("%Y-%m-%dT%H:%M:%SZ"),
                "classification": result['classification']
            }
        }
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Solve endpoint failed: {e}")
        raise HTTPException(status_code=500, detail=f"Solve failed: {str(e)}")

# Explain endpoint (solution + steps + AI explanation)
@app.post("/explain")
async def explain_problem(request: Dict[str, Any]):
    """Solve mathematical problem and provide AI explanation"""
    start_time = time.time()
    
    try:
        if not problem_parser or not openai_service:
            raise HTTPException(status_code=503, detail="Required services not available")
        
        # Extract request data
        problem_text = request.get('problem_text')
        problem_image = request.get('problem_image')
        image_type = request.get('image_type', 'auto')
        subject_area = request.get('subject_area')
        difficulty_level = request.get('difficulty_level', 'intermediate')
        include_explanation = request.get('include_explanation', True)
        timeout_ms = request.get('timeout_ms', 30000)
        
        # Process image if provided
        if problem_image:
            image_result = image_processor.process_image(
                problem_image,
                image_type
            )
            
            if not image_result['success']:
                raise HTTPException(
                    status_code=400,
                    detail=f"Image processing failed: {image_result.get('error', 'Unknown error')}"
                )
            
            problem_text = image_result['text']
        
        if not problem_text:
            raise HTTPException(status_code=400, detail="No problem text available")
        
        # Classify and solve the problem
        result = problem_parser.classify_and_solve(
            problem_text,
            subject_area,
            timeout=timeout_ms / 1000
        )
        
        if not result['success']:
            raise HTTPException(
                status_code=422,
                detail=f"Problem solving failed: {result.get('error', 'Unknown error')}"
            )
        
        # Generate AI explanation if requested
        explanation = None
        if include_explanation:
            # The solution is already a dictionary from the SolverRegistry
            solution = result['solution']
            solution_dict = {
                "answer": solution.get('result', ''),
                "method": solution.get('method', 'unknown'),
                "confidence": solution.get('confidence', 1.0),
                "steps": solution.get('steps', []),
                "verification": solution.get('verification', ''),
                "metadata": solution.get('metadata', {})
            }
            
            explanation_result = openai_service.explain_solution(
                problem_text,
                solution_dict,
                subject_area,
                difficulty_level
            )
            
            if explanation_result['success']:
                explanation = {
                    "content": explanation_result['explanation'],
                    "tokens_used": explanation_result.get('tokens_used', 0),
                    "model": explanation_result.get('model', 'unknown')
                }
        
        # Format response
        processing_time = time.time() - start_time
        solution = result['solution']
        
        # The solution is already a dictionary from the SolverRegistry
        solution_dict = {
            "answer": solution.get('result', ''),
            "method": solution.get('method', 'unknown'),
            "confidence": solution.get('confidence', 1.0),
            "steps": solution.get('steps', []),
            "verification": solution.get('verification', ''),
            "metadata": solution.get('metadata', {})
        }
        
        return {
            "success": True,
            "problem_text": problem_text,
            "classification": result['classification'],
            "solution": solution_dict,
            "explanation": explanation,
            "metadata": {
                "solver_used": result['solver_used'],
                "processing_time": processing_time,
                "timestamp": time.strftime("%Y-%m-%dT%H:%M:%SZ"),
                "classification": result['classification']
            }
        }
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Explain endpoint failed: {e}")
        raise HTTPException(status_code=500, detail=f"Explain failed: {str(e)}")

# Root endpoint
@app.get("/")
async def root():
    """Root endpoint with basic information"""
    return {
        "service": "Math Solver Microservice",
        "version": "1.0.0",
        "status": "running",
        "documentation": "/docs",
        "health": "/health"
    }

if __name__ == "__main__":
    # Get configuration from environment
    host = os.getenv("HOST", "0.0.0.0")
    port = int(os.getenv("PORT", "8002"))
    reload = os.getenv("RELOAD", "false").lower() == "true"
    log_level = os.getenv("LOG_LEVEL", "info")
    
    logger.info(f"Starting Math Solver Microservice on {host}:{port}")
    
    # Run the application
    uvicorn.run(
        "main:app",
        host=host,
        port=port,
        reload=reload,
        log_level=log_level
    )