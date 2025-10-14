"""
Pydantic Schemas

Defines request and response models for the math microservice API.
Provides validation, serialization, and documentation for all endpoints.
"""

from pydantic import BaseModel, Field, validator
from typing import Dict, List, Any, Optional, Union
from datetime import datetime
from enum import Enum

class SubjectArea(str, Enum):
    """Supported mathematical subject areas"""
    ALGEBRA = "algebra"
    CALCULUS = "calculus"
    GEOMETRY = "geometry"
    STATISTICS = "statistics"
    ARITHMETIC = "arithmetic"
    TRIGONOMETRY = "trigonometry"
    MATHS = "maths"

class DifficultyLevel(str, Enum):
    """Supported difficulty levels"""
    BEGINNER = "beginner"
    INTERMEDIATE = "intermediate"
    ADVANCED = "advanced"

class ImageType(str, Enum):
    """Supported image types for processing"""
    PRINTED = "printed"
    HANDWRITTEN = "handwritten"
    AUTO = "auto"

# Base Models
class BaseRequest(BaseModel):
    """Base request model with common fields"""
    subject_area: Optional[SubjectArea] = Field(None, description="Mathematical subject area")
    difficulty_level: Optional[DifficultyLevel] = Field("intermediate", description="Difficulty level")
    timeout_ms: Optional[int] = Field(5000, ge=1000, le=30000, description="Timeout in milliseconds")
    
    class Config:
        use_enum_values = True

class BaseResponse(BaseModel):
    """Base response model with common fields"""
    success: bool = Field(..., description="Whether the request was successful")
    timestamp: datetime = Field(default_factory=datetime.utcnow, description="Response timestamp")
    request_id: str = Field(..., description="Unique request identifier")

# Request Models
class SolveRequest(BaseRequest):
    """Request model for /solve endpoint"""
    problem_text: Optional[str] = Field(None, max_length=1000, description="Mathematical problem as text")
    problem_image: Optional[str] = Field(None, description="Base64 encoded image or image data")
    image_type: Optional[ImageType] = Field("auto", description="Type of image (printed/handwritten/auto)")
    options: Optional[Dict[str, Any]] = Field(default_factory=dict, description="Additional options")
    
    @validator('problem_text', 'problem_image')
    def validate_input(cls, v, values):
        """Ensure at least one input method is provided"""
        if not v and not values.get('problem_text') and not values.get('problem_image'):
            raise ValueError('Either problem_text or problem_image must be provided')
        return v

class ExplainRequest(SolveRequest):
    """Request model for /explain endpoint"""
    include_explanation: bool = Field(True, description="Whether to include AI explanation")
    explanation_style: Optional[str] = Field("educational", description="Style of explanation")

class LaTeXRequest(BaseModel):
    """Request model for /latex endpoint"""
    problem_text: str = Field(..., max_length=1000, description="Mathematical problem as text")
    solution: Optional[str] = Field(None, description="Solution to convert to LaTeX")
    render_solution: bool = Field(True, description="Whether to render solution in LaTeX")

# Response Data Models
class StepData(BaseModel):
    """Model for individual solution step"""
    step_number: int = Field(..., ge=1, description="Step number")
    operation: str = Field(..., description="Operation performed")
    description: str = Field(..., description="Human-readable description")
    expression: str = Field(..., description="Mathematical expression")
    latex: Optional[str] = Field(None, description="LaTeX representation")
    confidence: float = Field(1.0, ge=0.0, le=1.0, description="Confidence in this step")

class ClassificationData(BaseModel):
    """Model for problem classification"""
    subject: str = Field(..., description="Detected subject area")
    confidence: float = Field(..., ge=0.0, le=1.0, description="Classification confidence")
    method: str = Field(..., description="Classification method used")
    fallback_subjects: List[str] = Field(default_factory=list, description="Alternative subjects")

class SolutionData(BaseModel):
    """Model for mathematical solution"""
    answer: str = Field(..., description="Final answer")
    method: str = Field(..., description="Method used to solve")
    confidence: float = Field(..., ge=0.0, le=1.0, description="Solution confidence")
    steps: List[StepData] = Field(..., description="Step-by-step solution")
    verification: str = Field(..., description="Verification information")
    metadata: Dict[str, Any] = Field(default_factory=dict, description="Additional metadata")

class ExplanationData(BaseModel):
    """Model for AI explanation"""
    content: str = Field(..., description="Explanation content")
    method: str = Field(..., description="Explanation method")
    success: bool = Field(..., description="Whether explanation was successful")
    tokens_used: int = Field(0, ge=0, description="OpenAI tokens used")
    error: Optional[str] = Field(None, description="Error message if failed")

class MetadataData(BaseModel):
    """Model for response metadata"""
    processing_time: float = Field(..., ge=0.0, description="Processing time in seconds")
    solver_used: str = Field(..., description="Solver that was used")
    timestamp: datetime = Field(default_factory=datetime.utcnow, description="Processing timestamp")
    additional_data: Dict[str, Any] = Field(default_factory=dict, description="Additional metadata")

# Response Models
class SolveResponse(BaseResponse):
    """Response model for /solve endpoint"""
    classification: ClassificationData = Field(..., description="Problem classification")
    solution: SolutionData = Field(..., description="Mathematical solution")
    metadata: MetadataData = Field(..., description="Response metadata")

class ExplainResponse(BaseResponse):
    """Response model for /explain endpoint"""
    classification: ClassificationData = Field(..., description="Problem classification")
    solution: SolutionData = Field(..., description="Mathematical solution")
    explanation: ExplanationData = Field(..., description="AI explanation")
    metadata: MetadataData = Field(..., description="Response metadata")

class LaTeXResponse(BaseResponse):
    """Response model for /latex endpoint"""
    latex: Dict[str, str] = Field(..., description="LaTeX representations")
    metadata: MetadataData = Field(..., description="Response metadata")

class HealthResponse(BaseResponse):
    """Response model for /health endpoint"""
    status: str = Field(..., description="Service status")
    services: Dict[str, Dict[str, bool]] = Field(..., description="Service statuses")
    version: str = Field(..., description="Service version")

class SolversResponse(BaseResponse):
    """Response model for /solvers endpoint"""
    available_solvers: List[str] = Field(..., description="List of available solvers")
    capabilities: Dict[str, List[str]] = Field(..., description="Solver capabilities")
    solver_details: Dict[str, Dict[str, Any]] = Field(..., description="Detailed solver information")

class ErrorResponse(BaseResponse):
    """Response model for errors"""
    success: bool = Field(False, description="Always false for errors")
    error: Dict[str, str] = Field(..., description="Error information")
    metadata: Optional[MetadataData] = Field(None, description="Error metadata")

# Validation Models
class ImageValidation(BaseModel):
    """Model for image validation"""
    valid: bool = Field(..., description="Whether image is valid")
    error: Optional[str] = Field(None, description="Error message if invalid")
    file_size: Optional[int] = Field(None, description="File size in bytes")
    format: Optional[str] = Field(None, description="Image format")

class ProblemValidation(BaseModel):
    """Model for problem validation"""
    valid: bool = Field(..., description="Whether problem is valid")
    error: Optional[str] = Field(None, description="Error message if invalid")
    message: Optional[str] = Field(None, description="Validation message")

# Configuration Models
class SolverConfig(BaseModel):
    """Model for solver configuration"""
    name: str = Field(..., description="Solver name")
    timeout: int = Field(5, ge=1, le=30, description="Solver timeout in seconds")
    enabled: bool = Field(True, description="Whether solver is enabled")
    capabilities: List[str] = Field(default_factory=list, description="Solver capabilities")

class ServiceConfig(BaseModel):
    """Model for service configuration"""
    openai_enabled: bool = Field(True, description="Whether OpenAI service is enabled")
    tesseract_enabled: bool = Field(True, description="Whether Tesseract OCR is enabled")
    max_file_size: int = Field(10485760, description="Maximum file size in bytes")
    temp_cleanup: bool = Field(True, description="Whether to cleanup temp files")

# Statistics Models
class ProcessingStats(BaseModel):
    """Model for processing statistics"""
    total_requests: int = Field(0, ge=0, description="Total requests processed")
    successful_requests: int = Field(0, ge=0, description="Successful requests")
    failed_requests: int = Field(0, ge=0, description="Failed requests")
    average_processing_time: float = Field(0.0, ge=0.0, description="Average processing time")
    solver_usage: Dict[str, int] = Field(default_factory=dict, description="Solver usage statistics")

class TokenUsage(BaseModel):
    """Model for token usage tracking"""
    total_tokens: int = Field(0, ge=0, description="Total tokens used")
    explanation_tokens: int = Field(0, ge=0, description="Tokens used for explanations")
    vision_tokens: int = Field(0, ge=0, description="Tokens used for vision")
    cost_estimate: float = Field(0.0, ge=0.0, description="Estimated cost in USD")

# Batch Processing Models
class BatchSolveRequest(BaseModel):
    """Model for batch solve requests"""
    problems: List[SolveRequest] = Field(..., min_items=1, max_items=10, description="List of problems to solve")
    parallel: bool = Field(True, description="Whether to process in parallel")

class BatchSolveResponse(BaseResponse):
    """Model for batch solve responses"""
    results: List[Union[SolveResponse, ErrorResponse]] = Field(..., description="Individual results")
    summary: Dict[str, int] = Field(..., description="Summary statistics")

# Webhook Models
class WebhookRequest(BaseModel):
    """Model for webhook requests"""
    url: str = Field(..., description="Webhook URL")
    events: List[str] = Field(..., description="Events to subscribe to")
    secret: Optional[str] = Field(None, description="Webhook secret for verification")

class WebhookEvent(BaseModel):
    """Model for webhook events"""
    event_type: str = Field(..., description="Type of event")
    request_id: str = Field(..., description="Request ID")
    timestamp: datetime = Field(default_factory=datetime.utcnow, description="Event timestamp")
    data: Dict[str, Any] = Field(..., description="Event data")

# Rate Limiting Models
class RateLimitInfo(BaseModel):
    """Model for rate limiting information"""
    limit: int = Field(..., ge=1, description="Rate limit per window")
    remaining: int = Field(..., ge=0, description="Remaining requests")
    reset_time: datetime = Field(..., description="When the limit resets")
    window_size: int = Field(..., ge=1, description="Window size in seconds")

class RateLimitResponse(BaseResponse):
    """Model for rate limit exceeded response"""
    success: bool = Field(False, description="Always false for rate limit")
    error: Dict[str, str] = Field(..., description="Rate limit error")
    rate_limit: RateLimitInfo = Field(..., description="Rate limit information")




