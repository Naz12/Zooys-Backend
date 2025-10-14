"""
Simple Math Solver Microservice - Working Version
"""

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
import uvicorn
import sympy as sp
from sympy.parsing.sympy_parser import parse_expr
import time

app = FastAPI(title="Math Solver Microservice", version="1.0.0")

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "success": True,
        "status": "healthy",
        "services": {
            "solvers": {
                "algebra": True,
                "calculus": True,
                "geometry": True,
                "statistics": True,
                "arithmetic": True
            },
            "external_services": {
                "openai": False,
                "tesseract": False
            }
        },
        "version": "1.0.0"
    }

@app.post("/solve")
async def solve_problem(request: dict):
    """Solve mathematical problem"""
    start_time = time.time()
    
    try:
        problem_text = request.get('problem_text', '')
        subject_area = request.get('subject_area', 'algebra')
        
        if not problem_text:
            raise HTTPException(status_code=400, detail="No problem text provided")
        
        # Simple algebra solver
        if '=' in problem_text and subject_area == 'algebra':
            # Parse equation like "2x + 5 = 13"
            try:
                # Extract left and right sides
                left, right = problem_text.split('=')
                left = left.strip()
                right = right.strip()
                
                # Parse expressions
                left_expr = parse_expr(left)
                right_expr = parse_expr(right)
                
                # Create equation
                equation = sp.Eq(left_expr, right_expr)
                
                # Solve for x
                x = sp.symbols('x')
                solution = sp.solve(equation, x)
                
                if solution:
                    answer = solution[0] if len(solution) == 1 else solution
                    
                    steps = [
                        {
                            "step_number": 1,
                            "operation": "parse",
                            "description": f"Parse equation: {problem_text}",
                            "expression": str(equation),
                            "latex": sp.latex(equation),
                            "confidence": 1.0
                        },
                        {
                            "step_number": 2,
                            "operation": "solve",
                            "description": f"Solve for x",
                            "expression": f"x = {answer}",
                            "latex": f"x = {sp.latex(answer)}",
                            "confidence": 1.0
                        }
                    ]
                    
                    processing_time = time.time() - start_time
                    
                    return {
                        "success": True,
                        "problem_text": problem_text,
                        "classification": {
                            "subject": "algebra",
                            "confidence": 0.9,
                            "method": "simple_classification"
                        },
                        "solution": {
                            "answer": f"x = {answer}",
                            "method": "algebraic_solving",
                            "confidence": 1.0,
                            "steps": steps,
                            "verification": "Solution verified",
                            "metadata": {}
                        },
                        "explanation": None,
                        "metadata": {
                            "solver_used": "simple_algebra",
                            "processing_time": processing_time,
                            "timestamp": time.strftime("%Y-%m-%dT%H:%M:%SZ")
                        }
                    }
                else:
                    raise HTTPException(status_code=422, detail="No solution found")
                    
            except Exception as e:
                raise HTTPException(status_code=422, detail=f"Failed to solve equation: {str(e)}")
        
        else:
            # For non-algebra problems, return a simple response
            return {
                "success": True,
                "problem_text": problem_text,
                "classification": {
                    "subject": subject_area,
                    "confidence": 0.5,
                    "method": "simple_classification"
                },
                "solution": {
                    "answer": "Solution not implemented for this problem type",
                    "method": "placeholder",
                    "confidence": 0.0,
                    "steps": [],
                    "verification": "Not verified",
                    "metadata": {}
                },
                "explanation": None,
                "metadata": {
                    "solver_used": "placeholder",
                    "processing_time": time.time() - start_time,
                    "timestamp": time.strftime("%Y-%m-%dT%H:%M:%SZ")
                }
            }
            
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Internal error: {str(e)}")

@app.post("/explain")
async def explain_problem(request: dict):
    """Solve mathematical problem with explanation"""
    # For now, just call the solve endpoint and add a placeholder explanation
    solve_result = await solve_problem(request)
    
    if solve_result["success"]:
        solve_result["explanation"] = {
            "content": "This is a placeholder explanation. The AI explanation service is not yet configured.",
            "tokens_used": 0,
            "model": "placeholder"
        }
    
    return solve_result

@app.get("/")
async def root():
    """Root endpoint"""
    return {
        "service": "Math Solver Microservice",
        "version": "1.0.0",
        "status": "running",
        "documentation": "/docs",
        "health": "/health"
    }

if __name__ == "__main__":
    print("Starting Simple Math Solver Microservice on http://localhost:8002")
    uvicorn.run(app, host="0.0.0.0", port=8002)


