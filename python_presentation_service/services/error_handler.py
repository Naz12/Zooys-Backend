"""
Centralized error handling for presentation microservice
"""

import logging
import traceback
from typing import Dict, Any, Optional
from fastapi import HTTPException
from openai import OpenAIError, RateLimitError, APITimeoutError, AuthenticationError

from models.errors import ErrorCode, get_error_response

logger = logging.getLogger(__name__)

class MicroserviceError(Exception):
    """Base exception for microservice errors"""
    
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
        super().__init__(self.message)

def handle_openai_error(error: Exception) -> Dict[str, Any]:
    """Handle OpenAI API errors and convert to standardized format"""
    
    if isinstance(error, RateLimitError):
        logger.warning(f"OpenAI rate limit error: {error}")
        return get_error_response(
            ErrorCode.OPENAI_RATE_LIMIT,
            "OpenAI API rate limit exceeded. Please try again later."
        )
    
    elif isinstance(error, APITimeoutError):
        logger.warning(f"OpenAI timeout error: {error}")
        return get_error_response(
            ErrorCode.OPENAI_TIMEOUT,
            "OpenAI API request timed out. Please try again."
        )
    
    elif isinstance(error, AuthenticationError):
        logger.error(f"OpenAI authentication error: {error}")
        return get_error_response(
            ErrorCode.OPENAI_INVALID_KEY,
            "Invalid OpenAI API key. Please check your configuration."
        )
    
    elif isinstance(error, OpenAIError):
        logger.error(f"OpenAI API error: {error}")
        return get_error_response(
            ErrorCode.OPENAI_API_ERROR,
            f"OpenAI API error: {str(error)}"
        )
    
    else:
        logger.error(f"Unexpected OpenAI error: {error}")
        return get_error_response(
            ErrorCode.OPENAI_API_ERROR,
            "An unexpected error occurred with OpenAI API"
        )

def handle_validation_error(error: Exception, field: Optional[str] = None) -> Dict[str, Any]:
    """Handle validation errors"""
    
    message = f"Validation error: {str(error)}"
    if field:
        message = f"Validation error for field '{field}': {str(error)}"
    
    logger.warning(f"Validation error: {error}")
    return get_error_response(
        ErrorCode.VALIDATION_ERROR,
        message
    )

def handle_generation_error(error: Exception, operation: str = "generation") -> Dict[str, Any]:
    """Handle content generation errors"""
    
    logger.error(f"{operation} error: {error}")
    return get_error_response(
        ErrorCode.GENERATION_ERROR,
        f"{operation.title()} failed: {str(error)}"
    )

def handle_export_error(error: Exception) -> Dict[str, Any]:
    """Handle PowerPoint export errors"""
    
    logger.error(f"Export error: {error}")
    return get_error_response(
        ErrorCode.EXPORT_ERROR,
        f"PowerPoint export failed: {str(error)}"
    )

def handle_internal_error(error: Exception) -> Dict[str, Any]:
    """Handle internal server errors"""
    
    logger.error(f"Internal error: {error}")
    logger.error(f"Traceback: {traceback.format_exc()}")
    
    return get_error_response(
        ErrorCode.INTERNAL_ERROR,
        f"An internal server error occurred: {str(error)}"
    )

def create_error_response(
    success: bool = False,
    error: Optional[Dict[str, Any]] = None,
    **kwargs
) -> Dict[str, Any]:
    """Create standardized error response"""
    
    response = {
        "success": success,
        "timestamp": None  # Will be set by the endpoint
    }
    
    if error:
        response["error"] = error
    
    # Add any additional fields
    response.update(kwargs)
    
    return response

def create_success_response(
    data: Any = None,
    metadata: Optional[Dict[str, Any]] = None,
    **kwargs
) -> Dict[str, Any]:
    """Create standardized success response"""
    
    response = {
        "success": True,
        "timestamp": None  # Will be set by the endpoint
    }
    
    if data is not None:
        response["data"] = data
    
    if metadata:
        response["metadata"] = metadata
    
    # Add any additional fields
    response.update(kwargs)
    
    return response

def log_error_context(
    operation: str,
    error: Exception,
    context: Optional[Dict[str, Any]] = None
) -> None:
    """Log error with context for debugging"""
    
    log_data = {
        "operation": operation,
        "error_type": type(error).__name__,
        "error_message": str(error)
    }
    
    if context:
        log_data.update(context)
    
    logger.error(f"Error in {operation}: {log_data}")
    logger.error(f"Traceback: {traceback.format_exc()}")
