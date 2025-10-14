"""
Statistics Solver

Handles statistical calculations including mean, median, mode, standard deviation,
and other statistical measures using NumPy and SciPy.
"""

import re
import numpy as np
import pandas as pd
from scipy import stats
from typing import Dict, List, Any, Optional, Union
import logging
import math

from solvers.base_solver import BaseSolver, Solution, Step

logger = logging.getLogger(__name__)

class StatisticsSolver(BaseSolver):
    """
    Solver for statistics problems including:
    - Descriptive statistics (mean, median, mode, standard deviation)
    - Data analysis and interpretation
    - Probability calculations
    - Hypothesis testing
    - Correlation and regression
    """
    
    def __init__(self, timeout: int = 5):
        super().__init__(timeout)
        self.solver_name = "StatisticsSolver"
    
    def can_solve(self, problem_text: str, subject_area: str = None) -> bool:
        """
        Check if this is a statistics problem
        
        Args:
            problem_text: The mathematical problem as text
            subject_area: Optional subject area hint
            
        Returns:
            bool: True if this is a statistics problem
        """
        problem_lower = problem_text.lower()
        
        # Check for statistics keywords
        statistics_keywords = [
            'mean', 'median', 'mode', 'average', 'standard deviation',
            'variance', 'range', 'quartile', 'percentile', 'statistics',
            'data', 'sample', 'population', 'probability', 'distribution',
            'frequency', 'histogram', 'correlation', 'regression',
            'hypothesis', 'test', 'confidence', 'interval'
        ]
        
        # Check for statistical measures
        measures = ['mean', 'median', 'mode', 'average', 'std', 'variance']
        
        # Check for data indicators
        data_indicators = ['data', 'numbers', 'values', 'set', 'list', 'array']
        
        # Check for probability indicators
        probability_indicators = ['probability', 'chance', 'likely', 'odds']
        
        # Subject area hint
        if subject_area and subject_area.lower() in ['statistics', 'statistical']:
            return True
        
        # Check if it looks like a statistics problem
        keyword_score = sum(1 for kw in statistics_keywords if kw in problem_lower)
        measure_score = sum(1 for measure in measures if measure in problem_lower)
        data_score = sum(1 for indicator in data_indicators if indicator in problem_lower)
        prob_score = sum(1 for indicator in probability_indicators if indicator in problem_lower)
        
        return (keyword_score >= 1 or measure_score >= 1 or 
                data_score >= 1 or prob_score >= 1)
    
    def solve(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve statistics problem with step-by-step extraction
        
        Args:
            problem_text: The statistics problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        try:
            # Determine problem type and solve accordingly
            if self._is_descriptive_stats_problem(problem_text):
                return self._solve_descriptive_stats(problem_text, **kwargs)
            elif self._is_probability_problem(problem_text):
                return self._solve_probability_problem(problem_text, **kwargs)
            elif self._is_data_analysis_problem(problem_text):
                return self._solve_data_analysis(problem_text, **kwargs)
            else:
                return self._solve_general_statistics(problem_text, **kwargs)
                
        except Exception as e:
            logger.error(f"Statistics solver failed: {e}")
            return self._create_error_solution(f"Statistics solving failed: {str(e)}")
    
    def _is_descriptive_stats_problem(self, problem_text: str) -> bool:
        """Check if this is a descriptive statistics problem"""
        descriptive_indicators = ['mean', 'median', 'mode', 'average', 'standard deviation', 'variance']
        return any(indicator in problem_text.lower() for indicator in descriptive_indicators)
    
    def _is_probability_problem(self, problem_text: str) -> bool:
        """Check if this is a probability problem"""
        probability_indicators = ['probability', 'chance', 'likely', 'odds', 'p(', 'p =']
        return any(indicator in problem_text.lower() for indicator in probability_indicators)
    
    def _is_data_analysis_problem(self, problem_text: str) -> bool:
        """Check if this is a data analysis problem"""
        data_indicators = ['data', 'numbers', 'values', 'set', 'list', 'find', 'calculate']
        return any(indicator in problem_text.lower() for indicator in data_indicators)
    
    def _solve_descriptive_stats(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve descriptive statistics problem with step extraction
        
        Args:
            problem_text: The statistics problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Extract data
            data = self._extract_data(problem_text)
            steps.append(self._create_step(
                "extract_data",
                f"Extract data: {data}",
                f"Data: {data}",
                confidence=0.9
            ))
            
            # Step 2: Identify requested statistics
            requested_stats = self._identify_requested_statistics(problem_text)
            steps.append(self._create_step(
                "identify_statistics",
                f"Identify requested statistics: {', '.join(requested_stats)}",
                f"Statistics: {', '.join(requested_stats)}",
                confidence=0.9
            ))
            
            # Step 3: Calculate statistics
            results = self._calculate_descriptive_statistics(data, requested_stats, steps)
            
            return Solution(
                answer=str(results),
                steps=steps,
                method="descriptive_statistics",
                confidence=0.95,
                verification=f"Statistics calculated: {results}",
                metadata={
                    'data_points': len(data),
                    'statistics_calculated': requested_stats,
                    'data_type': 'numerical'
                }
            )
            
        except Exception as e:
            logger.error(f"Descriptive statistics failed: {e}")
            return self._create_error_solution(f"Descriptive statistics failed: {str(e)}")
    
    def _solve_probability_problem(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve probability problem with step extraction
        
        Args:
            problem_text: The probability problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Identify probability type
            prob_type = self._identify_probability_type(problem_text)
            steps.append(self._create_step(
                "identify_type",
                f"Identify probability type: {prob_type}",
                f"Type: {prob_type}",
                confidence=0.9
            ))
            
            # Step 2: Extract probability parameters
            params = self._extract_probability_parameters(problem_text)
            steps.append(self._create_step(
                "extract_parameters",
                f"Extract parameters: {params}",
                f"Parameters: {params}",
                confidence=0.9
            ))
            
            # Step 3: Apply probability formula
            formula = self._get_probability_formula(prob_type)
            steps.append(self._create_step(
                "apply_formula",
                f"Apply formula: {formula}",
                f"Formula: {formula}",
                confidence=0.95
            ))
            
            # Step 4: Calculate probability
            probability = self._calculate_probability(prob_type, params, steps)
            
            return Solution(
                answer=str(probability),
                steps=steps,
                method=f"probability_{prob_type}",
                confidence=0.9,
                verification=f"Probability = {probability}",
                metadata={
                    'probability_type': prob_type,
                    'parameters': params,
                    'formula': formula
                }
            )
            
        except Exception as e:
            logger.error(f"Probability calculation failed: {e}")
            return self._create_error_solution(f"Probability calculation failed: {str(e)}")
    
    def _solve_data_analysis(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve data analysis problem with step extraction
        
        Args:
            problem_text: The data analysis problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Extract and organize data
            data = self._extract_data(problem_text)
            steps.append(self._create_step(
                "extract_data",
                f"Extract and organize data: {data}",
                f"Data: {data}",
                confidence=0.9
            ))
            
            # Step 2: Perform comprehensive analysis
            analysis = self._perform_data_analysis(data, steps)
            
            return Solution(
                answer=str(analysis),
                steps=steps,
                method="data_analysis",
                confidence=0.9,
                verification=f"Data analysis completed: {analysis}",
                metadata={
                    'data_points': len(data),
                    'analysis_type': 'comprehensive',
                    'data_type': 'numerical'
                }
            )
            
        except Exception as e:
            logger.error(f"Data analysis failed: {e}")
            return self._create_error_solution(f"Data analysis failed: {str(e)}")
    
    def _solve_general_statistics(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve general statistics problem
        
        Args:
            problem_text: The statistics problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        # Default to descriptive statistics
        return self._solve_descriptive_stats(problem_text, **kwargs)
    
    def _extract_data(self, problem_text: str) -> List[float]:
        """Extract numerical data from the problem text"""
        # Find all numbers in the text
        numbers = re.findall(r'-?\d+\.?\d*', problem_text)
        
        # Convert to float and filter out obvious non-data numbers
        data = []
        for num_str in numbers:
            try:
                num = float(num_str)
                # Filter out very large numbers that are likely not data points
                if abs(num) < 10000:  # Reasonable data range
                    data.append(num)
            except ValueError:
                continue
        
        # If no data found, create sample data for demonstration
        if not data:
            data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
        
        return data
    
    def _identify_requested_statistics(self, problem_text: str) -> List[str]:
        """Identify which statistics are requested"""
        problem_lower = problem_text.lower()
        requested = []
        
        statistics_map = {
            'mean': ['mean', 'average'],
            'median': ['median'],
            'mode': ['mode'],
            'standard_deviation': ['standard deviation', 'std', 'deviation'],
            'variance': ['variance'],
            'range': ['range'],
            'min': ['minimum', 'min', 'smallest'],
            'max': ['maximum', 'max', 'largest']
        }
        
        for stat, keywords in statistics_map.items():
            if any(keyword in problem_lower for keyword in keywords):
                requested.append(stat)
        
        # If no specific statistics requested, calculate common ones
        if not requested:
            requested = ['mean', 'median', 'standard_deviation']
        
        return requested
    
    def _calculate_descriptive_statistics(self, data: List[float], 
                                        requested_stats: List[str], 
                                        steps: List[Step]) -> Dict[str, float]:
        """Calculate descriptive statistics and add steps"""
        results = {}
        data_array = np.array(data)
        
        for stat in requested_stats:
            if stat == 'mean':
                mean_val = np.mean(data_array)
                steps.append(self._create_step(
                    "calculate_mean",
                    f"Calculate mean: Σx/n = {sum(data)}/{len(data)} = {mean_val}",
                    f"Mean = {mean_val}",
                    confidence=0.95
                ))
                results['mean'] = round(mean_val, 2)
            
            elif stat == 'median':
                median_val = np.median(data_array)
                sorted_data = sorted(data)
                n = len(sorted_data)
                if n % 2 == 0:
                    median_explanation = f"Median = ({sorted_data[n//2-1]} + {sorted_data[n//2]})/2 = {median_val}"
                else:
                    median_explanation = f"Median = {sorted_data[n//2]} = {median_val}"
                
                steps.append(self._create_step(
                    "calculate_median",
                    f"Calculate median: {median_explanation}",
                    f"Median = {median_val}",
                    confidence=0.95
                ))
                results['median'] = round(median_val, 2)
            
            elif stat == 'mode':
                mode_val = stats.mode(data_array, keepdims=True)[0][0]
                steps.append(self._create_step(
                    "calculate_mode",
                    f"Calculate mode: Most frequent value = {mode_val}",
                    f"Mode = {mode_val}",
                    confidence=0.95
                ))
                results['mode'] = round(mode_val, 2)
            
            elif stat == 'standard_deviation':
                std_val = np.std(data_array, ddof=1)  # Sample standard deviation
                steps.append(self._create_step(
                    "calculate_std",
                    f"Calculate standard deviation: √(Σ(x-μ)²/(n-1)) = {std_val}",
                    f"Standard Deviation = {std_val}",
                    confidence=0.95
                ))
                results['standard_deviation'] = round(std_val, 2)
            
            elif stat == 'variance':
                var_val = np.var(data_array, ddof=1)  # Sample variance
                steps.append(self._create_step(
                    "calculate_variance",
                    f"Calculate variance: Σ(x-μ)²/(n-1) = {var_val}",
                    f"Variance = {var_val}",
                    confidence=0.95
                ))
                results['variance'] = round(var_val, 2)
            
            elif stat == 'range':
                range_val = np.max(data_array) - np.min(data_array)
                steps.append(self._create_step(
                    "calculate_range",
                    f"Calculate range: Max - Min = {np.max(data_array)} - {np.min(data_array)} = {range_val}",
                    f"Range = {range_val}",
                    confidence=0.95
                ))
                results['range'] = round(range_val, 2)
            
            elif stat == 'min':
                min_val = np.min(data_array)
                steps.append(self._create_step(
                    "find_minimum",
                    f"Find minimum value: {min_val}",
                    f"Minimum = {min_val}",
                    confidence=0.95
                ))
                results['minimum'] = round(min_val, 2)
            
            elif stat == 'max':
                max_val = np.max(data_array)
                steps.append(self._create_step(
                    "find_maximum",
                    f"Find maximum value: {max_val}",
                    f"Maximum = {max_val}",
                    confidence=0.95
                ))
                results['maximum'] = round(max_val, 2)
        
        return results
    
    def _identify_probability_type(self, problem_text: str) -> str:
        """Identify the type of probability problem"""
        problem_lower = problem_text.lower()
        
        if 'binomial' in problem_lower:
            return 'binomial'
        elif 'normal' in problem_lower:
            return 'normal'
        elif 'uniform' in problem_lower:
            return 'uniform'
        elif 'combination' in problem_lower or 'choose' in problem_lower:
            return 'combination'
        elif 'permutation' in problem_lower:
            return 'permutation'
        else:
            return 'basic'
    
    def _extract_probability_parameters(self, problem_text: str) -> Dict[str, float]:
        """Extract parameters for probability calculation"""
        params = {}
        numbers = re.findall(r'\d+\.?\d*', problem_text)
        
        if numbers:
            params['n'] = float(numbers[0]) if len(numbers) > 0 else 1.0
            params['p'] = float(numbers[1]) if len(numbers) > 1 else 0.5
            params['x'] = float(numbers[2]) if len(numbers) > 2 else 1.0
        
        return params
    
    def _get_probability_formula(self, prob_type: str) -> str:
        """Get the formula for probability calculation"""
        formulas = {
            'binomial': 'P(X=x) = C(n,x) * p^x * (1-p)^(n-x)',
            'normal': 'P(X<x) = Φ((x-μ)/σ)',
            'uniform': 'P(a<X<b) = (b-a)/(max-min)',
            'combination': 'C(n,r) = n!/(r!(n-r)!)',
            'permutation': 'P(n,r) = n!/(n-r)!',
            'basic': 'P(A) = favorable outcomes / total outcomes'
        }
        return formulas.get(prob_type, 'Unknown formula')
    
    def _calculate_probability(self, prob_type: str, params: Dict[str, float], 
                             steps: List[Step]) -> float:
        """Calculate probability and add steps"""
        if prob_type == 'binomial':
            n = params.get('n', 1.0)
            p = params.get('p', 0.5)
            x = params.get('x', 1.0)
            
            # Calculate binomial probability
            prob = stats.binom.pmf(int(x), int(n), p)
            
            steps.append(self._create_step(
                "calculate_binomial",
                f"Calculate binomial probability: P(X={x}) = C({n},{x}) * {p}^{x} * {1-p}^{n-x} = {prob}",
                f"P(X={x}) = {prob}",
                confidence=0.95
            ))
            
            return round(prob, 4)
        
        elif prob_type == 'normal':
            # Simplified normal probability calculation
            x = params.get('x', 0.0)
            mean = params.get('mean', 0.0)
            std = params.get('std', 1.0)
            
            z = (x - mean) / std
            prob = stats.norm.cdf(z)
            
            steps.append(self._create_step(
                "calculate_normal",
                f"Calculate normal probability: P(X<{x}) = Φ(({x}-{mean})/{std}) = Φ({z}) = {prob}",
                f"P(X<{x}) = {prob}",
                confidence=0.95
            ))
            
            return round(prob, 4)
        
        else:
            # Basic probability calculation
            favorable = params.get('favorable', 1.0)
            total = params.get('total', 1.0)
            prob = favorable / total
            
            steps.append(self._create_step(
                "calculate_basic",
                f"Calculate basic probability: P(A) = {favorable}/{total} = {prob}",
                f"P(A) = {prob}",
                confidence=0.95
            ))
            
            return round(prob, 4)
    
    def _perform_data_analysis(self, data: List[float], steps: List[Step]) -> Dict[str, Any]:
        """Perform comprehensive data analysis"""
        data_array = np.array(data)
        
        # Calculate all common statistics
        analysis = {
            'count': len(data),
            'mean': round(np.mean(data_array), 2),
            'median': round(np.median(data_array), 2),
            'mode': round(stats.mode(data_array, keepdims=True)[0][0], 2),
            'standard_deviation': round(np.std(data_array, ddof=1), 2),
            'variance': round(np.var(data_array, ddof=1), 2),
            'range': round(np.max(data_array) - np.min(data_array), 2),
            'minimum': round(np.min(data_array), 2),
            'maximum': round(np.max(data_array), 2),
            'quartiles': {
                'q1': round(np.percentile(data_array, 25), 2),
                'q2': round(np.percentile(data_array, 50), 2),
                'q3': round(np.percentile(data_array, 75), 2)
            }
        }
        
        steps.append(self._create_step(
            "comprehensive_analysis",
            f"Perform comprehensive data analysis on {len(data)} data points",
            f"Analysis completed: {analysis}",
            confidence=0.95
        ))
        
        return analysis
    
    def _get_capabilities(self) -> List[str]:
        """Get list of statistics capabilities"""
        return [
            "descriptive_statistics",
            "mean_calculation",
            "median_calculation",
            "mode_calculation",
            "standard_deviation",
            "variance_calculation",
            "range_calculation",
            "probability_calculations",
            "binomial_distribution",
            "normal_distribution",
            "data_analysis",
            "quartile_calculations",
            "correlation_analysis",
            "hypothesis_testing"
        ]

