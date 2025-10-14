"""
Problem Parser Service

Classifies mathematical problems and routes them to appropriate solvers.
Uses the new SolverRegistry for flexible solver management.
"""

import re
import sympy as sp
from sympy.parsing.sympy_parser import parse_expr
from typing import Dict, List, Any, Optional, Tuple
import logging

from solvers.solver_registry import SolverRegistry

logger = logging.getLogger(__name__)

class ProblemParser:
    """
    Problem classifier that routes problems to the best available solver
    using the SolverRegistry system.
    """
    
    def __init__(self):
        self.solver_registry = SolverRegistry()
        
        # Classification patterns for problem type detection
        self.classification_patterns = self._initialize_patterns()
    
    def _initialize_patterns(self) -> Dict[str, Dict[str, List[str]]]:
        """Initialize classification patterns for each subject"""
        return {
            'calculus': {
                'keywords': [
                    'derivative', 'integral', 'limit', 'differentiate', 
                    'integrate', 'tangent line', 'rate of change'
                ],
                'symbols': [r'd/dx', r'∫', r'∂', r'lim', r'dy/dx'],
                'phrases': [r'find.*derivative', r'evaluate.*integral', 
                           r'area under.*curve']
            },
            'algebra': {
                'keywords': [
                    'solve', 'equation', 'simplify', 'factor', 'expand',
                    'polynomial', 'quadratic', 'linear', 'variable'
                ],
                'symbols': [r'[xyz]\s*=', r'\^2', r'x\^', r'='],
                'phrases': [r'solve for [xyz]', r'find [xyz]', r'what is [xyz]']
            },
            'geometry': {
                'keywords': [
                    'area', 'volume', 'perimeter', 'angle', 'triangle',
                    'circle', 'rectangle', 'sphere', 'radius', 'diameter'
                ],
                'symbols': [r'π', r'°', r'∠'],
                'phrases': [r'area of', r'volume of', r'perimeter of']
            },
            'statistics': {
                'keywords': [
                    'mean', 'median', 'mode', 'average', 'probability',
                    'standard deviation', 'variance', 'distribution'
                ],
                'symbols': [r'σ', r'μ', r'%'],
                'phrases': [r'find.*average', r'calculate.*mean', r'probability of']
            },
            'arithmetic': {
                'keywords': [
                    'add', 'subtract', 'multiply', 'divide', 'sum',
                    'difference', 'product', 'quotient', 'percentage'
                ],
                'symbols': [r'^\d+\s*[\+\-\*/]\s*\d+', r'\d+%'],
                'phrases': [r'what is \d+', r'calculate \d+']
            }
        }
    
    def classify_and_solve(self, problem_text: str, subject_area: str = None, 
                          difficulty_level: str = "intermediate", **kwargs) -> Dict[str, Any]:
        """
        Classify problem and solve using the best available solver
        
        Args:
            problem_text: The mathematical problem as text
            subject_area: Optional subject area hint from user
            difficulty_level: Difficulty level (beginner, intermediate, advanced)
            **kwargs: Additional parameters
            
        Returns:
            Dict: Classification result and solution
        """
        try:
            # Step 1: Validate problem
            validation = self.validate_problem(problem_text)
            if not validation['valid']:
                return {
                    'success': False,
                    'error': validation['error'],
                    'solver_used': 'none'
                }
            
            # Step 2: Classify problem type
            classification = self._classify_problem(problem_text, subject_area)
            
            # Step 3: Solve using solver registry
            solution = self.solver_registry.solve_problem(
                problem_text=problem_text,
                subject_area=subject_area or classification.get('subject', 'maths'),
                difficulty_level=difficulty_level,
                preferred_solver=None  # Let registry choose best solver
            )
            
            # Step 4: Combine results
            result = {
                'success': solution.get('success', False),
                'classification': classification,
                'solution': solution,
                'solver_used': solution.get('solver_used', 'unknown'),
                'solver_registry_info': solution.get('solver_registry', {})
            }
            
            if not solution.get('success', False):
                result['error'] = solution.get('error', 'Unknown error')
            
            return result
            
        except Exception as e:
            logger.error(f"Problem parsing failed: {e}")
            return {
                'success': False,
                'error': f'Problem parsing failed: {str(e)}',
                'classification': {'subject': 'unknown', 'confidence': 0.0},
                'solver_used': 'none'
            }
    
    def _classify_problem(self, problem_text: str, subject_area: str = None) -> Dict[str, Any]:
        """
        Classify the type of mathematical problem
        
        Args:
            problem_text: The mathematical problem as text
            subject_area: Optional subject area hint
            
        Returns:
            Dict: Classification result with subject, confidence, and method
        """
        # Quick keyword-based classification
        classification = self._classify_by_keywords(problem_text)
        
        # If user provided subject area and confidence is low, use user input
        if subject_area and classification.get('confidence', 0) < 0.7:
            user_subject = subject_area.lower()
            if user_subject in ['arithmetic', 'algebra', 'calculus', 'geometry', 'statistics']:
                classification = {
                    'subject': user_subject,
                    'confidence': 0.8,
                    'method': 'user_provided'
                }
        
        # If still no classification, default to arithmetic
        if not classification.get('subject'):
            classification = {
                'subject': 'arithmetic',
                'confidence': 0.5,
                'method': 'default_fallback'
            }
        
        return classification
    
    def _classify_by_keywords(self, problem_text: str) -> Dict[str, Any]:
        """
        Classify using keyword patterns
        
        Args:
            problem_text: The mathematical problem as text
            
        Returns:
            Dict: Classification result
        """
        problem_lower = problem_text.lower()
        scores = {}
        
        for subject, patterns in self.classification_patterns.items():
            score = 0
            
            # Check keywords (weight: 2)
            score += sum(2 for kw in patterns['keywords'] if kw in problem_lower)
            
            # Check symbols (weight: 3)
            score += sum(3 for sym in patterns['symbols'] 
                        if re.search(sym, problem_text))
            
            # Check phrases (weight: 5)
            score += sum(5 for phrase in patterns['phrases'] 
                        if re.search(phrase, problem_lower))
            
            scores[subject] = score
        
        if max(scores.values()) > 0:
            best_subject = max(scores, key=scores.get)
            confidence = scores[best_subject] / sum(scores.values()) if sum(scores.values()) > 0 else 0
            return {
                'subject': best_subject,
                'confidence': min(confidence, 0.9),
                'method': 'keyword_matching'
            }
        
        return {'subject': None, 'confidence': 0}
    
    def get_available_solvers(self) -> List[str]:
        """Get list of available solvers"""
        return self.solver_registry.get_available_solvers()
    
    def get_solver_info(self) -> Dict[str, Dict[str, Any]]:
        """Get information about all registered solvers"""
        return self.solver_registry.get_solver_info()
    
    def register_solver(self, name: str, solver):
        """Register a new solver"""
        self.solver_registry.register_solver(name, solver)
    
    def validate_problem(self, problem_text: str) -> Dict[str, Any]:
        """
        Validate if the problem can be processed
        
        Args:
            problem_text: The mathematical problem as text
            
        Returns:
            Dict: Validation result
        """
        if not problem_text or not problem_text.strip():
            return {
                'valid': False,
                'error': 'Empty problem text'
            }
        
        if len(problem_text) > 1000:
            return {
                'valid': False,
                'error': 'Problem text too long (max 1000 characters)'
            }
        
        # Check if it contains any mathematical content
        has_numbers = bool(re.search(r'\d+', problem_text))
        has_math_symbols = bool(re.search(r'[\+\-\*/=<>]', problem_text))
        has_math_keywords = any(kw in problem_text.lower() for kw in [
            'solve', 'calculate', 'find', 'compute', 'evaluate'
        ])
        
        if not (has_numbers or has_math_symbols or has_math_keywords):
            return {
                'valid': False,
                'error': 'No mathematical content detected'
            }
        
        return {
            'valid': True,
            'message': 'Problem is valid for processing'
        }