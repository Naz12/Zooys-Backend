from typing import Dict, List, Optional, Type, Any
import logging
from .base_solver import BaseSolver
from .sympy_solver import SymPySolver

logger = logging.getLogger(__name__)

class SolverRegistry:
    """Registry for managing math solvers"""
    
    def __init__(self):
        self.solvers: Dict[str, BaseSolver] = {}
        self._register_default_solvers()
    
    def _register_default_solvers(self):
        """Register default solvers"""
        # Register SymPy as the primary solver
        sympy_solver = SymPySolver()
        self.register_solver("sympy", sympy_solver)
        
        logger.info("Default solvers registered: SymPy")
    
    def register_solver(self, name: str, solver: BaseSolver):
        """Register a new solver"""
        self.solvers[name] = solver
        logger.info(f"Registered solver: {name} ({solver.library})")
    
    def get_solver(self, name: str) -> Optional[BaseSolver]:
        """Get a solver by name"""
        return self.solvers.get(name)
    
    def get_available_solvers(self) -> List[str]:
        """Get list of available solver names"""
        return list(self.solvers.keys())
    
    def get_solver_info(self) -> Dict[str, Dict[str, Any]]:
        """Get information about all registered solvers"""
        return {name: solver.get_info() for name, solver in self.solvers.items()}
    
    def find_best_solver(self, problem_text: str, preferred_solver: str = None) -> Optional[BaseSolver]:
        """Find the best solver for a given problem"""
        # If preferred solver is specified and can solve the problem
        if preferred_solver and preferred_solver in self.solvers:
            solver = self.solvers[preferred_solver]
            if solver.can_solve(problem_text):
                logger.info(f"Using preferred solver: {preferred_solver}")
                return solver
        
        # Find the first solver that can handle the problem
        for name, solver in self.solvers.items():
            if solver.can_solve(problem_text):
                logger.info(f"Selected solver: {name} for problem: {problem_text[:50]}...")
                return solver
        
        # Fallback to SymPy if available
        if "sympy" in self.solvers:
            logger.info("No specific solver found, using SymPy as fallback")
            return self.solvers["sympy"]
        
        logger.warning("No suitable solver found")
        return None
    
    def solve_problem(self, problem_text: str, subject_area: str = "maths", 
                     difficulty_level: str = "intermediate", 
                     preferred_solver: str = None) -> Dict[str, Any]:
        """Solve a problem using the best available solver"""
        solver = self.find_best_solver(problem_text, preferred_solver)
        
        if not solver:
            return {
                "success": False,
                "error": "No suitable solver available",
                "solver_used": "none",
                "problem_type": "unknown"
            }
        
        try:
            result = solver.solve(problem_text, subject_area, difficulty_level)
            result["solver_registry"] = {
                "available_solvers": self.get_available_solvers(),
                "selected_solver": solver.name,
                "solver_library": solver.library
            }
            return result
        except Exception as e:
            logger.error(f"Solver execution failed: {e}")
            return {
                "success": False,
                "error": f"Solver execution failed: {str(e)}",
                "solver_used": solver.name,
                "problem_type": "unknown"
            }
