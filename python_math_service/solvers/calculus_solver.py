"""
Calculus Solver

Handles calculus problems including derivatives, integrals, and limits using SymPy.
Extracts detailed step-by-step solutions showing the rules applied.
"""

import re
import sympy as sp
from sympy.parsing.sympy_parser import parse_expr
from sympy import symbols, diff, integrate, limit, dsolve, Derivative, Integral, Limit
from sympy import sin, cos, tan, exp, log, sqrt, pi, E
from typing import Dict, List, Any, Optional, Union
import logging

from solvers.base_solver import BaseSolver, Solution, Step

logger = logging.getLogger(__name__)

class CalculusSolver(BaseSolver):
    """
    Solver for calculus problems including:
    - Derivatives (power rule, chain rule, product rule, quotient rule)
    - Integrals (basic, substitution, by parts)
    - Limits
    - Differential equations
    """
    
    def __init__(self, timeout: int = 5):
        super().__init__(timeout)
        self.solver_name = "CalculusSolver"
    
    def can_solve(self, problem_text: str, subject_area: str = None) -> bool:
        """
        Check if this is a calculus problem
        
        Args:
            problem_text: The mathematical problem as text
            subject_area: Optional subject area hint
            
        Returns:
            bool: True if this is a calculus problem
        """
        problem_lower = problem_text.lower()
        
        # Check for calculus keywords
        calculus_keywords = [
            'derivative', 'differentiate', 'integral', 'integrate',
            'limit', 'differential', 'calculus', 'd/dx', '∫', 'lim'
        ]
        
        # Check for calculus symbols
        calculus_symbols = ['d/dx', '∫', 'lim', '∂']
        
        # Check for derivative notation
        has_derivative = any(notation in problem_text for notation in [
            'd/dx', 'dy/dx', 'd/d', 'derivative', 'differentiate'
        ])
        
        # Check for integral notation
        has_integral = any(notation in problem_text for notation in [
            '∫', 'integral', 'integrate', '∫dx'
        ])
        
        # Check for limit notation
        has_limit = any(notation in problem_text for notation in [
            'lim', 'limit', 'as x approaches', 'x→'
        ])
        
        # Subject area hint
        if subject_area and subject_area.lower() in ['calculus', 'derivative', 'integral']:
            return True
        
        # Check if it looks like a calculus problem
        keyword_score = sum(1 for kw in calculus_keywords if kw in problem_lower)
        symbol_score = sum(1 for sym in calculus_symbols if sym in problem_text)
        
        return (keyword_score >= 1 or symbol_score >= 1 or 
                has_derivative or has_integral or has_limit)
    
    def solve(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve calculus problem with step-by-step extraction
        
        Args:
            problem_text: The calculus problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        try:
            # Determine problem type and solve accordingly
            if self._is_derivative_problem(problem_text):
                return self._solve_derivative(problem_text, **kwargs)
            elif self._is_integral_problem(problem_text):
                return self._solve_integral(problem_text, **kwargs)
            elif self._is_limit_problem(problem_text):
                return self._solve_limit(problem_text, **kwargs)
            elif self._is_differential_equation(problem_text):
                return self._solve_differential_equation(problem_text, **kwargs)
            else:
                return self._create_error_solution("Unknown calculus problem type")
                
        except Exception as e:
            logger.error(f"Calculus solver failed: {e}")
            return self._create_error_solution(f"Calculus solving failed: {str(e)}")
    
    def _is_derivative_problem(self, problem_text: str) -> bool:
        """Check if this is a derivative problem"""
        derivative_indicators = ['d/dx', 'derivative', 'differentiate', 'dy/dx']
        return any(indicator in problem_text.lower() for indicator in derivative_indicators)
    
    def _is_integral_problem(self, problem_text: str) -> bool:
        """Check if this is an integral problem"""
        integral_indicators = ['∫', 'integral', 'integrate', '∫dx']
        return any(indicator in problem_text.lower() for indicator in integral_indicators)
    
    def _is_limit_problem(self, problem_text: str) -> bool:
        """Check if this is a limit problem"""
        limit_indicators = ['lim', 'limit', 'as x approaches', 'x→']
        return any(indicator in problem_text.lower() for indicator in limit_indicators)
    
    def _is_differential_equation(self, problem_text: str) -> bool:
        """Check if this is a differential equation"""
        # Look for equations with derivatives
        if '=' in problem_text and ('d/dx' in problem_text or 'dy/dx' in problem_text):
            return True
        return False
    
    def _solve_derivative(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve derivative problem with step extraction
        
        Args:
            problem_text: The derivative problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Parse the function
            function = self._extract_function_from_derivative(problem_text)
            steps.append(self._create_step(
                "parse",
                "Parse the function to differentiate",
                str(function),
                sp.latex(function)
            ))
            
            # Step 2: Identify differentiation rules
            rules = self._identify_differentiation_rules(function)
            steps.append(self._create_step(
                "identify_rules",
                f"Identify applicable rules: {', '.join(rules)}",
                f"Rules: {', '.join(rules)}",
                confidence=0.9
            ))
            
            # Step 3: Apply differentiation rules step by step
            derivative = self._differentiate_with_steps(function, steps)
            
            # Step 4: Simplify the result
            simplified = sp.simplify(derivative)
            if simplified != derivative:
                steps.append(self._create_step(
                    "simplify",
                    "Simplify the derivative",
                    str(simplified),
                    sp.latex(simplified)
                ))
            
            return Solution(
                answer=str(simplified),
                steps=steps,
                method="derivative_calculation",
                confidence=0.95,
                verification=f"d/dx({function}) = {simplified}",
                metadata={
                    'rules_applied': rules,
                    'function_type': self._get_function_type(function),
                    'complexity': self._assess_complexity(function)
                }
            )
            
        except Exception as e:
            logger.error(f"Derivative solving failed: {e}")
            return self._create_error_solution(f"Derivative solving failed: {str(e)}")
    
    def _solve_integral(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve integral problem with step extraction
        
        Args:
            problem_text: The integral problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Parse the integrand
            integrand = self._extract_integrand(problem_text)
            steps.append(self._create_step(
                "parse",
                "Parse the integrand",
                str(integrand),
                sp.latex(integrand)
            ))
            
            # Step 2: Identify integration method
            method = self._identify_integration_method(integrand)
            steps.append(self._create_step(
                "identify_method",
                f"Identify integration method: {method}",
                f"Method: {method}",
                confidence=0.9
            ))
            
            # Step 3: Apply integration
            integral_result = self._integrate_with_steps(integrand, method, steps)
            
            # Step 4: Add constant of integration
            if not integral_result.has(sp.Integral):
                steps.append(self._create_step(
                    "add_constant",
                    "Add constant of integration",
                    f"{integral_result} + C",
                    sp.latex(integral_result) + " + C"
                ))
                final_result = f"{integral_result} + C"
            else:
                final_result = str(integral_result)
            
            return Solution(
                answer=final_result,
                steps=steps,
                method=f"integration_{method}",
                confidence=0.9,
                verification=f"∫({integrand}) dx = {final_result}",
                metadata={
                    'integration_method': method,
                    'integrand_type': self._get_function_type(integrand),
                    'has_constant': True
                }
            )
            
        except Exception as e:
            logger.error(f"Integral solving failed: {e}")
            return self._create_error_solution(f"Integral solving failed: {str(e)}")
    
    def _solve_limit(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve limit problem with step extraction
        
        Args:
            problem_text: The limit problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Parse the limit
            expression, variable, point = self._parse_limit(problem_text)
            steps.append(self._create_step(
                "parse",
                f"Parse the limit: lim({expression}) as {variable} → {point}",
                f"lim({expression}) as {variable} → {point}",
                sp.latex(expression)
            ))
            
            # Step 2: Check for indeterminate forms
            indeterminate = self._check_indeterminate_form(expression, variable, point)
            if indeterminate:
                steps.append(self._create_step(
                    "identify_form",
                    f"Identify indeterminate form: {indeterminate}",
                    f"Form: {indeterminate}",
                    confidence=0.9
                ))
            
            # Step 3: Apply limit techniques
            limit_result = self._evaluate_limit_with_steps(expression, variable, point, steps)
            
            return Solution(
                answer=str(limit_result),
                steps=steps,
                method="limit_evaluation",
                confidence=0.9,
                verification=f"lim({expression}) as {variable} → {point} = {limit_result}",
                metadata={
                    'indeterminate_form': indeterminate,
                    'limit_point': str(point),
                    'variable': str(variable)
                }
            )
            
        except Exception as e:
            logger.error(f"Limit solving failed: {e}")
            return self._create_error_solution(f"Limit solving failed: {str(e)}")
    
    def _solve_differential_equation(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve differential equation with step extraction
        
        Args:
            problem_text: The differential equation as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Parse the differential equation
            equation = self._parse_differential_equation(problem_text)
            steps.append(self._create_step(
                "parse",
                "Parse the differential equation",
                str(equation),
                sp.latex(equation)
            ))
            
            # Step 2: Identify equation type
            eq_type = self._identify_differential_equation_type(equation)
            steps.append(self._create_step(
                "identify_type",
                f"Identify equation type: {eq_type}",
                f"Type: {eq_type}",
                confidence=0.9
            ))
            
            # Step 3: Solve the differential equation
            solution = dsolve(equation)
            steps.append(self._create_step(
                "solve",
                "Solve the differential equation",
                str(solution),
                sp.latex(solution)
            ))
            
            return Solution(
                answer=str(solution),
                steps=steps,
                method=f"differential_equation_{eq_type}",
                confidence=0.85,
                verification=f"Solution: {solution}",
                metadata={
                    'equation_type': eq_type,
                    'order': self._get_differential_equation_order(equation)
                }
            )
            
        except Exception as e:
            logger.error(f"Differential equation solving failed: {e}")
            return self._create_error_solution(f"Differential equation solving failed: {str(e)}")
    
    def _extract_function_from_derivative(self, problem_text: str) -> sp.Expr:
        """Extract the function from derivative notation"""
        # Handle common derivative notations
        if 'd/dx' in problem_text:
            # Extract what's after d/dx
            parts = problem_text.split('d/dx')
            if len(parts) > 1:
                function_text = parts[1].strip()
                # Remove parentheses if present
                function_text = function_text.strip('()')
                return parse_expr(function_text)
        
        # Handle "derivative of" notation
        if 'derivative of' in problem_text.lower():
            parts = problem_text.lower().split('derivative of')
            if len(parts) > 1:
                function_text = parts[1].strip()
                return parse_expr(function_text)
        
        # Handle "differentiate" notation
        if 'differentiate' in problem_text.lower():
            parts = problem_text.lower().split('differentiate')
            if len(parts) > 1:
                function_text = parts[1].strip()
                return parse_expr(function_text)
        
        # Default: try to parse the whole expression
        return parse_expr(problem_text)
    
    def _extract_integrand(self, problem_text: str) -> sp.Expr:
        """Extract the integrand from integral notation"""
        # Handle ∫ notation
        if '∫' in problem_text:
            # Extract what's between ∫ and dx
            parts = problem_text.split('∫')
            if len(parts) > 1:
                integrand_text = parts[1].replace('dx', '').strip()
                return parse_expr(integrand_text)
        
        # Handle "integral of" notation
        if 'integral of' in problem_text.lower():
            parts = problem_text.lower().split('integral of')
            if len(parts) > 1:
                integrand_text = parts[1].strip()
                return parse_expr(integrand_text)
        
        # Default: try to parse the whole expression
        return parse_expr(problem_text)
    
    def _parse_limit(self, problem_text: str) -> tuple:
        """Parse limit notation to extract expression, variable, and point"""
        # Handle "lim" notation
        if 'lim' in problem_text.lower():
            # Extract expression and limit point
            # This is a simplified parser - could be enhanced
            x = symbols('x')
            expression = parse_expr(problem_text.split('lim')[1].split('as')[0].strip())
            return expression, x, 0  # Default to x → 0
        
        # Default parsing
        x = symbols('x')
        expression = parse_expr(problem_text)
        return expression, x, 0
    
    def _parse_differential_equation(self, problem_text: str) -> sp.Equality:
        """Parse differential equation"""
        # This is a simplified parser - could be enhanced
        return parse_expr(problem_text)
    
    def _identify_differentiation_rules(self, function: sp.Expr) -> List[str]:
        """Identify which differentiation rules apply"""
        rules = []
        
        if function.is_Pow:
            rules.append("power_rule")
        if function.is_Mul:
            rules.append("product_rule")
        if function.is_Add:
            rules.append("sum_rule")
        if function.has(sp.sin, sp.cos, sp.tan):
            rules.append("trigonometric_rule")
        if function.has(sp.exp):
            rules.append("exponential_rule")
        if function.has(sp.log):
            rules.append("logarithmic_rule")
        
        return rules if rules else ["basic_rule"]
    
    def _identify_integration_method(self, integrand: sp.Expr) -> str:
        """Identify the best integration method"""
        if integrand.is_Pow:
            return "power_rule"
        elif integrand.has(sp.sin, sp.cos, sp.tan):
            return "trigonometric"
        elif integrand.has(sp.exp):
            return "exponential"
        elif integrand.has(sp.log):
            return "logarithmic"
        else:
            return "basic"
    
    def _differentiate_with_steps(self, function: sp.Expr, steps: List[Step]) -> sp.Expr:
        """Differentiate function and add steps"""
        derivative = diff(function)
        steps.append(self._create_step(
            "differentiate",
            "Apply differentiation",
            f"d/dx({function}) = {derivative}",
            sp.latex(derivative)
        ))
        return derivative
    
    def _integrate_with_steps(self, integrand: sp.Expr, method: str, steps: List[Step]) -> sp.Expr:
        """Integrate function and add steps"""
        integral_result = integrate(integrand)
        steps.append(self._create_step(
            "integrate",
            f"Apply {method} integration",
            f"∫({integrand}) dx = {integral_result}",
            sp.latex(integral_result)
        ))
        return integral_result
    
    def _evaluate_limit_with_steps(self, expression: sp.Expr, variable: sp.Symbol, 
                                 point: sp.Expr, steps: List[Step]) -> sp.Expr:
        """Evaluate limit and add steps"""
        limit_result = limit(expression, variable, point)
        steps.append(self._create_step(
            "evaluate_limit",
            f"Evaluate limit as {variable} → {point}",
            f"lim({expression}) = {limit_result}",
            sp.latex(limit_result)
        ))
        return limit_result
    
    def _check_indeterminate_form(self, expression: sp.Expr, variable: sp.Symbol, point: sp.Expr) -> Optional[str]:
        """Check for indeterminate forms"""
        try:
            # Substitute the limit point
            result = expression.subs(variable, point)
            if result == sp.oo or result == -sp.oo:
                return "∞"
            elif result == 0:
                return "0/0"
            elif result == sp.oo - sp.oo:
                return "∞ - ∞"
            else:
                return None
        except:
            return None
    
    def _identify_differential_equation_type(self, equation: sp.Equality) -> str:
        """Identify the type of differential equation"""
        # Simplified classification
        if equation.has(Derivative):
            return "ordinary"
        else:
            return "algebraic"
    
    def _get_differential_equation_order(self, equation: sp.Equality) -> int:
        """Get the order of the differential equation"""
        # Simplified - count derivatives
        return len([arg for arg in equation.args if isinstance(arg, Derivative)])
    
    def _get_function_type(self, function: sp.Expr) -> str:
        """Get the type of function"""
        if function.has(sp.sin, sp.cos, sp.tan):
            return "trigonometric"
        elif function.has(sp.exp):
            return "exponential"
        elif function.has(sp.log):
            return "logarithmic"
        elif function.is_Pow:
            return "power"
        elif function.is_polynomial():
            return "polynomial"
        else:
            return "general"
    
    def _assess_complexity(self, function: sp.Expr) -> str:
        """Assess the complexity of the function"""
        complexity_score = 0
        
        # Count operations
        complexity_score += len(function.args)
        
        # Check for special functions
        if function.has(sp.sin, sp.cos, sp.tan, sp.exp, sp.log):
            complexity_score += 2
        
        if complexity_score <= 3:
            return "simple"
        elif complexity_score <= 6:
            return "moderate"
        else:
            return "complex"
    
    def _get_capabilities(self) -> List[str]:
        """Get list of calculus capabilities"""
        return [
            "derivatives",
            "integrals",
            "limits",
            "differential_equations",
            "power_rule",
            "chain_rule",
            "product_rule",
            "quotient_rule",
            "trigonometric_differentiation",
            "exponential_differentiation",
            "logarithmic_differentiation"
        ]

