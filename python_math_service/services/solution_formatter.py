"""
Solution Formatter Service

Formats mathematical solutions for API responses with consistent structure,
LaTeX rendering, and proper error handling.
"""

import json
import logging
from typing import Dict, List, Any, Optional, Union
from datetime import datetime

logger = logging.getLogger(__name__)

class SolutionFormatter:
    """
    Service for formatting mathematical solutions including:
    - Standardizing response structure
    - LaTeX rendering for mathematical expressions
    - Error handling and fallback formatting
    - Metadata and timing information
    """
    
    def __init__(self):
        """Initialize solution formatter"""
        self.default_timeout = 5.0  # Default timeout in seconds
    
    def format_solution_response(self, 
                               success: bool,
                               solution_data: Optional[Dict[str, Any]] = None,
                               classification: Optional[Dict[str, Any]] = None,
                               explanation: Optional[Dict[str, Any]] = None,
                               error: Optional[str] = None,
                               metadata: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        """
        Format complete solution response for API
        
        Args:
            success: Whether the solution was successful
            solution_data: Solution data from solver
            classification: Problem classification result
            explanation: AI explanation data
            error: Error message if failed
            metadata: Additional metadata
            
        Returns:
            Dict: Formatted API response
        """
        try:
            response = {
                'success': success,
                'timestamp': datetime.utcnow().isoformat(),
                'request_id': self._generate_request_id()
            }
            
            if success and solution_data:
                # Format successful solution
                response.update(self._format_success_response(
                    solution_data, classification, explanation, metadata
                ))
            else:
                # Format error response
                response.update(self._format_error_response(error, metadata))
            
            return response
            
        except Exception as e:
            logger.error(f"Solution formatting failed: {e}")
            return self._format_error_response(f"Formatting failed: {str(e)}")
    
    def format_solve_response(self, 
                            solution_data: Dict[str, Any],
                            classification: Dict[str, Any],
                            metadata: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        """
        Format response for /solve endpoint (solution + steps only)
        
        Args:
            solution_data: Solution data from solver
            classification: Problem classification result
            metadata: Additional metadata
            
        Returns:
            Dict: Formatted solve response
        """
        try:
            return {
                'success': True,
                'timestamp': datetime.utcnow().isoformat(),
                'request_id': self._generate_request_id(),
                'classification': self._format_classification(classification),
                'solution': self._format_solution_data(solution_data),
                'metadata': self._format_metadata(metadata or {})
            }
            
        except Exception as e:
            logger.error(f"Solve response formatting failed: {e}")
            return self._format_error_response(f"Solve formatting failed: {str(e)}")
    
    def format_explain_response(self,
                              solution_data: Dict[str, Any],
                              classification: Dict[str, Any],
                              explanation: Dict[str, Any],
                              metadata: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        """
        Format response for /explain endpoint (solution + steps + explanation)
        
        Args:
            solution_data: Solution data from solver
            classification: Problem classification result
            explanation: AI explanation data
            metadata: Additional metadata
            
        Returns:
            Dict: Formatted explain response
        """
        try:
            return {
                'success': True,
                'timestamp': datetime.utcnow().isoformat(),
                'request_id': self._generate_request_id(),
                'classification': self._format_classification(classification),
                'solution': self._format_solution_data(solution_data),
                'explanation': self._format_explanation(explanation),
                'metadata': self._format_metadata(metadata or {})
            }
            
        except Exception as e:
            logger.error(f"Explain response formatting failed: {e}")
            return self._format_error_response(f"Explain formatting failed: {str(e)}")
    
    def format_latex_response(self,
                            problem_text: str,
                            solution_data: Dict[str, Any],
                            metadata: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        """
        Format response for /latex endpoint
        
        Args:
            problem_text: Original problem text
            solution_data: Solution data from solver
            metadata: Additional metadata
            
        Returns:
            Dict: Formatted LaTeX response
        """
        try:
            return {
                'success': True,
                'timestamp': datetime.utcnow().isoformat(),
                'request_id': self._generate_request_id(),
                'latex': {
                    'input': self._convert_to_latex(problem_text),
                    'solution': self._convert_solution_to_latex(solution_data)
                },
                'metadata': self._format_metadata(metadata or {})
            }
            
        except Exception as e:
            logger.error(f"LaTeX response formatting failed: {e}")
            return self._format_error_response(f"LaTeX formatting failed: {str(e)}")
    
    def _format_success_response(self,
                               solution_data: Dict[str, Any],
                               classification: Optional[Dict[str, Any]],
                               explanation: Optional[Dict[str, Any]],
                               metadata: Optional[Dict[str, Any]]) -> Dict[str, Any]:
        """Format successful solution response"""
        response = {}
        
        if classification:
            response['classification'] = self._format_classification(classification)
        
        if solution_data:
            response['solution'] = self._format_solution_data(solution_data)
        
        if explanation:
            response['explanation'] = self._format_explanation(explanation)
        
        if metadata:
            response['metadata'] = self._format_metadata(metadata)
        
        return response
    
    def _format_error_response(self, error: str, metadata: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        """Format error response"""
        response = {
            'error': {
                'message': error,
                'type': 'processing_error'
            }
        }
        
        if metadata:
            response['metadata'] = self._format_metadata(metadata)
        
        return response
    
    def _format_classification(self, classification: Dict[str, Any]) -> Dict[str, Any]:
        """Format classification data"""
        return {
            'subject': classification.get('subject', 'unknown'),
            'confidence': round(classification.get('confidence', 0.0), 3),
            'method': classification.get('method', 'unknown'),
            'fallback_subjects': classification.get('fallback_subjects', [])
        }
    
    def _format_solution_data(self, solution_data: Dict[str, Any]) -> Dict[str, Any]:
        """Format solution data"""
        return {
            'answer': str(solution_data.get('answer', '')),
            'method': solution_data.get('method', 'unknown'),
            'confidence': round(solution_data.get('confidence', 0.0), 3),
            'steps': self._format_steps(solution_data.get('steps', [])),
            'verification': solution_data.get('verification', ''),
            'metadata': solution_data.get('metadata', {})
        }
    
    def _format_steps(self, steps: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """Format solution steps"""
        formatted_steps = []
        
        for i, step in enumerate(steps):
            formatted_step = {
                'step_number': i + 1,
                'operation': step.get('operation', ''),
                'description': step.get('description', ''),
                'expression': step.get('expression', ''),
                'confidence': round(step.get('confidence', 1.0), 3)
            }
            
            # Add LaTeX if available
            if 'latex' in step:
                formatted_step['latex'] = step['latex']
            
            formatted_steps.append(formatted_step)
        
        return formatted_steps
    
    def _format_explanation(self, explanation: Dict[str, Any]) -> Dict[str, Any]:
        """Format AI explanation data"""
        return {
            'content': explanation.get('explanation', ''),
            'method': explanation.get('method', 'unknown'),
            'success': explanation.get('success', False),
            'tokens_used': explanation.get('tokens_used', 0),
            'error': explanation.get('error', '')
        }
    
    def _format_metadata(self, metadata: Dict[str, Any]) -> Dict[str, Any]:
        """Format metadata"""
        formatted_metadata = {
            'processing_time': metadata.get('processing_time', 0.0),
            'solver_used': metadata.get('solver_used', 'unknown'),
            'timestamp': datetime.utcnow().isoformat()
        }
        
        # Add any additional metadata
        for key, value in metadata.items():
            if key not in formatted_metadata:
                formatted_metadata[key] = value
        
        return formatted_metadata
    
    def _convert_to_latex(self, text: str) -> str:
        """Convert text to LaTeX format"""
        # Basic LaTeX conversion for common mathematical expressions
        latex_text = text
        
        # Convert common symbols
        replacements = {
            '^': '^',
            '**': '^',
            'sqrt': '\\sqrt',
            'pi': '\\pi',
            'alpha': '\\alpha',
            'beta': '\\beta',
            'gamma': '\\gamma',
            'delta': '\\delta',
            'theta': '\\theta',
            'lambda': '\\lambda',
            'mu': '\\mu',
            'sigma': '\\sigma',
            'phi': '\\phi',
            'omega': '\\omega',
            'infinity': '\\infty',
            'sum': '\\sum',
            'integral': '\\int',
            'partial': '\\partial',
            'nabla': '\\nabla',
            'times': '\\times',
            'div': '\\div',
            'pm': '\\pm',
            'mp': '\\mp',
            'leq': '\\leq',
            'geq': '\\geq',
            'neq': '\\neq',
            'approx': '\\approx',
            'equiv': '\\equiv',
            'propto': '\\propto',
            'in': '\\in',
            'notin': '\\notin',
            'subset': '\\subset',
            'supset': '\\supset',
            'cup': '\\cup',
            'cap': '\\cap',
            'emptyset': '\\emptyset',
            'rightarrow': '\\rightarrow',
            'leftarrow': '\\leftarrow',
            'leftrightarrow': '\\leftrightarrow',
            'Rightarrow': '\\Rightarrow',
            'Leftarrow': '\\Leftarrow',
            'Leftrightarrow': '\\Leftrightarrow'
        }
        
        for text_symbol, latex_symbol in replacements.items():
            latex_text = latex_text.replace(text_symbol, latex_symbol)
        
        return latex_text
    
    def _convert_solution_to_latex(self, solution_data: Dict[str, Any]) -> str:
        """Convert solution data to LaTeX format"""
        answer = solution_data.get('answer', '')
        
        # If answer is already in LaTeX format, return it
        if '\\' in str(answer):
            return str(answer)
        
        # Otherwise, convert to LaTeX
        return self._convert_to_latex(str(answer))
    
    def _generate_request_id(self) -> str:
        """Generate unique request ID"""
        import uuid
        return str(uuid.uuid4())
    
    def format_health_response(self, 
                             solvers_status: Dict[str, bool],
                             services_status: Dict[str, bool]) -> Dict[str, Any]:
        """Format health check response"""
        return {
            'status': 'healthy',
            'timestamp': datetime.utcnow().isoformat(),
            'services': {
                'solvers': solvers_status,
                'external_services': services_status
            },
            'version': '1.0.0'
        }
    
    def format_solvers_response(self, 
                              solvers_info: Dict[str, Dict[str, Any]]) -> Dict[str, Any]:
        """Format solvers information response"""
        return {
            'available_solvers': list(solvers_info.keys()),
            'capabilities': {
                name: info.get('capabilities', [])
                for name, info in solvers_info.items()
            },
            'solver_details': solvers_info
        }
    
    def validate_response_format(self, response: Dict[str, Any]) -> Dict[str, Any]:
        """Validate response format and return validation result"""
        required_fields = ['success', 'timestamp', 'request_id']
        
        missing_fields = []
        for field in required_fields:
            if field not in response:
                missing_fields.append(field)
        
        if missing_fields:
            return {
                'valid': False,
                'missing_fields': missing_fields,
                'error': f'Missing required fields: {", ".join(missing_fields)}'
            }
        
        # Check success field type
        if not isinstance(response['success'], bool):
            return {
                'valid': False,
                'error': 'success field must be boolean'
            }
        
        # Check timestamp format
        try:
            datetime.fromisoformat(response['timestamp'].replace('Z', '+00:00'))
        except:
            return {
                'valid': False,
                'error': 'Invalid timestamp format'
            }
        
        return {
            'valid': True,
            'message': 'Response format is valid'
        }




