"""
Models Package

This package contains Pydantic models for request/response validation:
- Request schemas for API endpoints
- Response schemas for consistent API responses
- Validation models for data integrity
"""

from .schemas import (
    SolveRequest,
    SolveResponse,
    ExplainRequest,
    ExplainResponse,
    LaTeXRequest,
    LaTeXResponse,
    HealthResponse,
    SolversResponse,
    ErrorResponse,
    ClassificationData,
    SolutionData,
    StepData,
    ExplanationData,
    MetadataData
)

__all__ = [
    'SolveRequest',
    'SolveResponse',
    'ExplainRequest',
    'ExplainResponse',
    'LaTeXRequest',
    'LaTeXResponse',
    'HealthResponse',
    'SolversResponse',
    'ErrorResponse',
    'ClassificationData',
    'SolutionData',
    'StepData',
    'ExplanationData',
    'MetadataData'
]




