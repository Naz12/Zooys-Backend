from abc import ABC, abstractmethod
from typing import Dict, Any, List, Optional
import logging

logger = logging.getLogger(__name__)

class BaseSolver(ABC):
    """Base class for all math solvers"""
    
    def __init__(self):
        self.name = self.__class__.__name__
        self.library = "Unknown"
    
    @abstractmethod
    def can_solve(self, problem_text: str) -> bool:
        """Check if this solver can handle the given problem"""
        pass
    
    @abstractmethod
    def solve(self, problem_text: str, subject_area: str = "maths", difficulty_level: str = "intermediate") -> Dict[str, Any]:
        """Solve the math problem and return structured result"""
        pass
    
    @abstractmethod
    def get_supported_types(self) -> List[str]:
        """Return list of problem types this solver supports"""
        pass
    
    def get_info(self) -> Dict[str, Any]:
        """Get solver information"""
        return {
            "name": self.name,
            "library": self.library,
            "supported_types": self.get_supported_types()
        }