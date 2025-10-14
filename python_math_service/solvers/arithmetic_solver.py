"""
Arithmetic Solver

Handles basic arithmetic operations including addition, subtraction, multiplication,
division, fractions, decimals, percentages, and order of operations (PEMDAS).
"""

import re
import math
import fractions
from typing import Dict, List, Any, Optional, Union
import logging
import sympy as sp
from sympy.parsing.sympy_parser import parse_expr

from solvers.base_solver import BaseSolver, Solution, Step

logger = logging.getLogger(__name__)

class ArithmeticSolver(BaseSolver):
    """
    Solver for arithmetic problems including:
    - Basic operations (+, -, *, /)
    - Order of operations (PEMDAS)
    - Fractions and decimals
    - Percentages and ratios
    - Simple word problems
    """
    
    def __init__(self, timeout: int = 5):
        super().__init__(timeout)
        self.solver_name = "ArithmeticSolver"
    
    def can_solve(self, problem_text: str, subject_area: str = None) -> bool:
        """
        Check if this is an arithmetic problem
        
        Args:
            problem_text: The mathematical problem as text
            subject_area: Optional subject area hint
            
        Returns:
            bool: True if this is an arithmetic problem
        """
        problem_lower = problem_text.lower()
        
        # Check for arithmetic keywords
        arithmetic_keywords = [
            'add', 'subtract', 'multiply', 'divide', 'sum', 'difference',
            'product', 'quotient', 'plus', 'minus', 'times', 'divided by',
            'arithmetic', 'basic', 'simple', 'calculate', 'compute'
        ]
        
        # Check for arithmetic operations
        operations = ['+', '-', '*', '/', '×', '÷', 'plus', 'minus', 'times', 'divided by']
        
        # Check for numbers and simple expressions
        has_numbers = bool(re.search(r'\d+', problem_text))
        has_operations = any(op in problem_text for op in operations)
        
        # Check for percentage problems
        has_percentage = '%' in problem_text or 'percent' in problem_lower
        
        # Check for fraction problems
        has_fractions = '/' in problem_text and re.search(r'\d+/\d+', problem_text)
        
        # Subject area hint
        if subject_area and subject_area.lower() in ['arithmetic', 'basic']:
            return True
        
        # Check if it looks like an arithmetic problem
        keyword_score = sum(1 for kw in arithmetic_keywords if kw in problem_lower)
        
        # Simple arithmetic expressions (e.g., "2 + 3", "5 * 4")
        simple_expression = has_numbers and has_operations and len(problem_text.split()) <= 10
        
        return (keyword_score >= 1 or simple_expression or has_percentage or has_fractions)
    
    def solve(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve arithmetic problem with step-by-step extraction
        
        Args:
            problem_text: The arithmetic problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        try:
            # Determine problem type and solve accordingly
            if self._is_percentage_problem(problem_text):
                return self._solve_percentage_problem(problem_text, **kwargs)
            elif self._is_fraction_problem(problem_text):
                return self._solve_fraction_problem(problem_text, **kwargs)
            elif self._is_word_problem(problem_text):
                return self._solve_word_problem(problem_text, **kwargs)
            else:
                return self._solve_basic_arithmetic(problem_text, **kwargs)
                
        except Exception as e:
            logger.error(f"Arithmetic solver failed: {e}")
            return self._create_error_solution(f"Arithmetic solving failed: {str(e)}")
    
    def _is_percentage_problem(self, problem_text: str) -> bool:
        """Check if this is a percentage problem"""
        percentage_indicators = ['%', 'percent', 'percentage']
        return any(indicator in problem_text.lower() for indicator in percentage_indicators)
    
    def _is_fraction_problem(self, problem_text: str) -> bool:
        """Check if this is a fraction problem"""
        # Check for fraction keywords (explicit fraction problems)
        has_fraction_keywords = any(kw in problem_text.lower() for kw in ['fraction', 'numerator', 'denominator', 'add fractions', 'subtract fractions'])
        
        # Only treat as fraction problem if it's explicitly about fractions
        # Don't treat expressions with fractions as fraction problems
        if has_fraction_keywords:
            return True
            
        # Check if it's a simple fraction operation (like "1/2 + 1/3")
        # but not a complex arithmetic expression with fractions
        has_fraction_notation = bool(re.search(r'\d+/\d+', problem_text))
        has_arithmetic_operations = any(op in problem_text for op in ['+', '-', '*', '/', '(', ')'])
        
        # If it has fractions but also complex arithmetic, treat as basic arithmetic
        if has_fraction_notation and has_arithmetic_operations:
            # Count the complexity - if it has multiple operations, it's basic arithmetic
            operation_count = sum(1 for op in ['+', '-', '*', '/'] if op in problem_text)
            if operation_count > 1:
                return False
        
        return has_fraction_notation and not has_arithmetic_operations
    
    def _is_word_problem(self, problem_text: str) -> bool:
        """Check if this is a word problem"""
        word_problem_indicators = ['has', 'have', 'total', 'altogether', 'left', 'remaining', 'more than', 'less than']
        return any(indicator in problem_text.lower() for indicator in word_problem_indicators)
    
    def _solve_basic_arithmetic(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve basic arithmetic expression with step-by-step extraction
        
        Args:
            problem_text: The arithmetic expression as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Parse the expression
            expression = self._parse_expression(problem_text)
            steps.append(self._create_step(
                "parse",
                f"Parse the expression: {expression}",
                f"Expression: {expression}",
                confidence=0.95
            ))
            
            # Step 2: Apply order of operations (PEMDAS)
            result = self._evaluate_with_pemdas(expression, steps)
            
            return Solution(
                answer=str(result),
                steps=steps,
                method="arithmetic_evaluation",
                confidence=0.95,
                verification=f"Expression evaluation: {expression} = {result}",
                metadata={
                    'expression': expression,
                    'operations_count': self._count_operations(expression),
                    'complexity': self._assess_complexity(expression)
                }
            )
            
        except Exception as e:
            logger.error(f"Basic arithmetic failed: {e}")
            return self._create_error_solution(f"Basic arithmetic failed: {str(e)}")
    
    def _solve_percentage_problem(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve percentage problem with step extraction
        
        Args:
            problem_text: The percentage problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Identify percentage type
            pct_type = self._identify_percentage_type(problem_text)
            steps.append(self._create_step(
                "identify_type",
                f"Identify percentage type: {pct_type}",
                f"Type: {pct_type}",
                confidence=0.9
            ))
            
            # Step 2: Extract values
            values = self._extract_percentage_values(problem_text)
            steps.append(self._create_step(
                "extract_values",
                f"Extract values: {values}",
                f"Values: {values}",
                confidence=0.9
            ))
            
            # Step 3: Apply percentage formula
            formula = self._get_percentage_formula(pct_type)
            steps.append(self._create_step(
                "apply_formula",
                f"Apply formula: {formula}",
                f"Formula: {formula}",
                confidence=0.95
            ))
            
            # Step 4: Calculate result
            result = self._calculate_percentage(pct_type, values, steps)
            
            return Solution(
                answer=str(result),
                steps=steps,
                method=f"percentage_{pct_type}",
                confidence=0.95,
                verification=f"Percentage calculation: {result}",
                metadata={
                    'percentage_type': pct_type,
                    'values': values,
                    'formula': formula
                }
            )
            
        except Exception as e:
            logger.error(f"Percentage calculation failed: {e}")
            return self._create_error_solution(f"Percentage calculation failed: {str(e)}")
    
    def _solve_fraction_problem(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve fraction problem with step extraction
        
        Args:
            problem_text: The fraction problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Identify fraction operation
            operation = self._identify_fraction_operation(problem_text)
            steps.append(self._create_step(
                "identify_operation",
                f"Identify fraction operation: {operation}",
                f"Operation: {operation}",
                confidence=0.9
            ))
            
            # Step 2: Extract fractions
            fractions = self._extract_fractions(problem_text)
            steps.append(self._create_step(
                "extract_fractions",
                f"Extract fractions: {fractions}",
                f"Fractions: {fractions}",
                confidence=0.9
            ))
            
            # Step 3: Perform fraction operation
            result = self._perform_fraction_operation(operation, fractions, steps)
            
            return Solution(
                answer=str(result),
                steps=steps,
                method=f"fraction_{operation}",
                confidence=0.95,
                verification=f"Fraction operation result: {result}",
                metadata={
                    'operation': operation,
                    'fractions': [str(f) for f in fractions],
                    'result_type': 'fraction'
                }
            )
            
        except Exception as e:
            logger.error(f"Fraction calculation failed: {e}")
            return self._create_error_solution(f"Fraction calculation failed: {str(e)}")
    
    def _solve_word_problem(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve arithmetic word problem with step extraction
        
        Args:
            problem_text: The word problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Identify the operation needed
            operation = self._identify_word_problem_operation(problem_text)
            steps.append(self._create_step(
                "identify_operation",
                f"Identify operation needed: {operation}",
                f"Operation: {operation}",
                confidence=0.9
            ))
            
            # Step 2: Extract numbers
            numbers = self._extract_numbers_from_word_problem(problem_text)
            steps.append(self._create_step(
                "extract_numbers",
                f"Extract numbers: {numbers}",
                f"Numbers: {numbers}",
                confidence=0.9
            ))
            
            # Step 3: Set up the calculation
            calculation = self._setup_word_problem_calculation(operation, numbers, steps)
            
            # Step 4: Calculate result
            result = self._calculate_word_problem_result(operation, numbers, steps)
            
            return Solution(
                answer=str(result),
                steps=steps,
                method=f"word_problem_{operation}",
                confidence=0.9,
                verification=f"Word problem solution: {result}",
                metadata={
                    'operation': operation,
                    'numbers': numbers,
                    'problem_type': 'word_problem'
                }
            )
            
        except Exception as e:
            logger.error(f"Word problem failed: {e}")
            return self._create_error_solution(f"Word problem failed: {str(e)}")
    
    def _parse_expression(self, problem_text: str) -> str:
        """Parse and normalize the arithmetic expression"""
        # Clean up the expression
        expression = problem_text.strip()
        
        # Replace common word operators with symbols
        replacements = {
            'plus': '+',
            'minus': '-',
            'times': '*',
            'multiplied by': '*',
            'divided by': '/',
            '×': '*',
            '÷': '/'
        }
        
        for word, symbol in replacements.items():
            expression = expression.replace(word, symbol)
        
        return expression
    
    def _evaluate_with_pemdas(self, expression: str, steps: List[Step]) -> float:
        """Evaluate expression using PEMDAS order of operations with SymPy"""
        try:
            # Step 1: Parentheses
            if '(' in expression:
                steps.append(self._create_step(
                    "parentheses",
                    "Evaluate expressions in parentheses first",
                    "PEMDAS: P (Parentheses)",
                    confidence=0.95
                ))
            
            # Step 2: Exponents
            if '**' in expression or '^' in expression:
                steps.append(self._create_step(
                    "exponents",
                    "Evaluate exponents",
                    "PEMDAS: E (Exponents)",
                    confidence=0.95
                ))
            
            # Step 3: Multiplication and Division
            if '*' in expression or '/' in expression:
                steps.append(self._create_step(
                    "multiplication_division",
                    "Evaluate multiplication and division from left to right",
                    "PEMDAS: MD (Multiplication, Division)",
                    confidence=0.95
                ))
            
            # Step 4: Addition and Subtraction
            if '+' in expression or '-' in expression:
                steps.append(self._create_step(
                    "addition_subtraction",
                    "Evaluate addition and subtraction from left to right",
                    "PEMDAS: AS (Addition, Subtraction)",
                    confidence=0.95
                ))
            
            # Use SymPy for safe and accurate evaluation
            try:
                # Parse the expression with SymPy
                parsed_expr = parse_expr(expression, transformations='all')
                
                # Evaluate to get the result
                result = float(parsed_expr.evalf())
                
                steps.append(self._create_step(
                    "final_result",
                    f"Final calculation: {expression} = {result}",
                    f"Result = {result}",
                    confidence=0.95
                ))
                
                return result
                
            except Exception as sympy_error:
                logger.warning(f"SymPy evaluation failed, falling back to eval: {sympy_error}")
                # Fallback to eval if SymPy fails
                result = eval(expression)
                
                steps.append(self._create_step(
                    "final_result",
                    f"Final calculation: {expression} = {result}",
                    f"Result = {result}",
                    confidence=0.9
                ))
                
                return result
            
        except Exception as e:
            logger.error(f"Expression evaluation failed: {e}")
            raise
    
    def _identify_percentage_type(self, problem_text: str) -> str:
        """Identify the type of percentage problem"""
        problem_lower = problem_text.lower()
        
        if 'what percent' in problem_lower or 'what percentage' in problem_lower:
            return 'find_percentage'
        elif 'percent of' in problem_lower:
            return 'find_part'
        elif 'is what percent' in problem_lower:
            return 'find_percentage'
        else:
            return 'basic_percentage'
    
    def _extract_percentage_values(self, problem_text: str) -> Dict[str, float]:
        """Extract values from percentage problem"""
        values = {}
        numbers = re.findall(r'\d+\.?\d*', problem_text)
        
        if numbers:
            values['value1'] = float(numbers[0])
            if len(numbers) > 1:
                values['value2'] = float(numbers[1])
        
        return values
    
    def _get_percentage_formula(self, pct_type: str) -> str:
        """Get the formula for percentage calculation"""
        formulas = {
            'find_percentage': 'Percentage = (Part / Whole) × 100',
            'find_part': 'Part = (Percentage / 100) × Whole',
            'basic_percentage': 'Result = (Percentage / 100) × Number'
        }
        return formulas.get(pct_type, 'Unknown formula')
    
    def _calculate_percentage(self, pct_type: str, values: Dict[str, float], 
                            steps: List[Step]) -> float:
        """Calculate percentage and add steps"""
        if pct_type == 'find_percentage':
            part = values.get('value1', 0.0)
            whole = values.get('value2', 1.0)
            percentage = (part / whole) * 100
            
            steps.append(self._create_step(
                "calculate_percentage",
                f"Calculate percentage: ({part} / {whole}) × 100 = {percentage}%",
                f"Percentage = {percentage}%",
                confidence=0.95
            ))
            
            return round(percentage, 2)
        
        elif pct_type == 'find_part':
            percentage = values.get('value1', 0.0)
            whole = values.get('value2', 1.0)
            part = (percentage / 100) * whole
            
            steps.append(self._create_step(
                "calculate_part",
                f"Calculate part: ({percentage} / 100) × {whole} = {part}",
                f"Part = {part}",
                confidence=0.95
            ))
            
            return round(part, 2)
        
        else:
            # Basic percentage calculation
            percentage = values.get('value1', 0.0)
            number = values.get('value2', 1.0)
            result = (percentage / 100) * number
            
            steps.append(self._create_step(
                "calculate_basic",
                f"Calculate: ({percentage} / 100) × {number} = {result}",
                f"Result = {result}",
                confidence=0.95
            ))
            
            return round(result, 2)
    
    def _identify_fraction_operation(self, problem_text: str) -> str:
        """Identify the fraction operation"""
        problem_lower = problem_text.lower()
        
        if 'add' in problem_lower or '+' in problem_text:
            return 'addition'
        elif 'subtract' in problem_lower or '-' in problem_text:
            return 'subtraction'
        elif 'multiply' in problem_lower or '*' in problem_text:
            return 'multiplication'
        elif 'divide' in problem_lower or '/' in problem_text:
            return 'division'
        else:
            return 'simplify'
    
    def _extract_fractions(self, problem_text: str) -> List[fractions.Fraction]:
        """Extract fractions from the problem text"""
        fraction_list = []
        
        # Find fraction patterns like "3/4" or "1 1/2"
        fraction_patterns = re.findall(r'\d+/\d+', problem_text)
        
        for pattern in fraction_patterns:
            try:
                fraction_list.append(fractions.Fraction(pattern))
            except ValueError:
                continue
        
        return fraction_list
    
    def _perform_fraction_operation(self, operation: str, fractions_list: List[fractions.Fraction], 
                                  steps: List[Step]) -> fractions.Fraction:
        """Perform fraction operation and add steps"""
        if len(fractions_list) < 2:
            return fractions_list[0] if fractions_list else fractions.Fraction(0)
        
        frac1, frac2 = fractions_list[0], fractions_list[1]
        
        if operation == 'addition':
            result = frac1 + frac2
            steps.append(self._create_step(
                "add_fractions",
                f"Add fractions: {frac1} + {frac2} = {result}",
                f"Result = {result}",
                confidence=0.95
            ))
        
        elif operation == 'subtraction':
            result = frac1 - frac2
            steps.append(self._create_step(
                "subtract_fractions",
                f"Subtract fractions: {frac1} - {frac2} = {result}",
                f"Result = {result}",
                confidence=0.95
            ))
        
        elif operation == 'multiplication':
            result = frac1 * frac2
            steps.append(self._create_step(
                "multiply_fractions",
                f"Multiply fractions: {frac1} × {frac2} = {result}",
                f"Result = {result}",
                confidence=0.95
            ))
        
        elif operation == 'division':
            result = frac1 / frac2
            steps.append(self._create_step(
                "divide_fractions",
                f"Divide fractions: {frac1} ÷ {frac2} = {result}",
                f"Result = {result}",
                confidence=0.95
            ))
        
        else:
            result = frac1
        
        return result
    
    def _identify_word_problem_operation(self, problem_text: str) -> str:
        """Identify the operation needed for a word problem"""
        problem_lower = problem_text.lower()
        
        if any(word in problem_lower for word in ['add', 'plus', 'sum', 'total', 'altogether', 'combined']):
            return 'addition'
        elif any(word in problem_lower for word in ['subtract', 'minus', 'difference', 'left', 'remaining']):
            return 'subtraction'
        elif any(word in problem_lower for word in ['multiply', 'times', 'product', 'each', 'per']):
            return 'multiplication'
        elif any(word in problem_lower for word in ['divide', 'split', 'share', 'equally']):
            return 'division'
        else:
            return 'addition'  # Default
    
    def _extract_numbers_from_word_problem(self, problem_text: str) -> List[float]:
        """Extract numbers from word problem"""
        numbers = re.findall(r'\d+\.?\d*', problem_text)
        return [float(num) for num in numbers]
    
    def _setup_word_problem_calculation(self, operation: str, numbers: List[float], 
                                      steps: List[Step]) -> str:
        """Set up the calculation for word problem"""
        if len(numbers) >= 2:
            if operation == 'addition':
                calculation = f"{numbers[0]} + {numbers[1]}"
            elif operation == 'subtraction':
                calculation = f"{numbers[0]} - {numbers[1]}"
            elif operation == 'multiplication':
                calculation = f"{numbers[0]} × {numbers[1]}"
            elif operation == 'division':
                calculation = f"{numbers[0]} ÷ {numbers[1]}"
            else:
                calculation = f"{numbers[0]} + {numbers[1]}"
            
            steps.append(self._create_step(
                "setup_calculation",
                f"Set up calculation: {calculation}",
                f"Calculation: {calculation}",
                confidence=0.9
            ))
            
            return calculation
        
        return str(numbers[0]) if numbers else "0"
    
    def _calculate_word_problem_result(self, operation: str, numbers: List[float], 
                                     steps: List[Step]) -> float:
        """Calculate the result for word problem"""
        if len(numbers) >= 2:
            if operation == 'addition':
                result = numbers[0] + numbers[1]
            elif operation == 'subtraction':
                result = numbers[0] - numbers[1]
            elif operation == 'multiplication':
                result = numbers[0] * numbers[1]
            elif operation == 'division':
                result = numbers[0] / numbers[1] if numbers[1] != 0 else 0
            else:
                result = numbers[0] + numbers[1]
            
            steps.append(self._create_step(
                "calculate_result",
                f"Calculate result: {result}",
                f"Result = {result}",
                confidence=0.95
            ))
            
            return round(result, 2)
        
        return numbers[0] if numbers else 0.0
    
    def _count_operations(self, expression: str) -> int:
        """Count the number of operations in the expression"""
        operations = ['+', '-', '*', '/', '**', '^']
        return sum(1 for op in operations if op in expression)
    
    def _assess_complexity(self, expression: str) -> str:
        """Assess the complexity of the expression"""
        operation_count = self._count_operations(expression)
        
        if operation_count <= 1:
            return 'simple'
        elif operation_count <= 3:
            return 'moderate'
        else:
            return 'complex'
    
    def _get_capabilities(self) -> List[str]:
        """Get list of arithmetic capabilities"""
        return [
            "basic_arithmetic",
            "order_of_operations",
            "percentage_calculations",
            "fraction_operations",
            "word_problems",
            "addition",
            "subtraction",
            "multiplication",
            "division",
            "pemdas",
            "decimal_arithmetic",
            "integer_arithmetic"
        ]

