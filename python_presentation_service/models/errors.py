"""
Error code definitions for presentation microservice
"""

from enum import Enum
from typing import Dict, Any, Optional

class ErrorCode(Enum):
    """Standardized error codes for the presentation microservice"""
    
    # OpenAI API related errors
    OPENAI_API_ERROR = "OPENAI_API_ERROR"
    OPENAI_RATE_LIMIT = "OPENAI_RATE_LIMIT"
    OPENAI_TIMEOUT = "OPENAI_TIMEOUT"
    OPENAI_INVALID_KEY = "OPENAI_INVALID_KEY"
    
    # Validation errors
    VALIDATION_ERROR = "VALIDATION_ERROR"
    INVALID_INPUT = "INVALID_INPUT"
    MISSING_REQUIRED_FIELD = "MISSING_REQUIRED_FIELD"
    
    # Generation errors
    GENERATION_ERROR = "GENERATION_ERROR"
    OUTLINE_GENERATION_FAILED = "OUTLINE_GENERATION_FAILED"
    CONTENT_GENERATION_FAILED = "CONTENT_GENERATION_FAILED"
    
    # Export errors
    EXPORT_ERROR = "EXPORT_ERROR"
    POWERPOINT_CREATION_FAILED = "POWERPOINT_CREATION_FAILED"
    FILE_SAVE_FAILED = "FILE_SAVE_FAILED"
    
    # Internal errors
    INTERNAL_ERROR = "INTERNAL_ERROR"
    SERVICE_UNAVAILABLE = "SERVICE_UNAVAILABLE"
    UNKNOWN_ERROR = "UNKNOWN_ERROR"

class ErrorResponse:
    """Standardized error response structure"""
    
    def __init__(
        self,
        code: ErrorCode,
        message: str,
        details: Optional[str] = None,
        retry_after: Optional[int] = None,
        recoverable: bool = True
    ):
        self.code = code
        self.message = message
        self.details = details
        self.retry_after = retry_after
        self.recoverable = recoverable
    
    def to_dict(self) -> Dict[str, Any]:
        """Convert error response to dictionary"""
        error_dict = {
            "code": self.code.value,
            "message": self.message,
            "recoverable": self.recoverable
        }
        
        if self.details:
            error_dict["details"] = self.details
        
        if self.retry_after:
            error_dict["retry_after"] = self.retry_after
        
        return error_dict

# Predefined error responses
ERROR_RESPONSES = {
    ErrorCode.OPENAI_API_ERROR: ErrorResponse(
        ErrorCode.OPENAI_API_ERROR,
        "OpenAI API request failed",
        "Please check your API key and try again",
        recoverable=True
    ),
    ErrorCode.OPENAI_RATE_LIMIT: ErrorResponse(
        ErrorCode.OPENAI_RATE_LIMIT,
        "OpenAI API rate limit exceeded",
        "Please try again in a few minutes",
        retry_after=60,
        recoverable=True
    ),
    ErrorCode.OPENAI_TIMEOUT: ErrorResponse(
        ErrorCode.OPENAI_TIMEOUT,
        "OpenAI API request timed out",
        "The request took too long to complete",
        retry_after=30,
        recoverable=True
    ),
    ErrorCode.VALIDATION_ERROR: ErrorResponse(
        ErrorCode.VALIDATION_ERROR,
        "Invalid input data provided",
        "Please check your request parameters",
        recoverable=False
    ),
    ErrorCode.GENERATION_ERROR: ErrorResponse(
        ErrorCode.GENERATION_ERROR,
        "Content generation failed",
        "Unable to generate the requested content",
        recoverable=True
    ),
    ErrorCode.EXPORT_ERROR: ErrorResponse(
        ErrorCode.EXPORT_ERROR,
        "PowerPoint export failed",
        "Unable to create the presentation file",
        recoverable=True
    ),
    ErrorCode.INTERNAL_ERROR: ErrorResponse(
        ErrorCode.INTERNAL_ERROR,
        "Internal server error",
        "An unexpected error occurred",
        recoverable=True
    )
}

def get_error_response(code: ErrorCode, custom_message: Optional[str] = None) -> Dict[str, Any]:
    """Get standardized error response for a given error code"""
    if code in ERROR_RESPONSES:
        error_response = ERROR_RESPONSES[code]
        if custom_message:
            error_response.message = custom_message
        return error_response.to_dict()
    else:
        return {
            "code": code.value,
            "message": custom_message or "An error occurred",
            "recoverable": True
        }
