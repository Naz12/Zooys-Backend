# Adding New Math Solvers

This document explains how to add new math solver libraries to the system.

## Current Architecture

The system uses a modular solver architecture:

- **BaseSolver**: Abstract base class that all solvers inherit from
- **SymPySolver**: Current primary solver using SymPy library
- **SolverRegistry**: Manages all solvers and routes problems to the best one
- **ProblemParser**: Classifies problems and coordinates with the registry

## Adding a New Solver

### Step 1: Create the Solver Class

Create a new file in `solvers/` directory, e.g., `solvers/numpy_solver.py`:

```python
import numpy as np
from typing import Dict, Any, List
import logging
from .base_solver import BaseSolver

logger = logging.getLogger(__name__)

class NumPySolver(BaseSolver):
    """NumPy-based solver for numerical computations"""
    
    def __init__(self):
        super().__init__()
        self.library = "NumPy"
        self.supported_types = [
            "linear_algebra", "statistics", "numerical_analysis"
        ]
    
    def can_solve(self, problem_text: str) -> bool:
        """Check if NumPy can handle this problem"""
        # Define criteria for when to use this solver
        numpy_indicators = [
            r'matrix', r'array', r'eigenvalue', r'determinant'
        ]
        return any(re.search(pattern, problem_text.lower()) for pattern in numpy_indicators)
    
    def solve(self, problem_text: str, subject_area: str = "maths", 
              difficulty_level: str = "intermediate") -> Dict[str, Any]:
        """Solve using NumPy"""
        try:
            # Your NumPy implementation here
            result = self._solve_with_numpy(problem_text)
            
            return {
                "success": True,
                "result": str(result),
                "steps": [{"step": 1, "description": "NumPy calculation", "result": str(result)}],
                "solver_used": "NumPy",
                "problem_type": "numerical",
                "method": "NumPy numerical computation"
            }
        except Exception as e:
            logger.error(f"NumPy solver error: {e}")
            return {
                "success": False,
                "error": f"NumPy solver failed: {str(e)}",
                "solver_used": "NumPy",
                "problem_type": "unknown"
            }
    
    def get_supported_types(self) -> List[str]:
        return self.supported_types
    
    def _solve_with_numpy(self, problem_text: str):
        """Your actual NumPy solving logic"""
        # Implementation here
        pass
```

### Step 2: Register the Solver

Update `solvers/solver_registry.py` to register your new solver:

```python
from .numpy_solver import NumPySolver

class SolverRegistry:
    def _register_default_solvers(self):
        """Register default solvers"""
        # Existing SymPy solver
        sympy_solver = SymPySolver()
        self.register_solver("sympy", sympy_solver)
        
        # Add your new solver
        numpy_solver = NumPySolver()
        self.register_solver("numpy", numpy_solver)
        
        logger.info("Default solvers registered: SymPy, NumPy")
```

### Step 3: Update Requirements

Add your library to `requirements.txt`:

```
numpy>=1.21.0
```

### Step 4: Test Your Solver

Create a test to verify your solver works:

```python
from solvers.solver_registry import SolverRegistry

def test_numpy_solver():
    registry = SolverRegistry()
    
    # Test a problem that should use NumPy
    result = registry.solve_problem("Find eigenvalues of matrix [[1,2],[3,4]]")
    
    assert result['success'] == True
    assert result['solver_used'] == 'NumPy'
```

## Solver Priority

The system chooses solvers in this order:

1. **Preferred solver** (if specified and can solve the problem)
2. **First available solver** that can solve the problem
3. **SymPy as fallback** (if no specific solver found)

## Example: Adding SciPy Solver

```python
# solvers/scipy_solver.py
import scipy.optimize
from .base_solver import BaseSolver

class SciPySolver(BaseSolver):
    def __init__(self):
        super().__init__()
        self.library = "SciPy"
        self.supported_types = ["optimization", "integration", "differential_equations"]
    
    def can_solve(self, problem_text: str) -> bool:
        return any(word in problem_text.lower() for word in 
                  ['optimize', 'minimize', 'maximize', 'integrate', 'differential'])
    
    def solve(self, problem_text: str, **kwargs) -> Dict[str, Any]:
        # SciPy implementation
        pass
```

## Benefits of This Architecture

1. **Easy to add new libraries**: Just create a new solver class
2. **Automatic routing**: System picks the best solver for each problem
3. **Fallback support**: SymPy handles problems other solvers can't
4. **Consistent interface**: All solvers return the same format
5. **Extensible**: Can add specialized solvers for specific domains

## Current Solvers

- **SymPy**: Primary solver for symbolic mathematics, algebra, calculus, arithmetic
- **Future**: NumPy (numerical), SciPy (scientific), TensorFlow (ML), etc.

The system is designed to grow with your needs while maintaining simplicity and reliability.
