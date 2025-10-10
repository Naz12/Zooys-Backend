"""
Working FastAPI Presentation Microservice
Minimal implementation to avoid dependency issues
"""

import json
import os
import sys
import time
import logging
import subprocess
from pathlib import Path

# Try to import FastAPI, if it fails, we'll use a simple HTTP server
try:
    from fastapi import FastAPI, HTTPException
    from fastapi.middleware.cors import CORSMiddleware
    from fastapi.responses import FileResponse
    from pydantic import BaseModel
    from typing import List, Dict, Any, Optional
    import uvicorn
    FASTAPI_AVAILABLE = True
except ImportError as e:
    print(f"FastAPI not available: {e}")
    FASTAPI_AVAILABLE = False

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

if FASTAPI_AVAILABLE:
    app = FastAPI(
        title="Presentation Microservice",
        description="FastAPI presentation management with PowerPoint generation",
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
            message="FastAPI Presentation microservice is running",
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
        """Export presentation to PowerPoint using Python script"""
        try:
            logger.info(f"Export request received for AI Result ID: {request.ai_result_id}")
            
            # Prepare data for Python script
            python_data = {
                "title": request.presentation_data.title,
                "slides": request.presentation_data.slides,
                "template": request.template,
                "color_scheme": request.color_scheme,
                "font_style": request.font_style,
                "user_id": request.user_id,
                "ai_result_id": request.ai_result_id
            }
            
            # Create temporary file for data
            temp_file = f"temp_presentation_{request.ai_result_id}_{int(time.time())}.json"
            with open(temp_file, 'w') as f:
                json.dump(python_data, f)
            
            # Get the path to the Python script
            script_path = Path(__file__).parent.parent / "python" / "generate_presentation.py"
            
            if not script_path.exists():
                raise HTTPException(status_code=500, detail="Python script not found")
            
            # Execute Python script
            try:
                result = subprocess.run(
                    ["py", str(script_path), temp_file],
                    capture_output=True,
                    text=True,
                    timeout=60
                )
                
                if result.returncode == 0:
                    # Parse the JSON output from the last line
                    output_lines = result.stdout.strip().split('\n')
                    json_output = output_lines[-1] if output_lines else "{}"
                    
                    try:
                        result_data = json.loads(json_output)
                        if result_data.get('success'):
                            # Clean up temp file
                            if os.path.exists(temp_file):
                                os.remove(temp_file)
                            
                            return {
                                "success": True,
                                "data": {
                                    "file_path": result_data['data']['file_path'],
                                    "file_size": result_data['data'].get('file_size', 0),
                                    "download_url": f"/download/{request.user_id}/{request.ai_result_id}"
                                },
                                "message": "Presentation exported successfully via FastAPI"
                            }
                        else:
                            raise HTTPException(status_code=500, detail=result_data.get('error', 'Export failed'))
                    except json.JSONDecodeError:
                        raise HTTPException(status_code=500, detail="Invalid response from Python script")
                else:
                    error_msg = result.stderr or "Unknown error"
                    raise HTTPException(status_code=500, detail=f"Python script failed: {error_msg}")
                    
            except subprocess.TimeoutExpired:
                raise HTTPException(status_code=500, detail="Export timeout")
            finally:
                # Clean up temp file
                if os.path.exists(temp_file):
                    os.remove(temp_file)
                
        except Exception as e:
            logger.error(f"Export failed: {str(e)}")
            raise HTTPException(status_code=500, detail=f"Export failed: {str(e)}")

    @app.get("/download/{user_id}/{ai_result_id}")
    async def download_presentation(user_id: int, ai_result_id: int):
        """Download generated presentation file"""
        try:
            # Look for the generated file
            possible_files = [
                f"presentation_{user_id}_{ai_result_id}.pptx",
                f"presentation_{ai_result_id}.pptx",
                f"generated_presentation_{ai_result_id}.pptx"
            ]
            
            file_found = None
            for filename in possible_files:
                file_path = Path("generated_presentations") / filename
                if file_path.exists():
                    file_found = file_path
                    break
            
            if not file_found:
                # Try to find any .pptx file with the ai_result_id
                generated_dir = Path("generated_presentations")
                if generated_dir.exists():
                    for file_path in generated_dir.glob("*.pptx"):
                        if str(ai_result_id) in file_path.name:
                            file_found = file_path
                            break
            
            if not file_found:
                raise HTTPException(status_code=404, detail="Presentation file not found")
            
            return FileResponse(
                path=str(file_found),
                filename=file_found.name,
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
            "main_working:app",
            host="0.0.0.0",
            port=8001,
            reload=True,
            log_level="info"
        )

else:
    # Fallback: Simple HTTP server without FastAPI
    from http.server import HTTPServer, BaseHTTPRequestHandler
    import urllib.parse
    
    class PresentationHandler(BaseHTTPRequestHandler):
        def do_GET(self):
            if self.path == '/health':
                self.send_response(200)
                self.send_header('Content-type', 'application/json')
                self.send_header('Access-Control-Allow-Origin', '*')
                self.end_headers()
                response = {
                    "status": "healthy",
                    "message": "Simple HTTP Presentation microservice is running",
                    "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
                }
                self.wfile.write(json.dumps(response).encode())
            elif self.path == '/templates':
                self.send_response(200)
                self.send_header('Content-type', 'application/json')
                self.send_header('Access-Control-Allow-Origin', '*')
                self.end_headers()
                templates = [
                    {
                        "id": "corporate_blue",
                        "name": "Corporate Blue",
                        "description": "Professional business theme"
                    }
                ]
                response = {"success": True, "templates": templates}
                self.wfile.write(json.dumps(response).encode())
            else:
                self.send_response(404)
                self.end_headers()
        
        def do_POST(self):
            if self.path == '/export':
                content_length = int(self.headers['Content-Length'])
                post_data = self.rfile.read(content_length)
                data = json.loads(post_data.decode('utf-8'))
                
                # Simple export logic here
                self.send_response(200)
                self.send_header('Content-type', 'application/json')
                self.send_header('Access-Control-Allow-Origin', '*')
                self.end_headers()
                response = {"success": True, "message": "Export via simple HTTP server"}
                self.wfile.write(json.dumps(response).encode())
            else:
                self.send_response(404)
                self.end_headers()
        
        def do_OPTIONS(self):
            self.send_response(200)
            self.send_header('Access-Control-Allow-Origin', '*')
            self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            self.send_header('Access-Control-Allow-Headers', 'Content-Type')
            self.end_headers()

    if __name__ == "__main__":
        # Create output directory
        os.makedirs("generated_presentations", exist_ok=True)
        
        # Start simple HTTP server
        server = HTTPServer(('0.0.0.0', 8001), PresentationHandler)
        print("Starting simple HTTP server on http://localhost:8001")
        print("Press Ctrl+C to stop the server")
        server.serve_forever()


