"""
Simple Algebra Solver - Working Version
"""

import sympy as sp
from sympy.parsing.sympy_parser import parse_expr, standard_transformations, implicit_multiplication_application
import re
import logging

logger = logging.getLogger(__name__)

class SimpleAlgebraSolver:
    """Simple algebra solver that actually works"""
    
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

# Test the solver
if __name__ == "__main__":
    solver = SimpleAlgebraSolver()
    
    # Test cases
    test_cases = [
        "2x + 5 = 13",
        "3x - 7 = 14",
        "x^2 + 2x + 1 = 0",
        "2x = 10"
    ]
    
    for test in test_cases:
        print(f"\nTesting: {test}")
        result = solver.solve(test)
        print(f"Result: {result}")


