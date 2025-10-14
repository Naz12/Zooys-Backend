"""
Geometry Solver

Handles geometry problems including areas, volumes, angles, and shape calculations.
Uses NumPy for numerical calculations and SymPy for symbolic geometry.
"""

import re
import numpy as np
import sympy as sp
from sympy import symbols, pi, sqrt, sin, cos, tan, asin, acos, atan
from typing import Dict, List, Any, Optional, Union, Tuple
import logging
import math

from solvers.base_solver import BaseSolver, Solution, Step

logger = logging.getLogger(__name__)

class GeometrySolver(BaseSolver):
    """
    Solver for geometry problems including:
    - Area calculations (circles, triangles, rectangles, etc.)
    - Volume calculations (spheres, cylinders, cones, etc.)
    - Angle calculations and trigonometric problems
    - Distance and coordinate geometry
    - Pythagorean theorem applications
    """
    
    def __init__(self, timeout: int = 5):
        super().__init__(timeout)
        self.solver_name = "GeometrySolver"
    
    def can_solve(self, problem_text: str, subject_area: str = None) -> bool:
        """
        Check if this is a geometry problem
        
        Args:
            problem_text: The mathematical problem as text
            subject_area: Optional subject area hint
            
        Returns:
            bool: True if this is a geometry problem
        """
        problem_lower = problem_text.lower()
        
        # Check for geometry keywords
        geometry_keywords = [
            'area', 'volume', 'perimeter', 'circumference', 'angle',
            'triangle', 'circle', 'rectangle', 'square', 'sphere',
            'cylinder', 'cone', 'pyramid', 'radius', 'diameter',
            'height', 'width', 'length', 'base', 'hypotenuse',
            'pythagorean', 'geometry', 'shape', 'polygon'
        ]
        
        # Check for geometric shapes
        shapes = [
            'triangle', 'circle', 'rectangle', 'square', 'sphere',
            'cylinder', 'cone', 'pyramid', 'cube', 'parallelogram',
            'trapezoid', 'rhombus', 'pentagon', 'hexagon', 'octagon'
        ]
        
        # Check for geometric measurements
        measurements = ['area', 'volume', 'perimeter', 'circumference', 'angle']
        
        # Check for geometric formulas
        formulas = ['π', 'pi', 'radius', 'diameter', 'height', 'base']
        
        # Subject area hint
        if subject_area and subject_area.lower() in ['geometry', 'geometric']:
            return True
        
        # Check if it looks like a geometry problem
        keyword_score = sum(1 for kw in geometry_keywords if kw in problem_lower)
        shape_score = sum(1 for shape in shapes if shape in problem_lower)
        measurement_score = sum(1 for measure in measurements if measure in problem_lower)
        formula_score = sum(1 for formula in formulas if formula in problem_lower)
        
        return (keyword_score >= 2 or shape_score >= 1 or 
                measurement_score >= 1 or formula_score >= 1)
    
    def solve(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve geometry problem with step-by-step extraction
        
        Args:
            problem_text: The geometry problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        try:
            # Determine problem type and solve accordingly
            if self._is_area_problem(problem_text):
                return self._solve_area_problem(problem_text, **kwargs)
            elif self._is_volume_problem(problem_text):
                return self._solve_volume_problem(problem_text, **kwargs)
            elif self._is_angle_problem(problem_text):
                return self._solve_angle_problem(problem_text, **kwargs)
            elif self._is_pythagorean_problem(problem_text):
                return self._solve_pythagorean_problem(problem_text, **kwargs)
            elif self._is_perimeter_problem(problem_text):
                return self._solve_perimeter_problem(problem_text, **kwargs)
            else:
                return self._solve_general_geometry(problem_text, **kwargs)
                
        except Exception as e:
            logger.error(f"Geometry solver failed: {e}")
            return self._create_error_solution(f"Geometry solving failed: {str(e)}")
    
    def _is_area_problem(self, problem_text: str) -> bool:
        """Check if this is an area calculation problem"""
        area_indicators = ['area', 'square units', 'cm²', 'm²', 'ft²']
        return any(indicator in problem_text.lower() for indicator in area_indicators)
    
    def _is_volume_problem(self, problem_text: str) -> bool:
        """Check if this is a volume calculation problem"""
        volume_indicators = ['volume', 'cubic units', 'cm³', 'm³', 'ft³']
        return any(indicator in problem_text.lower() for indicator in volume_indicators)
    
    def _is_angle_problem(self, problem_text: str) -> bool:
        """Check if this is an angle calculation problem"""
        angle_indicators = ['angle', 'degree', '°', 'radian', 'sin', 'cos', 'tan']
        return any(indicator in problem_text.lower() for indicator in angle_indicators)
    
    def _is_pythagorean_problem(self, problem_text: str) -> bool:
        """Check if this is a Pythagorean theorem problem"""
        pythagorean_indicators = ['pythagorean', 'hypotenuse', 'right triangle', 'a² + b²']
        return any(indicator in problem_text.lower() for indicator in pythagorean_indicators)
    
    def _is_perimeter_problem(self, problem_text: str) -> bool:
        """Check if this is a perimeter calculation problem"""
        perimeter_indicators = ['perimeter', 'circumference', 'around', 'boundary']
        return any(indicator in problem_text.lower() for indicator in perimeter_indicators)
    
    def _solve_area_problem(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve area calculation problem with step extraction
        
        Args:
            problem_text: The area problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Identify the shape
            shape = self._identify_shape(problem_text)
            steps.append(self._create_step(
                "identify_shape",
                f"Identify the shape: {shape}",
                f"Shape: {shape}",
                confidence=0.9
            ))
            
            # Step 2: Extract dimensions
            dimensions = self._extract_dimensions(problem_text, shape)
            steps.append(self._create_step(
                "extract_dimensions",
                f"Extract dimensions: {dimensions}",
                f"Dimensions: {dimensions}",
                confidence=0.9
            ))
            
            # Step 3: Apply area formula
            area_formula = self._get_area_formula(shape)
            steps.append(self._create_step(
                "apply_formula",
                f"Apply area formula: {area_formula}",
                f"Formula: {area_formula}",
                confidence=0.95
            ))
            
            # Step 4: Calculate area
            area = self._calculate_area(shape, dimensions)
            steps.append(self._create_step(
                "calculate",
                f"Calculate area: {area}",
                f"Area = {area}",
                confidence=0.95
            ))
            
            return Solution(
                answer=str(area),
                steps=steps,
                method=f"{shape}_area_calculation",
                confidence=0.95,
                verification=f"Area of {shape} = {area}",
                metadata={
                    'shape': shape,
                    'dimensions': dimensions,
                    'formula': area_formula,
                    'units': self._extract_units(problem_text)
                }
            )
            
        except Exception as e:
            logger.error(f"Area calculation failed: {e}")
            return self._create_error_solution(f"Area calculation failed: {str(e)}")
    
    def _solve_volume_problem(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve volume calculation problem with step extraction
        
        Args:
            problem_text: The volume problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Identify the shape
            shape = self._identify_shape(problem_text)
            steps.append(self._create_step(
                "identify_shape",
                f"Identify the shape: {shape}",
                f"Shape: {shape}",
                confidence=0.9
            ))
            
            # Step 2: Extract dimensions
            dimensions = self._extract_dimensions(problem_text, shape)
            steps.append(self._create_step(
                "extract_dimensions",
                f"Extract dimensions: {dimensions}",
                f"Dimensions: {dimensions}",
                confidence=0.9
            ))
            
            # Step 3: Apply volume formula
            volume_formula = self._get_volume_formula(shape)
            steps.append(self._create_step(
                "apply_formula",
                f"Apply volume formula: {volume_formula}",
                f"Formula: {volume_formula}",
                confidence=0.95
            ))
            
            # Step 4: Calculate volume
            volume = self._calculate_volume(shape, dimensions)
            steps.append(self._create_step(
                "calculate",
                f"Calculate volume: {volume}",
                f"Volume = {volume}",
                confidence=0.95
            ))
            
            return Solution(
                answer=str(volume),
                steps=steps,
                method=f"{shape}_volume_calculation",
                confidence=0.95,
                verification=f"Volume of {shape} = {volume}",
                metadata={
                    'shape': shape,
                    'dimensions': dimensions,
                    'formula': volume_formula,
                    'units': self._extract_units(problem_text)
                }
            )
            
        except Exception as e:
            logger.error(f"Volume calculation failed: {e}")
            return self._create_error_solution(f"Volume calculation failed: {str(e)}")
    
    def _solve_angle_problem(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve angle calculation problem with step extraction
        
        Args:
            problem_text: The angle problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Identify angle type
            angle_type = self._identify_angle_type(problem_text)
            steps.append(self._create_step(
                "identify_angle_type",
                f"Identify angle type: {angle_type}",
                f"Type: {angle_type}",
                confidence=0.9
            ))
            
            # Step 2: Extract given information
            given_info = self._extract_angle_info(problem_text)
            steps.append(self._create_step(
                "extract_info",
                f"Extract given information: {given_info}",
                f"Given: {given_info}",
                confidence=0.9
            ))
            
            # Step 3: Apply trigonometric formula
            formula = self._get_angle_formula(angle_type)
            steps.append(self._create_step(
                "apply_formula",
                f"Apply formula: {formula}",
                f"Formula: {formula}",
                confidence=0.95
            ))
            
            # Step 4: Calculate angle
            angle = self._calculate_angle(angle_type, given_info)
            steps.append(self._create_step(
                "calculate",
                f"Calculate angle: {angle}",
                f"Angle = {angle}",
                confidence=0.95
            ))
            
            return Solution(
                answer=str(angle),
                steps=steps,
                method=f"{angle_type}_angle_calculation",
                confidence=0.9,
                verification=f"Angle = {angle}",
                metadata={
                    'angle_type': angle_type,
                    'given_info': given_info,
                    'formula': formula
                }
            )
            
        except Exception as e:
            logger.error(f"Angle calculation failed: {e}")
            return self._create_error_solution(f"Angle calculation failed: {str(e)}")
    
    def _solve_pythagorean_problem(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve Pythagorean theorem problem with step extraction
        
        Args:
            problem_text: The Pythagorean problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Identify the theorem
            steps.append(self._create_step(
                "identify_theorem",
                "Identify Pythagorean theorem: a² + b² = c²",
                "a² + b² = c²",
                confidence=0.95
            ))
            
            # Step 2: Extract sides
            sides = self._extract_pythagorean_sides(problem_text)
            steps.append(self._create_step(
                "extract_sides",
                f"Extract sides: {sides}",
                f"Sides: {sides}",
                confidence=0.9
            ))
            
            # Step 3: Apply theorem
            result = self._apply_pythagorean_theorem(sides, steps)
            
            return Solution(
                answer=str(result),
                steps=steps,
                method="pythagorean_theorem",
                confidence=0.95,
                verification=f"Pythagorean theorem result: {result}",
                metadata={
                    'sides': sides,
                    'theorem': 'a² + b² = c²'
                }
            )
            
        except Exception as e:
            logger.error(f"Pythagorean theorem failed: {e}")
            return self._create_error_solution(f"Pythagorean theorem failed: {str(e)}")
    
    def _solve_perimeter_problem(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve perimeter calculation problem with step extraction
        
        Args:
            problem_text: The perimeter problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        steps = []
        
        try:
            # Step 1: Identify the shape
            shape = self._identify_shape(problem_text)
            steps.append(self._create_step(
                "identify_shape",
                f"Identify the shape: {shape}",
                f"Shape: {shape}",
                confidence=0.9
            ))
            
            # Step 2: Extract dimensions
            dimensions = self._extract_dimensions(problem_text, shape)
            steps.append(self._create_step(
                "extract_dimensions",
                f"Extract dimensions: {dimensions}",
                f"Dimensions: {dimensions}",
                confidence=0.9
            ))
            
            # Step 3: Apply perimeter formula
            perimeter_formula = self._get_perimeter_formula(shape)
            steps.append(self._create_step(
                "apply_formula",
                f"Apply perimeter formula: {perimeter_formula}",
                f"Formula: {perimeter_formula}",
                confidence=0.95
            ))
            
            # Step 4: Calculate perimeter
            perimeter = self._calculate_perimeter(shape, dimensions)
            steps.append(self._create_step(
                "calculate",
                f"Calculate perimeter: {perimeter}",
                f"Perimeter = {perimeter}",
                confidence=0.95
            ))
            
            return Solution(
                answer=str(perimeter),
                steps=steps,
                method=f"{shape}_perimeter_calculation",
                confidence=0.95,
                verification=f"Perimeter of {shape} = {perimeter}",
                metadata={
                    'shape': shape,
                    'dimensions': dimensions,
                    'formula': perimeter_formula,
                    'units': self._extract_units(problem_text)
                }
            )
            
        except Exception as e:
            logger.error(f"Perimeter calculation failed: {e}")
            return self._create_error_solution(f"Perimeter calculation failed: {str(e)}")
    
    def _solve_general_geometry(self, problem_text: str, **kwargs) -> Solution:
        """
        Solve general geometry problem
        
        Args:
            problem_text: The geometry problem as text
            **kwargs: Additional parameters
            
        Returns:
            Solution: Complete solution with steps
        """
        # Try to identify and solve the most likely geometry problem
        if 'triangle' in problem_text.lower():
            return self._solve_triangle_problem(problem_text, **kwargs)
        elif 'circle' in problem_text.lower():
            return self._solve_circle_problem(problem_text, **kwargs)
        else:
            return self._create_error_solution("Unable to identify geometry problem type")
    
    def _identify_shape(self, problem_text: str) -> str:
        """Identify the geometric shape from the problem text"""
        problem_lower = problem_text.lower()
        
        shapes = {
            'circle': ['circle', 'circular'],
            'triangle': ['triangle', 'triangular'],
            'rectangle': ['rectangle', 'rectangular'],
            'square': ['square'],
            'sphere': ['sphere', 'spherical'],
            'cylinder': ['cylinder', 'cylindrical'],
            'cone': ['cone', 'conical'],
            'cube': ['cube', 'cubic'],
            'parallelogram': ['parallelogram'],
            'trapezoid': ['trapezoid', 'trapezoidal']
        }
        
        for shape, keywords in shapes.items():
            if any(keyword in problem_lower for keyword in keywords):
                return shape
        
        return 'unknown'
    
    def _extract_dimensions(self, problem_text: str, shape: str) -> Dict[str, float]:
        """Extract dimensions from the problem text"""
        dimensions = {}
        
        # Extract numbers from the problem
        numbers = re.findall(r'\d+\.?\d*', problem_text)
        
        if shape == 'circle':
            if 'radius' in problem_text.lower():
                dimensions['radius'] = float(numbers[0]) if numbers else 1.0
            elif 'diameter' in problem_text.lower():
                dimensions['diameter'] = float(numbers[0]) if numbers else 1.0
                dimensions['radius'] = dimensions['diameter'] / 2
        elif shape == 'triangle':
            if len(numbers) >= 2:
                dimensions['base'] = float(numbers[0])
                dimensions['height'] = float(numbers[1])
        elif shape == 'rectangle':
            if len(numbers) >= 2:
                dimensions['length'] = float(numbers[0])
                dimensions['width'] = float(numbers[1])
        elif shape == 'sphere':
            if 'radius' in problem_text.lower():
                dimensions['radius'] = float(numbers[0]) if numbers else 1.0
        
        return dimensions
    
    def _get_area_formula(self, shape: str) -> str:
        """Get the area formula for a shape"""
        formulas = {
            'circle': 'A = πr²',
            'triangle': 'A = (1/2)bh',
            'rectangle': 'A = lw',
            'square': 'A = s²',
            'parallelogram': 'A = bh',
            'trapezoid': 'A = (1/2)(b₁ + b₂)h'
        }
        return formulas.get(shape, 'Unknown formula')
    
    def _get_volume_formula(self, shape: str) -> str:
        """Get the volume formula for a shape"""
        formulas = {
            'sphere': 'V = (4/3)πr³',
            'cylinder': 'V = πr²h',
            'cone': 'V = (1/3)πr²h',
            'cube': 'V = s³',
            'rectangular_prism': 'V = lwh'
        }
        return formulas.get(shape, 'Unknown formula')
    
    def _get_perimeter_formula(self, shape: str) -> str:
        """Get the perimeter formula for a shape"""
        formulas = {
            'circle': 'C = 2πr',
            'rectangle': 'P = 2(l + w)',
            'square': 'P = 4s',
            'triangle': 'P = a + b + c'
        }
        return formulas.get(shape, 'Unknown formula')
    
    def _calculate_area(self, shape: str, dimensions: Dict[str, float]) -> float:
        """Calculate area for a given shape and dimensions"""
        if shape == 'circle':
            radius = dimensions.get('radius', 1.0)
            return round(np.pi * radius ** 2, 2)
        elif shape == 'triangle':
            base = dimensions.get('base', 1.0)
            height = dimensions.get('height', 1.0)
            return round(0.5 * base * height, 2)
        elif shape == 'rectangle':
            length = dimensions.get('length', 1.0)
            width = dimensions.get('width', 1.0)
            return round(length * width, 2)
        elif shape == 'square':
            side = dimensions.get('side', 1.0)
            return round(side ** 2, 2)
        else:
            return 0.0
    
    def _calculate_volume(self, shape: str, dimensions: Dict[str, float]) -> float:
        """Calculate volume for a given shape and dimensions"""
        if shape == 'sphere':
            radius = dimensions.get('radius', 1.0)
            return round((4/3) * np.pi * radius ** 3, 2)
        elif shape == 'cylinder':
            radius = dimensions.get('radius', 1.0)
            height = dimensions.get('height', 1.0)
            return round(np.pi * radius ** 2 * height, 2)
        elif shape == 'cone':
            radius = dimensions.get('radius', 1.0)
            height = dimensions.get('height', 1.0)
            return round((1/3) * np.pi * radius ** 2 * height, 2)
        else:
            return 0.0
    
    def _calculate_perimeter(self, shape: str, dimensions: Dict[str, float]) -> float:
        """Calculate perimeter for a given shape and dimensions"""
        if shape == 'circle':
            radius = dimensions.get('radius', 1.0)
            return round(2 * np.pi * radius, 2)
        elif shape == 'rectangle':
            length = dimensions.get('length', 1.0)
            width = dimensions.get('width', 1.0)
            return round(2 * (length + width), 2)
        elif shape == 'square':
            side = dimensions.get('side', 1.0)
            return round(4 * side, 2)
        else:
            return 0.0
    
    def _identify_angle_type(self, problem_text: str) -> str:
        """Identify the type of angle calculation"""
        if 'sin' in problem_text.lower():
            return 'sine'
        elif 'cos' in problem_text.lower():
            return 'cosine'
        elif 'tan' in problem_text.lower():
            return 'tangent'
        else:
            return 'general'
    
    def _extract_angle_info(self, problem_text: str) -> Dict[str, float]:
        """Extract angle-related information"""
        info = {}
        numbers = re.findall(r'\d+\.?\d*', problem_text)
        
        if numbers:
            info['value'] = float(numbers[0])
        
        return info
    
    def _get_angle_formula(self, angle_type: str) -> str:
        """Get the formula for angle calculation"""
        formulas = {
            'sine': 'sin(θ) = opposite/hypotenuse',
            'cosine': 'cos(θ) = adjacent/hypotenuse',
            'tangent': 'tan(θ) = opposite/adjacent'
        }
        return formulas.get(angle_type, 'Unknown formula')
    
    def _calculate_angle(self, angle_type: str, info: Dict[str, float]) -> float:
        """Calculate angle using trigonometric functions"""
        value = info.get('value', 0.0)
        
        if angle_type == 'sine':
            return round(math.degrees(math.asin(value)), 2)
        elif angle_type == 'cosine':
            return round(math.degrees(math.acos(value)), 2)
        elif angle_type == 'tangent':
            return round(math.degrees(math.atan(value)), 2)
        else:
            return value
    
    def _extract_pythagorean_sides(self, problem_text: str) -> Dict[str, float]:
        """Extract sides for Pythagorean theorem"""
        sides = {}
        numbers = re.findall(r'\d+\.?\d*', problem_text)
        
        if len(numbers) >= 2:
            sides['a'] = float(numbers[0])
            sides['b'] = float(numbers[1])
        
        return sides
    
    def _apply_pythagorean_theorem(self, sides: Dict[str, float], steps: List[Step]) -> float:
        """Apply Pythagorean theorem and add steps"""
        a = sides.get('a', 0.0)
        b = sides.get('b', 0.0)
        
        # Calculate c
        c_squared = a**2 + b**2
        steps.append(self._create_step(
            "calculate_squares",
            f"Calculate a² + b² = {a}² + {b}² = {a**2} + {b**2} = {c_squared}",
            f"a² + b² = {c_squared}",
            confidence=0.95
        ))
        
        c = math.sqrt(c_squared)
        steps.append(self._create_step(
            "calculate_square_root",
            f"Calculate c = √{c_squared} = {c}",
            f"c = {c}",
            confidence=0.95
        ))
        
        return round(c, 2)
    
    def _extract_units(self, problem_text: str) -> str:
        """Extract units from the problem text"""
        units = ['cm', 'm', 'ft', 'in', 'km', 'mm']
        for unit in units:
            if unit in problem_text:
                return unit
        return 'units'
    
    def _solve_triangle_problem(self, problem_text: str, **kwargs) -> Solution:
        """Solve triangle-specific problems"""
        # Simplified triangle solver
        return self._solve_area_problem(problem_text, **kwargs)
    
    def _solve_circle_problem(self, problem_text: str, **kwargs) -> Solution:
        """Solve circle-specific problems"""
        # Simplified circle solver
        return self._solve_area_problem(problem_text, **kwargs)
    
    def _get_capabilities(self) -> List[str]:
        """Get list of geometry capabilities"""
        return [
            "area_calculations",
            "volume_calculations",
            "perimeter_calculations",
            "angle_calculations",
            "pythagorean_theorem",
            "trigonometric_calculations",
            "circle_geometry",
            "triangle_geometry",
            "coordinate_geometry",
            "shape_identification"
        ]

