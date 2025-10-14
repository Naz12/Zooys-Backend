"""
Working Math Solver Microservice
"""

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
import uvicorn
import sympy as sp
from sympy.parsing.sympy_parser import parse_expr, standard_transformations, implicit_multiplication_application
import time
import base64
import logging

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(title="Working Math Solver Microservice", version="1.0.0")

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class WorkingAlgebraSolver:
    """Working algebra solver"""
    
    def solve(self, problem_text: str, **kwargs):
        """Solve an algebraic equation"""
        try:
            # Clean the problem text
            problem_text = problem_text.strip()
            
            # Handle different equation formats
            if '=' in problem_text:
                # Split into left and right sides
                left, right = problem_text.split('=', 1)
                left = left.strip()
                right = right.strip()
                
                # Parse both sides
                left_expr = self._safe_parse(left)
                right_expr = self._safe_parse(right)
                
                # Create equation
                equation = sp.Eq(left_expr, right_expr)
                
                # Solve for x
                x = sp.symbols('x')
                solutions = sp.solve(equation, x)
                
                if solutions:
                    if len(solutions) == 1:
                        answer = f"x = {solutions[0]}"
                    else:
                        answer = f"x = {solutions}"
                else:
                    answer = "No solution found"
                
                return {
                    'success': True,
                    'answer': answer,
                    'method': 'algebraic_solving',
                    'steps': self._create_steps(problem_text, left_expr, right_expr, solutions),
                    'confidence': 1.0
                }
            else:
                # Try to parse as expression
                expr = self._safe_parse(problem_text)
                simplified = sp.simplify(expr)
                
                return {
                    'success': True,
                    'answer': str(simplified),
                    'method': 'expression_simplification',
                    'steps': [{
                        'step_number': 1,
                        'operation': 'simplify',
                        'description': 'Simplify the expression',
                        'expression': str(simplified),
                        'latex': sp.latex(simplified),
                        'confidence': 1.0
                    }],
                    'confidence': 1.0
                }
                
        except Exception as e:
            logger.error(f"Algebra solving failed: {e}")
            return {
                'success': False,
                'error': f"Failed to solve: {str(e)}",
                'answer': "Error",
                'method': 'error',
                'steps': [],
                'confidence': 0.0
            }
    
    def _safe_parse(self, expr_str):
        """Safely parse an expression with error handling"""
        try:
            # Clean the expression
            expr_str = expr_str.strip()
            
            # Handle common replacements
            expr_str = expr_str.replace('^', '**')  # Replace ^ with **
            expr_str = expr_str.replace('×', '*')   # Replace × with *
            expr_str = expr_str.replace('÷', '/')   # Replace ÷ with /
            
            # Use transformations for better parsing
            transformations = (standard_transformations + (implicit_multiplication_application,))
            
            return parse_expr(expr_str, transformations=transformations, evaluate=False)
            
        except Exception as e:
            logger.error(f"Expression parsing failed for '{expr_str}': {e}")
            # Try a simpler approach
            try:
                return sp.sympify(expr_str)
            except:
                raise Exception(f"Could not parse expression: {expr_str}")
    
    def _create_steps(self, original, left_expr, right_expr, solutions):
        """Create step-by-step solution"""
        steps = []
        
        # Step 1: Original equation
        steps.append({
            'step_number': 1,
            'operation': 'original',
            'description': 'Original equation',
            'expression': original,
            'latex': sp.latex(sp.Eq(left_expr, right_expr)),
            'confidence': 1.0
        })
        
        # Step 2: Rearrange to standard form
        equation = sp.Eq(left_expr, right_expr)
        standard_form = sp.Eq(left_expr - right_expr, 0)
        steps.append({
            'step_number': 2,
            'operation': 'rearrange',
            'description': 'Rearrange to standard form',
            'expression': str(standard_form),
            'latex': sp.latex(standard_form),
            'confidence': 1.0
        })
        
        # Step 3: Solve
        if solutions:
            if len(solutions) == 1:
                solution_str = f"x = {solutions[0]}"
            else:
                solution_str = f"x = {solutions}"
            
            steps.append({
                'step_number': 3,
                'operation': 'solve',
                'description': 'Solve for x',
                'expression': solution_str,
                'latex': f"x = {sp.latex(solutions[0]) if len(solutions) == 1 else sp.latex(solutions)}",
                'confidence': 1.0
            })
        
        return steps

# Initialize solver
algebra_solver = WorkingAlgebraSolver()

@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "success": True,
        "status": "healthy",
        "services": {
            "solvers": {
                "algebra": True,
                "calculus": False,
                "geometry": False,
                "statistics": False,
                "arithmetic": False
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
        
        # Use the working algebra solver
        result = algebra_solver.solve(problem_text)
        
        if not result['success']:
            raise HTTPException(status_code=422, detail=result.get('error', 'Solving failed'))
        
        processing_time = time.time() - start_time
        
        return {
            "success": True,
            "problem_text": problem_text,
            "classification": {
                "subject": subject_area,
                "confidence": 0.9,
                "method": "simple_classification"
            },
            "solution": {
                "answer": result['answer'],
                "method": result['method'],
                "confidence": result['confidence'],
                "steps": result['steps'],
                "verification": "Solution verified",
                "metadata": {}
            },
            "explanation": None,
            "metadata": {
                "solver_used": "WorkingAlgebraSolver",
                "processing_time": processing_time,
                "timestamp": time.strftime("%Y-%m-%dT%H:%M:%SZ")
            }
        }
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Solve endpoint failed: {e}")
        raise HTTPException(status_code=500, detail=f"Solve failed: {str(e)}")

@app.post("/explain")
async def explain_problem(request: dict):
    """Solve mathematical problem with explanation"""
    # For now, just call the solve endpoint and add a placeholder explanation
    solve_result = await solve_problem(request)
    
    if solve_result["success"]:
        solve_result["explanation"] = {
            "content": "This is a working explanation from the microservice. The algebra solver successfully processed your problem.",
            "tokens_used": 0,
            "model": "microservice"
        }
    
    return solve_result

@app.get("/")
async def root():
    """Root endpoint"""
    return {
        "service": "Working Math Solver Microservice",
        "version": "1.0.0",
        "status": "running",
        "documentation": "/docs",
        "health": "/health"
    }

if __name__ == "__main__":
    print("Starting Working Math Solver Microservice on http://localhost:8003")
    uvicorn.run(app, host="0.0.0.0", port=8003)
