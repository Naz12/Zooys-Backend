"""
Algebra Solver

Handles algebraic equations, expressions, and simplifications using SymPy.
Extracts detailed step-by-step solutions.
"""

import re
import sympy as sp
from sympy.parsing.sympy_parser import parse_expr
from sympy import symbols, solve, simplify, expand, factor, collect, cancel
from typing import Dict, List, Any, Optional, Union
import logging

from solvers.base_solver import BaseSolver, Solution, Step

logger = logging.getLogger(__name__)

class AlgebraSolver(BaseSolver):
    """
    Solver for algebraic problems including:
    - Linear equations
    - Quadratic equations
    - Systems of equations
    - Expression simplification
    - Factoring and expansion
    """
    
    def __init__(self, timeout: int = 5):
        super().__init__(timeout)
        self.solver_name = "AlgebraSolver"
    
    def can_solve(self, problem_text: str, subject_area: str = None) -> bool:
        """
        Check if this is an algebraic problem
        
        Args:
            problem_text: The mathematical problem as text
            subject_area: Optional subject area hint
            
        Returns:
            bool: True if this is an algebraic problem
        """
        problem_lower = problem_text.lower()
        
        # Check for algebraic keywords
        algebra_keywords = [
            'solve', 'equation', 'simplify', 'factor', 'expand',
            'linear', 'quadratic', 'polynomial', 'algebraic',
            'variable', 'unknown', 'x=', 'y=', 'z='
        ]
        
        # Check for algebraic symbols
        algebra_symbols = ['=', '+', '-', '*', '/', '^', '**', 'x', 'y', 'z']
        
        # Check for equation structure
        has_equals = '=' in problem_text
        has_variables = any(var in problem_text for var in ['x', 'y', 'z'])
        
        # Check for algebraic operations
        has_algebra_ops = any(op in problem_text for op in ['+', '-', '*', '/', '^', '**'])
        
        # Subject area hint
        if subject_area and subject_area.lower() in ['algebra', 'algebraic']:
            return True
        
        # Check if it looks like an algebraic problem
        keyword_score = sum(1 for kw in algebra_keywords if kw in problem_lower)
        symbol_score = sum(1 for sym in algebra_symbols if sym in problem_text)
        
        return (keyword_score >= 1 or (has_equals and has_variables) or 
                (has_variables and has_algebra_ops))
    
    def solve(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve algebraic problem with step-by-step extraction
        
        Args:
            problem_text: The algebraic problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        try:
            # Determine problem type and solve accordingly
            if '=' in problem_text:
                return self._solve_equation(problem_text, **kwargs)
            else:
                return self._simplify_expression(problem_text, **kwargs)
                
        except Exception as e:
            logger.error(f"Algebra solver failed: {e}")
            return self._create_error_solution(f"Algebra solving failed: {str(e)}")
    
    def _solve_equation(self, equation_text: str, **kwargs) -> Solution:
        """
        Solve algebraic equation with step extraction
        
        Args:
            equation_text: The equation to solve
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Parse the equation
            equation = self._parse_equation(equation_text)
            steps.append(self._create_step(
                "parse",
                "Parse the equation",
                str(equation),
                sp.latex(equation)
            ))
            
            # Step 2: Identify equation type
            eq_type = self._identify_equation_type(equation)
            steps.append(self._create_step(
                "identify",
                f"Identify equation type: {eq_type}",
                f"Type: {eq_type}",
                confidence=0.9
            ))
            
            # Step 3: Rearrange equation
            rearranged = self._rearrange_equation(equation)
            if rearranged != equation:
                steps.append(self._create_step(
                    "rearrange",
                    "Rearrange equation to standard form",
                    str(rearranged),
                    sp.latex(rearranged)
                ))
            
            # Step 4: Solve the equation
            solution = self._solve_equation_steps(rearranged, steps)
            
            # Step 5: Verify solution
            verification = self._verify_solution(equation, solution)
            steps.append(self._create_step(
                "verify",
                "Verify the solution",
                verification,
                confidence=0.95
            ))
            
            return Solution(
                answer=solution,
                steps=steps,
                method=f"{eq_type}_solving",
                confidence=0.95,
                verification=verification,
                metadata={
                    'equation_type': eq_type,
                    'variable_count': len(equation.free_symbols),
                    'degree': self._get_equation_degree(rearranged)
                }
            )
            
        except Exception as e:
            logger.error(f"Equation solving failed: {e}")
            return self._create_error_solution(f"Equation solving failed: {str(e)}")
    
    def _simplify_expression(self, expression_text: str, **kwargs) -> Solution:
        """
        Simplify algebraic expression with step extraction
        
        Args:
            expression_text: The expression to simplify
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Parse the expression
            expr = parse_expr(expression_text)
            steps.append(self._create_step(
                "parse",
                "Parse the expression",
                str(expr),
                sp.latex(expr)
            ))
            
            # Step 2: Expand if needed
            expanded = expand(expr)
            if expanded != expr:
                steps.append(self._create_step(
                    "expand",
                    "Expand the expression",
                    str(expanded),
                    sp.latex(expanded)
                ))
                expr = expanded
            
            # Step 3: Collect like terms
            collected = collect(expr)
            if collected != expr:
                steps.append(self._create_step(
                    "collect",
                    "Collect like terms",
                    str(collected),
                    sp.latex(collected)
                ))
                expr = collected
            
            # Step 4: Factor if beneficial
            factored = factor(expr)
            if factored != expr and len(str(factored)) < len(str(expr)):
                steps.append(self._create_step(
                    "factor",
                    "Factor the expression",
                    str(factored),
                    sp.latex(factored)
                ))
                expr = factored
            
            # Step 5: Final simplification
            simplified = simplify(expr)
            if simplified != expr:
                steps.append(self._create_step(
                    "simplify",
                    "Simplify the expression",
                    str(simplified),
                    sp.latex(simplified)
                ))
            
            return Solution(
                answer=str(simplified),
                steps=steps,
                method="expression_simplification",
                confidence=0.9,
                verification="Expression simplified successfully",
                metadata={
                    'original_length': len(expression_text),
                    'simplified_length': len(str(simplified)),
                    'reduction_ratio': len(str(simplified)) / len(expression_text)
                }
            )
            
        except Exception as e:
            logger.error(f"Expression simplification failed: {e}")
            return self._create_error_solution(f"Expression simplification failed: {str(e)}")
    
    def _parse_equation(self, equation_text: str) -> sp.Equality:
        """Parse equation text into SymPy Equality object"""
        try:
            # Clean the equation text - remove common prefixes
            cleaned_text = equation_text.strip()
            
            # Remove common prefixes like "Solve for x:", "Find x:", etc.
            prefixes_to_remove = [
                r'^solve\s+for\s+\w+:\s*',
                r'^find\s+\w+:\s*',
                r'^what\s+is\s+\w+:\s*',
                r'^calculate\s+\w+:\s*',
                r'^determine\s+\w+:\s*'
            ]
            
            import re
            for prefix in prefixes_to_remove:
                cleaned_text = re.sub(prefix, '', cleaned_text, flags=re.IGNORECASE)
            
            # Handle common equation formats
            if '=' in cleaned_text:
                left, right = cleaned_text.split('=', 1)
                left_expr = parse_expr(self._fix_implicit_multiplication(left.strip()))
                right_expr = parse_expr(self._fix_implicit_multiplication(right.strip()))
                return sp.Equality(left_expr, right_expr)
            else:
                # Treat as expression set to zero
                expr = parse_expr(self._fix_implicit_multiplication(cleaned_text))
                return sp.Equality(expr, 0)
        except Exception as e:
            logger.error(f"Equation parsing failed: {e}")
            raise
    
    def _fix_implicit_multiplication(self, expression: str) -> str:
        """Fix implicit multiplication in expressions (e.g., 2x -> 2*x)"""
        import re
        
        # Fix patterns like 2x, 3y, 5z, etc.
        expression = re.sub(r'(\d+)([a-zA-Z])', r'\1*\2', expression)
        
        # Fix patterns like x2, y3, etc. (though less common)
        expression = re.sub(r'([a-zA-Z])(\d+)', r'\1*\2', expression)
        
        # Fix patterns like (x+1)(x+2) -> (x+1)*(x+2)
        expression = re.sub(r'\)\s*\(', ')*(', expression)
        
        # Fix patterns like 2(x+1) -> 2*(x+1)
        expression = re.sub(r'(\d+)\s*\(', r'\1*(', expression)
        
        return expression
    
    def _identify_equation_type(self, equation: sp.Equality) -> str:
        """Identify the type of equation"""
        # Get the polynomial in standard form
        poly = equation.lhs - equation.rhs
        
        # Check degree
        degree = self._get_equation_degree(poly)
        
        if degree == 1:
            return "linear"
        elif degree == 2:
            return "quadratic"
        elif degree == 3:
            return "cubic"
        elif degree > 3:
            return f"polynomial_degree_{degree}"
        else:
            return "algebraic"
    
    def _get_equation_degree(self, poly) -> int:
        """Get the degree of a polynomial"""
        try:
            if hasattr(poly, 'as_poly'):
                poly_obj = poly.as_poly()
                if poly_obj is not None:
                    return poly_obj.degree()
            return 0
        except:
            return 0
    
    def _rearrange_equation(self, equation: sp.Equality) -> sp.Equality:
        """Rearrange equation to standard form (left side = 0)"""
        return sp.Equality(equation.lhs - equation.rhs, 0)
    
    def _solve_equation_steps(self, equation: sp.Equality, steps: List[Step]) -> Union[str, List[str]]:
        """Solve equation and add solving steps"""
        poly = equation.lhs
        
        # Get variables
        variables = list(poly.free_symbols)
        if not variables:
            return "No variables to solve for"
        
        # Solve the equation
        solutions = solve(poly, variables[0])
        
        # Add solving step
        if len(solutions) == 1:
            solution_str = f"{variables[0]} = {solutions[0]}"
            steps.append(self._create_step(
                "solve",
                f"Solve for {variables[0]}",
                solution_str,
                sp.latex(sp.Equality(variables[0], solutions[0]))
            ))
            return str(solutions[0])
        else:
            solution_str = f"{variables[0]} = {solutions}"
            steps.append(self._create_step(
                "solve",
                f"Solve for {variables[0]}",
                solution_str,
                sp.latex(solutions)
            ))
            return [str(sol) for sol in solutions]
    
    def _verify_solution(self, equation: sp.Equality, solution: Union[str, List[str]]) -> str:
        """Verify the solution by substitution"""
        try:
            if isinstance(solution, list):
                solutions = solution
            else:
                solutions = [solution]
            
            verification_results = []
            for sol in solutions:
                try:
                    # Substitute solution back into equation
                    if equation.lhs == equation.rhs:  # 0 = 0 form
                        poly = equation.lhs
                    else:
                        poly = equation.lhs - equation.rhs
                    
                    # Parse solution
                    sol_expr = parse_expr(str(sol))
                    result = poly.subs(list(equation.lhs.free_symbols)[0], sol_expr)
                    
                    # Check if result is approximately zero
                    if abs(float(result)) < 1e-10:
                        verification_results.append(f"✓ {sol} is correct")
                    else:
                        verification_results.append(f"✗ {sol} verification failed")
                        
                except Exception as e:
                    verification_results.append(f"? {sol} could not be verified")
            
            return "; ".join(verification_results)
            
        except Exception as e:
            return f"Verification failed: {str(e)}"
    
    def _get_capabilities(self) -> List[str]:
        """Get list of algebraic capabilities"""
        return [
            "linear_equations",
            "quadratic_equations", 
            "polynomial_equations",
            "expression_simplification",
            "factoring",
            "expansion",
            "collecting_like_terms",
            "equation_verification"
        ]

