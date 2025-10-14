"""
Enhanced FastAPI Presentation Microservice
Independent microservice with OpenAI integration for outline and content generation
"""

from fastapi import FastAPI, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import FileResponse
from pydantic import BaseModel
from typing import List, Dict, Any, Optional
import uvicorn
import json
import os
import sys
import time
import logging
import subprocess
from pathlib import Path
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

# Import our services
from services.openai_service import OpenAIService
from services.outline_generator import OutlineGenerator
from services.content_generator import ContentGenerator
from services.error_handler import (
    create_error_response, create_success_response, 
    handle_internal_error, log_error_context
)
from services.progress_tracker import get_progress_status

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Create FastAPI app
app = FastAPI(
    title="Enhanced Presentation Microservice",
    description="Independent presentation microservice with OpenAI integration",
    version="2.0.0"
)

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Configure this properly for production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Global services
openai_service: Optional[OpenAIService] = None
outline_generator: Optional[OutlineGenerator] = None
content_generator: Optional[ContentGenerator] = None

# Pydantic models
class OutlineRequest(BaseModel):
    content: str
    language: Optional[str] = "English"
    tone: Optional[str] = "Professional"
    length: Optional[str] = "Medium"
    model: Optional[str] = None

class ContentRequest(BaseModel):
    outline: Dict[str, Any]
    language: Optional[str] = "English"
    tone: Optional[str] = "Professional"
    detail_level: Optional[str] = "detailed"

class PresentationData(BaseModel):
    title: str
    slides: List[Dict[str, Any]]

class ExportRequest(BaseModel):
    presentation_data: PresentationData
    user_id: int
    ai_result_id: int
    template: Optional[str] = "corporate_blue"
    color_scheme: Optional[str] = "blue"
    font_style: Optional[str] = "modern"
    generate_missing_content: Optional[bool] = True

class HealthResponse(BaseModel):
    status: str
    message: str
    timestamp: str
    services: Dict[str, Any]

@app.on_event("startup")
async def startup_event():
    """Initialize services on startup"""
    global openai_service, outline_generator, content_generator
    
    logger.info("Starting Enhanced Presentation Microservice...")
    
    try:
        # Initialize services
        openai_service = OpenAIService()
        outline_generator = OutlineGenerator(openai_service)
        content_generator = ContentGenerator(openai_service)
        
        logger.info("All services initialized successfully")
        
    except Exception as e:
        logger.error(f"Failed to initialize services: {e}")
        raise

@app.on_event("shutdown")
async def shutdown_event():
    """Cleanup on shutdown"""
    logger.info("Shutting down Enhanced Presentation Microservice...")

# Exception handlers
@app.exception_handler(Exception)
async def global_exception_handler(request: Request, exc: Exception):
    """Global exception handler"""
    logger.error(f"Unhandled exception: {exc}", exc_info=True)
    return create_error_response(
        success=False,
        error={
            "code": "INTERNAL_ERROR",
            "message": "Internal server error",
            "recoverable": True
        },
        timestamp=time.time()
    )

# Health check endpoint
@app.get("/health", response_model=HealthResponse)
async def health_check():
    """Health check endpoint"""
    try:
        services_status = {
            "openai": openai_service.is_available() if openai_service else False,
            "outline_generator": outline_generator is not None,
            "content_generator": content_generator is not None
        }
        
        return HealthResponse(
            status="healthy",
            message="Enhanced Presentation microservice is running",
            timestamp=time.strftime("%Y-%m-%d %H:%M:%S"),
            services=services_status
        )
        
    except Exception as e:
        logger.error(f"Health check failed: {e}")
        raise HTTPException(status_code=500, detail="Health check failed")

# Generate outline endpoint
@app.post("/generate-outline")
async def generate_outline(request: OutlineRequest):
    """Generate presentation outline from content"""
    try:
        logger.info(f"Generate outline request received: content_length={len(request.content)}, language={request.language}, tone={request.tone}, length={request.length}, model={request.model}")
        
        if not outline_generator:
            logger.error("Outline generator not available")
            raise HTTPException(status_code=503, detail="Outline generator not available")
        
        logger.info(f"Generating outline for content length: {len(request.content)}")
        
        # Generate outline
        result = outline_generator.generate_outline(
            content=request.content,
            language=request.language,
            tone=request.tone,
            length=request.length,
            model=request.model
        )
        
        logger.info(f"Outline generation result: success={result.get('success', False)}, error={result.get('error', 'None')}")
        
        if result['success']:
            return create_success_response(
                data=result['outline'],
                metadata=result.get('metadata', {}),
                timestamp=time.time()
            )
        else:
            logger.error(f"Outline generation failed: {result.get('error', 'Unknown error')}")
            return create_error_response(
                success=False,
                error=result['error'],
                timestamp=time.time()
            )
            
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Exception in generate_outline: {str(e)}", exc_info=True)
        log_error_context("generate_outline", e)
        return create_error_response(
            success=False,
            error=handle_internal_error(e),
            timestamp=time.time()
        )

# Generate content endpoint
@app.post("/generate-content")
async def generate_content(request: ContentRequest):
    """Generate detailed content for presentation slides"""
    try:
        if not content_generator:
            raise HTTPException(status_code=503, detail="Content generator not available")
        
        logger.info(f"Generating content for outline: {request.outline.get('title', 'Unknown')}")
        
        # Generate content
        result = content_generator.generate_content(
            outline=request.outline,
            language=request.language,
            tone=request.tone,
            detail_level=request.detail_level
        )
        
        if result['success']:
            return create_success_response(
                data=result['presentation'],
                metadata=result.get('metadata', {}),
                timestamp=time.time()
            )
        else:
            return create_error_response(
                success=False,
                error=result['error'],
                timestamp=time.time()
            )
            
    except HTTPException:
        raise
    except Exception as e:
        log_error_context("generate_content", e)
        return create_error_response(
            success=False,
            error=handle_internal_error(e),
            timestamp=time.time()
        )

# Progress status endpoint
@app.get("/progress/{operation_id}")
async def get_progress(operation_id: str):
    """Get progress status for an operation"""
    try:
        progress = get_progress_status(operation_id)
        
        if progress:
            return create_success_response(
                data=progress,
                timestamp=time.time()
            )
        else:
            return create_error_response(
                success=False,
                error={
                    "code": "NOT_FOUND",
                    "message": f"Progress tracking not found for operation {operation_id}",
                    "recoverable": False
                },
                timestamp=time.time()
            )
            
    except Exception as e:
        log_error_context("get_progress", e)
        return create_error_response(
            success=False,
            error=handle_internal_error(e),
            timestamp=time.time()
        )

@app.get("/templates")
async def get_templates():
    """Get available presentation templates"""
    templates = [
        {
            "id": "corporate_blue",
            "name": "Corporate Blue",
            "description": "Professional business theme",
            "colors": ["#003366", "#0066CC", "#FFFFFF"],
            "preview": "corporate_blue_preview.png"
        },
        {
            "id": "modern_white",
            "name": "Modern White",
            "description": "Clean minimalist theme",
            "colors": ["#FFFFFF", "#F8F9FA", "#6C757D"],
            "preview": "modern_white_preview.png"
        },
        {
            "id": "creative_colorful",
            "name": "Creative Colorful",
            "description": "Vibrant energetic theme",
            "colors": ["#FF6B6B", "#4ECDC4", "#45B7D1"],
            "preview": "creative_colorful_preview.png"
        },
        {
            "id": "minimalist_gray",
            "name": "Minimalist Gray",
            "description": "Simple elegant theme",
            "colors": ["#2C3E50", "#95A5A6", "#ECF0F1"],
            "preview": "minimalist_gray_preview.png"
        }
    ]
    return {"success": True, "templates": templates}

@app.post("/export")
async def export_presentation(request: ExportRequest):
    """Export presentation to PowerPoint with optional content generation"""
    try:
        logger.info(f"Export request received for AI Result ID: {request.ai_result_id}")
        
        # Check if we need to generate missing content
        slides = request.presentation_data.slides
        needs_content_generation = request.generate_missing_content
        
        # Check if slides have content
        has_content = any(slide.get('content') for slide in slides)
        
        if needs_content_generation and not has_content and content_generator:
            logger.info("Generating missing content for export")
            
            # Create outline from presentation data
            outline = {
                "title": request.presentation_data.title,
                "slides": slides
            }
            
            # Generate content
            content_result = content_generator.generate_content(
                outline=outline,
                language="English",
                tone="Professional",
                detail_level="detailed"
            )
            
            if content_result['success']:
                # Update slides with generated content
                slides = content_result['presentation']['slides']
                logger.info("Content generated successfully for export")
            else:
                logger.warning(f"Content generation failed: {content_result.get('error', {})}")
        
        # Prepare data for Python script
        python_data = {
            "outline": {
                "title": request.presentation_data.title,
                "slides": slides
            },
            "template": request.template,
            "color_scheme": request.color_scheme,
            "font_style": request.font_style,
            "user_id": request.user_id,
            "ai_result_id": request.ai_result_id
        }
        
        # Create temporary file for data
        temp_file = f"temp_presentation_{request.ai_result_id}_{int(time.time())}.json"
        with open(temp_file, 'w') as f:
            json.dump(python_data, f)
        
        # Get the path to the Python script
        script_path = Path(__file__).parent.parent / "python" / "generate_presentation.py"
        
        if not script_path.exists():
            raise HTTPException(status_code=500, detail="Python script not found")
        
        # Execute Python script using virtual environment Python
        try:
            # Use the virtual environment Python executable
            venv_python = Path(__file__).parent / "venv" / "Scripts" / "python.exe"
            if not venv_python.exists():
                # Fallback to system Python if venv not found
                venv_python = "py"
            
            result = subprocess.run(
                [str(venv_python), str(script_path), temp_file],
                capture_output=True,
                text=True,
                timeout=60
            )
            
            if result.returncode == 0:
                # Parse the JSON output from the last line
                output_lines = result.stdout.strip().split('\n')
                json_output = output_lines[-1] if output_lines else "{}"
                
                try:
                    result_data = json.loads(json_output)
                    if result_data.get('success'):
                        # Clean up temp file
                        if os.path.exists(temp_file):
                            os.remove(temp_file)
                        
                        return create_success_response(
                            data={
                                "file_path": result_data['file_path'],
                                "file_size": result_data.get('file_size', 0),
                                "download_url": result_data['download_url']
                            },
                            metadata={
                                "content_generated": needs_content_generation and not has_content,
                                "template": request.template
                            },
                            timestamp=time.time()
                        )
                    else:
                        raise HTTPException(status_code=500, detail=result_data.get('error', 'Export failed'))
                except json.JSONDecodeError:
                    raise HTTPException(status_code=500, detail="Invalid response from Python script")
            else:
                error_msg = result.stderr or "Unknown error"
                raise HTTPException(status_code=500, detail=f"Python script failed: {error_msg}")
                
        except subprocess.TimeoutExpired:
            raise HTTPException(status_code=500, detail="Export timeout")
        finally:
            # Clean up temp file
            if os.path.exists(temp_file):
                os.remove(temp_file)
            
    except Exception as e:
        logger.error(f"Export failed: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Export failed: {str(e)}")

@app.get("/download/{user_id}/{ai_result_id}")
async def download_presentation(user_id: int, ai_result_id: int):
    """Download generated presentation file"""
    try:
        # Look for the generated file
        possible_files = [
            f"presentation_{user_id}_{ai_result_id}.pptx",
            f"presentation_{ai_result_id}.pptx",
            f"generated_presentation_{ai_result_id}.pptx"
        ]
        
        file_found = None
        for filename in possible_files:
            file_path = Path("generated_presentations") / filename
            if file_path.exists():
                file_found = file_path
                break
        
        if not file_found:
            # Try to find any .pptx file with the ai_result_id
            generated_dir = Path("generated_presentations")
            if generated_dir.exists():
                for file_path in generated_dir.glob("*.pptx"):
                    if str(ai_result_id) in file_path.name:
                        file_found = file_path
                        break
        
        if not file_found:
            raise HTTPException(status_code=404, detail="Presentation file not found")
        
        return FileResponse(
            path=str(file_found),
            filename=file_found.name,
            media_type="application/vnd.openxmlformats-officedocument.presentationml.presentation"
        )
        
    except Exception as e:
        logger.error(f"Download failed: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Download failed: {str(e)}")

@app.post("/generate")
async def generate_presentation(request: ExportRequest):
    """Generate presentation (alias for export)"""
    return await export_presentation(request)

# Root endpoint
@app.get("/")
async def root():
    """Root endpoint with basic information"""
    return create_success_response(
        data={
            "service": "Enhanced Presentation Microservice",
            "version": "2.0.0",
            "status": "running",
            "endpoints": {
                "health": "/health",
                "generate_outline": "/generate-outline",
                "generate_content": "/generate-content",
                "export": "/export",
                "templates": "/templates",
                "progress": "/progress/{operation_id}",
                "download": "/download/{user_id}/{ai_result_id}"
            }
        },
        timestamp=time.time()
    )

if __name__ == "__main__":
    # Create output directory
    os.makedirs("generated_presentations", exist_ok=True)
    
    # Start the server
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=8001,
        reload=True,
        log_level="info"
    )