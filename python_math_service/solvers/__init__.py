"""
Math Solvers Package

This package contains the new modular solver system:
- BaseSolver: Abstract base class for all solvers
- SymPySolver: SymPy-based solver for all math types
- SolverRegistry: Registry for managing solvers
- Easy to add new solvers in the future
"""

from .base_solver import BaseSolver
from .sympy_solver import SymPySolver
from .solver_registry import SolverRegistry

__all__ = [
    'BaseSolver',
    'SymPySolver', 
    'SolverRegistry'
]