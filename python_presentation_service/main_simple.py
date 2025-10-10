"""
Simple FastAPI Presentation Microservice
Compatible with older Python versions and simpler dependencies
"""

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import FileResponse
from pydantic import BaseModel
from typing import List, Dict, Any, Optional
import uvicorn
import json
import os
import sys
import time
import logging
from pathlib import Path

# Add the parent directory to the path to import our presentation generator
sys.path.append(str(Path(__file__).parent.parent / "python"))

try:
    from generate_presentation import PresentationGenerator
except ImportError as e:
    print(f"Warning: Could not import PresentationGenerator: {e}")
    PresentationGenerator = None

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(
    title="Presentation Microservice",
    description="Simple presentation management with PowerPoint generation",
    version="1.0.0"
)

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Configure this properly for production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize presentation generator
presentation_generator = PresentationGenerator() if PresentationGenerator else None

# Pydantic models
class PresentationData(BaseModel):
    title: str
    slides: List[Dict[str, Any]]

class ExportRequest(BaseModel):
    presentation_data: PresentationData
    user_id: int
    ai_result_id: int
    template: Optional[str] = "corporate_blue"
    color_scheme: Optional[str] = "blue"
    font_style: Optional[str] = "modern"

class HealthResponse(BaseModel):
    status: str
    message: str
    timestamp: str

@app.get("/health", response_model=HealthResponse)
async def health_check():
    """Health check endpoint"""
    return HealthResponse(
        status="healthy",
        message="Presentation microservice is running",
        timestamp=time.strftime("%Y-%m-%d %H:%M:%S")
    )

@app.get("/templates")
async def get_templates():
    """Get available presentation templates"""
    templates = [
        {
            "id": "corporate_blue",
            "name": "Corporate Blue",
            "description": "Professional business theme",
            "colors": ["#003366", "#0066CC", "#FFFFFF"],
            "preview": "corporate_blue_preview.png"
        },
        {
            "id": "modern_white",
            "name": "Modern White",
            "description": "Clean minimalist theme",
            "colors": ["#FFFFFF", "#F8F9FA", "#6C757D"],
            "preview": "modern_white_preview.png"
        },
        {
            "id": "creative_colorful",
            "name": "Creative Colorful",
            "description": "Vibrant energetic theme",
            "colors": ["#FF6B6B", "#4ECDC4", "#45B7D1"],
            "preview": "creative_colorful_preview.png"
        },
        {
            "id": "minimalist_gray",
            "name": "Minimalist Gray",
            "description": "Simple elegant theme",
            "colors": ["#2C3E50", "#95A5A6", "#ECF0F1"],
            "preview": "minimalist_gray_preview.png"
        }
    ]
    return {"success": True, "templates": templates}

@app.post("/export")
async def export_presentation(request: ExportRequest):
    """Export presentation to PowerPoint"""
    try:
        logger.info(f"Export request received for AI Result ID: {request.ai_result_id}")
        
        if not presentation_generator:
            raise HTTPException(status_code=500, detail="Presentation generator not available")
        
        # Prepare data for PowerPoint generation
        python_data = {
            "title": request.presentation_data.title,
            "slides": request.presentation_data.slides,
            "template": request.template,
            "color_scheme": request.color_scheme,
            "font_style": request.font_style,
            "user_id": request.user_id,
            "ai_result_id": request.ai_result_id
        }
        
        # Generate PowerPoint using the existing Python script logic
        result = presentation_generator.generate_presentation(python_data)
        
        if result.get('success'):
            return {
                "success": True,
                "data": {
                    "file_path": result['data']['file_path'],
                    "file_size": result['data'].get('file_size', 0),
                    "download_url": f"/download/{request.user_id}/{request.ai_result_id}"
                },
                "message": "Presentation exported successfully"
            }
        else:
            raise HTTPException(status_code=500, detail=result.get('error', 'Export failed'))
            
    except Exception as e:
        logger.error(f"Export failed: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Export failed: {str(e)}")

@app.get("/download/{user_id}/{ai_result_id}")
async def download_presentation(user_id: int, ai_result_id: int):
    """Download generated presentation file"""
    try:
        # Construct file path
        filename = f"presentation_{user_id}_{ai_result_id}.pptx"
        file_path = Path("generated_presentations") / filename
        
        if not file_path.exists():
            raise HTTPException(status_code=404, detail="Presentation file not found")
        
        return FileResponse(
            path=str(file_path),
            filename=filename,
            media_type="application/vnd.openxmlformats-officedocument.presentationml.presentation"
        )
        
    except Exception as e:
        logger.error(f"Download failed: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Download failed: {str(e)}")

@app.post("/generate")
async def generate_presentation(request: ExportRequest):
    """Generate presentation (alias for export)"""
    return await export_presentation(request)

if __name__ == "__main__":
    # Create output directory
    os.makedirs("generated_presentations", exist_ok=True)
    
    # Start the server
    uvicorn.run(
        "main_simple:app",
        host="0.0.0.0",
        port=8001,
        reload=True,
        log_level="info"
    )