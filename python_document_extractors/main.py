#!/usr/bin/env python3
"""
Document Extraction Microservice
FastAPI service for extracting text from various document types
"""

from fastapi import FastAPI, HTTPException, UploadFile, File, Form
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from typing import Optional, Dict, Any
import os
import sys
import json
import tempfile
import traceback
from pathlib import Path

# Add the current directory to Python path
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

# Import extractors
from pdf_extractor import extract_text_from_pdf
from word_extractor import extract_text_from_word
from txt_extractor import extract_text_from_txt
from ppt_extractor import extract_text_from_ppt
from excel_extractor import extract_text_from_excel

app = FastAPI(
    title="Document Extraction Microservice",
    description="Extract text from various document types (PDF, Word, TXT, PowerPoint, Excel)",
    version="1.0.0"
)

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Pydantic models for request/response
class ExtractionRequest(BaseModel):
    file_type: str
    options: Optional[Dict[str, Any]] = {}

class ExtractionResponse(BaseModel):
    success: bool
    text: str = ""
    pages: int = 0
    metadata: Dict[str, Any] = {}
    word_count: int = 0
    character_count: int = 0
    extraction_method: str = ""
    error: Optional[str] = None

class HealthResponse(BaseModel):
    status: str
    message: str
    version: str

# Health check endpoint
@app.get("/health", response_model=HealthResponse)
async def health_check():
    """Health check endpoint"""
    return HealthResponse(
        status="healthy",
        message="Document extraction microservice is running",
        version="1.0.0"
    )

# Root endpoint
@app.get("/")
async def root():
    """Root endpoint with service information"""
    return {
        "service": "Document Extraction Microservice",
        "version": "1.0.0",
        "endpoints": {
            "health": "/health",
            "extract": "/extract",
            "extract_file": "/extract/file"
        }
    }

# Extract from file path endpoint
@app.post("/extract", response_model=ExtractionResponse)
async def extract_from_path(
    file_path: str = Form(...),
    file_type: str = Form(...),
    options: str = Form("{}")
):
    """
    Extract text from a file given its path
    """
    try:
        # Validate file path
        if not os.path.exists(file_path):
            raise HTTPException(status_code=404, detail=f"File not found: {file_path}")
        
        # Parse options
        try:
            options_dict = json.loads(options) if options else {}
        except json.JSONDecodeError:
            options_dict = {}
        
        # Route to appropriate extractor based on file type
        result = await route_extraction(file_path, file_type, options_dict)
        
        return ExtractionResponse(**result)
        
    except HTTPException:
        raise
    except Exception as e:
        return ExtractionResponse(
            success=False,
            error=f"Extraction failed: {str(e)}"
        )

# Extract from uploaded file endpoint
@app.post("/extract/file", response_model=ExtractionResponse)
async def extract_from_upload(
    file: UploadFile = File(...),
    file_type: str = Form(...),
    options: str = Form("{}")
):
    """
    Extract text from an uploaded file
    """
    temp_file_path = None
    
    try:
        # Parse options
        try:
            options_dict = json.loads(options) if options else {}
        except json.JSONDecodeError:
            options_dict = {}
        
        # Create temporary file
        with tempfile.NamedTemporaryFile(delete=False, suffix=f".{file_type}") as temp_file:
            temp_file_path = temp_file.name
            content = await file.read()
            temp_file.write(content)
        
        # Route to appropriate extractor
        result = await route_extraction(temp_file_path, file_type, options_dict)
        
        return ExtractionResponse(**result)
        
    except Exception as e:
        return ExtractionResponse(
            success=False,
            error=f"Extraction failed: {str(e)}"
        )
    finally:
        # Clean up temporary file
        if temp_file_path and os.path.exists(temp_file_path):
            try:
                os.unlink(temp_file_path)
            except:
                pass

async def route_extraction(file_path: str, file_type: str, options: Dict[str, Any]) -> Dict[str, Any]:
    """
    Route extraction request to appropriate extractor
    """
    try:
        file_type_lower = file_type.lower()
        
        if file_type_lower == 'pdf':
            result = extract_text_from_pdf(file_path)
        elif file_type_lower in ['doc', 'docx']:
            result = extract_text_from_word(file_path)
        elif file_type_lower == 'txt':
            result = extract_text_from_txt(file_path)
        elif file_type_lower in ['ppt', 'pptx']:
            result = extract_text_from_ppt(file_path)
        elif file_type_lower in ['xls', 'xlsx']:
            result = extract_text_from_excel(file_path)
        else:
            raise ValueError(f"Unsupported file type: {file_type}")
        
        # Ensure result has required fields
        if not isinstance(result, dict):
            raise ValueError("Extractor returned invalid result format")
        
        # Set default values for missing fields
        result.setdefault('success', False)
        result.setdefault('text', '')
        result.setdefault('pages', 0)
        result.setdefault('metadata', {})
        result.setdefault('word_count', 0)
        result.setdefault('character_count', 0)
        result.setdefault('extraction_method', 'unknown')
        
        return result
        
    except Exception as e:
        return {
            'success': False,
            'text': '',
            'pages': 0,
            'metadata': {},
            'word_count': 0,
            'character_count': 0,
            'extraction_method': 'error',
            'error': str(e)
        }

# Error handlers
@app.exception_handler(404)
async def not_found_handler(request, exc):
    return JSONResponse(
        status_code=404,
        content={"error": "Endpoint not found", "detail": str(exc)}
    )

@app.exception_handler(500)
async def internal_error_handler(request, exc):
    return JSONResponse(
        status_code=500,
        content={"error": "Internal server error", "detail": str(exc)}
    )

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=8003,
        reload=True,
        log_level="info"
    )


