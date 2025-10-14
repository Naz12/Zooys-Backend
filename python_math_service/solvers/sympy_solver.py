import sympy as sp
from sympy.parsing.sympy_parser import parse_expr
from sympy import symbols, solve, simplify, expand, factor, diff, integrate, limit, latex
from typing import Dict, Any, List
import re
import logging
from .base_solver import BaseSolver

logger = logging.getLogger(__name__)

class SymPySolver(BaseSolver):
    """SymPy-based solver for all types of math problems"""
    
    def __init__(self):
        super().__init__()
        self.library = "SymPy"
        self.supported_types = [
            "arithmetic", "algebra", "calculus", "geometry", 
            "statistics", "trigonometry", "complex_numbers"
        ]
    
    def can_solve(self, problem_text: str) -> bool:
        """SymPy can handle most mathematical problems"""
        # Check if it contains mathematical content
        math_indicators = [
            r'\d+',  # numbers
            r'[+\-*/=]',  # operators
            r'[a-zA-Z]',  # variables
            r'[()]',  # parentheses
            r'sin|cos|tan|log|ln|sqrt|exp',  # functions
        ]
        
        return any(re.search(pattern, problem_text) for pattern in math_indicators)
    
    def solve(self, problem_text: str, subject_area: str = "maths", difficulty_level: str = "intermediate") -> Dict[str, Any]:
        """Solve using SymPy with intelligent problem type detection"""
        try:
            problem_type = self._detect_problem_type(problem_text)
            logger.info(f"SymPy solving {problem_type} problem: {problem_text}")
            
            if problem_type == "arithmetic":
                return self._solve_arithmetic(problem_text)
            elif problem_type == "algebra":
                return self._solve_algebra(problem_text)
            elif problem_type == "calculus":
                return self._solve_calculus(problem_text)
            elif problem_type == "geometry":
                return self._solve_geometry(problem_text)
            elif problem_type == "trigonometry":
                return self._solve_trigonometry(problem_text)
            else:
                return self._solve_general(problem_text)
                
        except Exception as e:
            logger.error(f"SymPy solver error: {e}")
            return {
                "success": False,
                "error": f"SymPy solver failed: {str(e)}",
                "solver_used": "SymPy",
                "problem_type": "unknown"
            }
    
    def get_supported_types(self) -> List[str]:
        return self.supported_types
    
    def _detect_problem_type(self, problem_text: str) -> str:
        """Detect the type of mathematical problem"""
        text = problem_text.lower().strip()
        
        # Calculus indicators
        if any(word in text for word in ['derivative', 'integral', 'limit', 'differentiate', 'integrate']):
            return "calculus"
        
        # Trigonometry indicators
        if any(func in text for func in ['sin', 'cos', 'tan', 'sinh', 'cosh', 'tanh']):
            return "trigonometry"
        
        # Geometry indicators
        if any(word in text for word in ['area', 'perimeter', 'volume', 'triangle', 'circle', 'square', 'rectangle']):
            return "geometry"
        
        # Algebra indicators (equations with variables)
        if '=' in text and re.search(r'[a-zA-Z]', text):
            return "algebra"
        
        # Statistics indicators
        if any(word in text for word in ['mean', 'median', 'mode', 'standard deviation', 'variance', 'probability']):
            return "statistics"
        
        # Default to arithmetic for simple expressions
        return "arithmetic"
    
    def _solve_arithmetic(self, problem_text: str) -> Dict[str, Any]:
        """Solve arithmetic problems using SymPy"""
        try:
            # Clean the expression
            expression = self._clean_expression(problem_text)
            
            # Parse and evaluate
            parsed_expr = parse_expr(expression, transformations='all')
            result = parsed_expr.evalf()
            
            # Generate steps
            steps = self._generate_arithmetic_steps(expression, parsed_expr)
            
            return {
                "success": True,
                "result": str(result),
                "latex": latex(result),
                "steps": steps,
                "solver_used": "SymPy",
                "problem_type": "arithmetic",
                "method": "SymPy arithmetic evaluation"
            }
            
        except Exception as e:
            logger.error(f"Arithmetic solving failed: {e}")
            return {
                "success": False,
                "error": f"Arithmetic solving failed: {str(e)}",
                "solver_used": "SymPy",
                "problem_type": "arithmetic"
            }
    
    def _solve_algebra(self, problem_text: str) -> Dict[str, Any]:
        """Solve algebraic equations using SymPy"""
        try:
            # Extract equation
            if '=' in problem_text:
                left, right = problem_text.split('=', 1)
                equation = f"{left.strip()} - ({right.strip()})"
            else:
                equation = problem_text
            
            # Parse equation
            parsed_eq = parse_expr(equation, transformations='all')
            
            # Solve for variables
            variables = list(parsed_eq.free_symbols)
            if variables:
                solutions = solve(parsed_eq, variables[0])
                result = solutions[0] if solutions else "No solution"
            else:
                result = parsed_eq.evalf()
            
            # Generate steps
            steps = self._generate_algebra_steps(problem_text, parsed_eq, variables, solutions)
            
            return {
                "success": True,
                "result": str(result),
                "latex": latex(result),
                "steps": steps,
                "solver_used": "SymPy",
                "problem_type": "algebra",
                "method": "SymPy algebraic solving"
            }
            
        except Exception as e:
            logger.error(f"Algebra solving failed: {e}")
            return {
                "success": False,
                "error": f"Algebra solving failed: {str(e)}",
                "solver_used": "SymPy",
                "problem_type": "algebra"
            }
    
    def _solve_calculus(self, problem_text: str) -> Dict[str, Any]:
        """Solve calculus problems using SymPy"""
        try:
            # This is a simplified version - can be expanded
            if 'derivative' in problem_text.lower() or 'differentiate' in problem_text.lower():
                # Extract function and variable
                # This would need more sophisticated parsing
                return self._solve_general(problem_text)
            elif 'integral' in problem_text.lower() or 'integrate' in problem_text.lower():
                return self._solve_general(problem_text)
            else:
                return self._solve_general(problem_text)
                
        except Exception as e:
            logger.error(f"Calculus solving failed: {e}")
            return {
                "success": False,
                "error": f"Calculus solving failed: {str(e)}",
                "solver_used": "SymPy",
                "problem_type": "calculus"
            }
    
    def _solve_geometry(self, problem_text: str) -> Dict[str, Any]:
        """Solve geometry problems using SymPy"""
        try:
            # For now, treat as general problem
            return self._solve_general(problem_text)
            
        except Exception as e:
            logger.error(f"Geometry solving failed: {e}")
            return {
                "success": False,
                "error": f"Geometry solving failed: {str(e)}",
                "solver_used": "SymPy",
                "problem_type": "geometry"
            }
    
    def _solve_trigonometry(self, problem_text: str) -> Dict[str, Any]:
        """Solve trigonometry problems using SymPy"""
        try:
            return self._solve_general(problem_text)
            
        except Exception as e:
            logger.error(f"Trigonometry solving failed: {e}")
            return {
                "success": False,
                "error": f"Trigonometry solving failed: {str(e)}",
                "solver_used": "SymPy",
                "problem_type": "trigonometry"
            }
    
    def _solve_general(self, problem_text: str) -> Dict[str, Any]:
        """General SymPy solving for any mathematical expression"""
        try:
            # Clean and parse
            expression = self._clean_expression(problem_text)
            parsed_expr = parse_expr(expression, transformations='all')
            
            # Try to evaluate
            result = parsed_expr.evalf()
            
            return {
                "success": True,
                "result": str(result),
                "latex": latex(result),
                "steps": [{"step": 1, "description": f"Evaluated: {expression}", "result": str(result)}],
                "solver_used": "SymPy",
                "problem_type": "general",
                "method": "SymPy general evaluation"
            }
            
        except Exception as e:
            logger.error(f"General solving failed: {e}")
            return {
                "success": False,
                "error": f"General solving failed: {str(e)}",
                "solver_used": "SymPy",
                "problem_type": "general"
            }
    
    def _clean_expression(self, text: str) -> str:
        """Clean mathematical expression for SymPy parsing"""
        # Remove common text
        text = re.sub(r'[^\d\s\+\-\*/\(\)\.\^a-zA-Z]', '', text)
        
        # Convert common symbols
        text = text.replace('÷', '/')
        text = text.replace('×', '*')
        text = text.replace('**', '^')
        
        # Handle fractions
        text = re.sub(r'(\d+)/(\d+)', r'(\1)/(\2)', text)
        
        return text.strip()
    
    def _generate_arithmetic_steps(self, expression: str, parsed_expr) -> List[Dict[str, Any]]:
        """Generate step-by-step solution for arithmetic"""
        steps = []
        steps.append({
            "step": 1,
            "description": f"Original expression: {expression}",
            "result": expression
        })
        
        # Show simplified form
        simplified = simplify(parsed_expr)
        if simplified != parsed_expr:
            steps.append({
                "step": 2,
                "description": f"Simplified: {simplified}",
                "result": str(simplified)
            })
        
        # Final result
        result = simplified.evalf()
        steps.append({
            "step": len(steps) + 1,
            "description": f"Final result: {result}",
            "result": str(result)
        })
        
        return steps
    
    def _generate_algebra_steps(self, problem_text: str, parsed_eq, variables, solutions) -> List[Dict[str, Any]]:
        """Generate step-by-step solution for algebra"""
        steps = []
        steps.append({
            "step": 1,
            "description": f"Original equation: {problem_text}",
            "result": problem_text
        })
        
        if variables:
            var = variables[0]
            steps.append({
                "step": 2,
                "description": f"Rearranged equation: {parsed_eq} = 0",
                "result": f"{parsed_eq} = 0"
            })
            
            if solutions:
                steps.append({
                    "step": 3,
                    "description": f"Solution for {var}: {solutions[0]}",
                    "result": str(solutions[0])
                })
        
        return steps
