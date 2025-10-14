"""
Progress tracking system for presentation microservice
"""

import time
import logging
from typing import Dict, Any, Optional, List
from enum import Enum

logger = logging.getLogger(__name__)

class ProgressStatus(Enum):
    """Progress status enumeration"""
    PENDING = "pending"
    PROCESSING = "processing"
    COMPLETED = "completed"
    FAILED = "failed"

class ProgressStep:
    """Individual progress step"""
    
    def __init__(
        self,
        name: str,
        description: str,
        percentage: int,
        estimated_duration: Optional[int] = None
    ):
        self.name = name
        self.description = description
        self.percentage = percentage
        self.estimated_duration = estimated_duration
        self.completed = False
        self.started_at = None
        self.completed_at = None

class ProgressTracker:
    """Track progress of long-running operations"""
    
    def __init__(self, operation_id: str, total_steps: int):
        self.operation_id = operation_id
        self.total_steps = total_steps
        self.current_step = 0
        self.status = ProgressStatus.PENDING
        self.steps: List[ProgressStep] = []
        self.started_at = time.time()
        self.completed_at = None
        self.error_message = None
        
        # Initialize default steps
        self._initialize_default_steps()
    
    def _initialize_default_steps(self):
        """Initialize default progress steps"""
        self.steps = [
            ProgressStep("validation", "Validating input data", 5, 2),
            ProgressStep("preparation", "Preparing for generation", 10, 3),
            ProgressStep("generation", "Generating content", 70, 30),
            ProgressStep("processing", "Processing results", 85, 5),
            ProgressStep("finalization", "Finalizing output", 95, 3),
            ProgressStep("completion", "Operation completed", 100, 1)
        ]
    
    def start(self):
        """Start the progress tracking"""
        self.status = ProgressStatus.PROCESSING
        self.started_at = time.time()
        logger.info(f"Progress tracking started for operation {self.operation_id}")
    
    def update_step(self, step_name: str, description: Optional[str] = None):
        """Update to a specific step"""
        for i, step in enumerate(self.steps):
            if step.name == step_name:
                # Mark previous steps as completed
                for j in range(i):
                    if not self.steps[j].completed:
                        self.steps[j].completed = True
                        self.steps[j].completed_at = time.time()
                
                # Update current step
                self.current_step = i
                step.started_at = time.time()
                if description:
                    step.description = description
                
                logger.info(f"Progress update for {self.operation_id}: {step.description} ({step.percentage}%)")
                break
    
    def complete_step(self, step_name: str):
        """Mark a specific step as completed"""
        for step in self.steps:
            if step.name == step_name:
                step.completed = True
                step.completed_at = time.time()
                logger.info(f"Step completed for {self.operation_id}: {step.name}")
                break
    
    def complete(self):
        """Mark the entire operation as completed"""
        self.status = ProgressStatus.COMPLETED
        self.completed_at = time.time()
        
        # Mark all remaining steps as completed
        for step in self.steps:
            if not step.completed:
                step.completed = True
                step.completed_at = time.time()
        
        logger.info(f"Operation completed for {self.operation_id}")
    
    def fail(self, error_message: str):
        """Mark the operation as failed"""
        self.status = ProgressStatus.FAILED
        self.completed_at = time.time()
        self.error_message = error_message
        logger.error(f"Operation failed for {self.operation_id}: {error_message}")
    
    def get_progress(self) -> Dict[str, Any]:
        """Get current progress information"""
        current_step = self.steps[self.current_step] if self.current_step < len(self.steps) else self.steps[-1]
        
        # Calculate estimated time remaining
        estimated_remaining = 0
        if self.status == ProgressStatus.PROCESSING:
            for i in range(self.current_step, len(self.steps)):
                if not self.steps[i].completed and self.steps[i].estimated_duration:
                    estimated_remaining += self.steps[i].estimated_duration
        
        return {
            "operation_id": self.operation_id,
            "status": self.status.value,
            "percentage": current_step.percentage,
            "current_step": current_step.description,
            "steps_completed": sum(1 for step in self.steps if step.completed),
            "total_steps": len(self.steps),
            "estimated_time_remaining": estimated_remaining,
            "started_at": self.started_at,
            "completed_at": self.completed_at,
            "error_message": self.error_message
        }

# Global progress tracker storage (in production, use Redis or database)
_progress_trackers: Dict[str, ProgressTracker] = {}

def create_progress_tracker(operation_id: str, total_steps: int = 6) -> ProgressTracker:
    """Create a new progress tracker"""
    tracker = ProgressTracker(operation_id, total_steps)
    _progress_trackers[operation_id] = tracker
    return tracker

def get_progress_tracker(operation_id: str) -> Optional[ProgressTracker]:
    """Get an existing progress tracker"""
    return _progress_trackers.get(operation_id)

def update_progress(operation_id: str, step_name: str, description: Optional[str] = None):
    """Update progress for an operation"""
    tracker = get_progress_tracker(operation_id)
    if tracker:
        tracker.update_step(step_name, description)

def complete_progress(operation_id: str):
    """Mark progress as completed"""
    tracker = get_progress_tracker(operation_id)
    if tracker:
        tracker.complete()

def fail_progress(operation_id: str, error_message: str):
    """Mark progress as failed"""
    tracker = get_progress_tracker(operation_id)
    if tracker:
        tracker.fail(error_message)

def get_progress_status(operation_id: str) -> Optional[Dict[str, Any]]:
    """Get progress status for an operation"""
    tracker = get_progress_tracker(operation_id)
    if tracker:
        return tracker.get_progress()
    return None

def cleanup_progress_tracker(operation_id: str):
    """Clean up completed progress tracker"""
    if operation_id in _progress_trackers:
        del _progress_trackers[operation_id]
