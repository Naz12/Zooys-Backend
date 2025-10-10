"""
Simple HTTP Presentation Microservice
Using Python's built-in http.server to avoid dependency issues
"""

import json
import os
import sys
import time
import logging
import subprocess
import urllib.parse
from pathlib import Path
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class PresentationHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        """Handle GET requests"""
        parsed_path = urlparse(self.path)
        path = parsed_path.path
        
        if path == '/health':
            self.send_health_response()
        elif path == '/templates':
            self.send_templates_response()
        elif path.startswith('/download/'):
            self.handle_download(parsed_path)
        else:
            self.send_error(404, "Not Found")
    
    def do_POST(self):
        """Handle POST requests"""
        parsed_path = urlparse(self.path)
        path = parsed_path.path
        
        if path == '/export' or path == '/generate':
            self.handle_export()
        else:
            self.send_error(404, "Not Found")
    
    def do_OPTIONS(self):
        """Handle CORS preflight requests"""
        self.send_response(200)
        self.send_cors_headers()
        self.end_headers()
    
    def send_cors_headers(self):
        """Send CORS headers"""
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
        self.send_header('Access-Control-Allow-Credentials', 'true')
    
    def send_json_response(self, data, status_code=200):
        """Send JSON response"""
        self.send_response(status_code)
        self.send_header('Content-Type', 'application/json')
        self.send_cors_headers()
        self.end_headers()
        
        response = json.dumps(data, indent=2)
        self.wfile.write(response.encode('utf-8'))
    
    def send_health_response(self):
        """Send health check response"""
        data = {
            "status": "healthy",
            "message": "Simple HTTP Presentation microservice is running",
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
        }
        self.send_json_response(data)
    
    def send_templates_response(self):
        """Send templates response"""
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
        data = {"success": True, "templates": templates}
        self.send_json_response(data)
    
    def handle_export(self):
        """Handle presentation export"""
        try:
            # Read request body
            content_length = int(self.headers.get('Content-Length', 0))
            if content_length == 0:
                self.send_json_response({"error": "No data provided"}, 400)
                return
            
            body = self.rfile.read(content_length)
            data = json.loads(body.decode('utf-8'))
            
            # Extract data
            presentation_data = data.get('presentation_data', {})
            user_id = data.get('user_id')
            ai_result_id = data.get('ai_result_id')
            template = data.get('template', 'corporate_blue')
            color_scheme = data.get('color_scheme', 'blue')
            font_style = data.get('font_style', 'modern')
            
            if not ai_result_id:
                self.send_json_response({"error": "ai_result_id is required"}, 400)
                return
            
            logger.info(f"Export request received for AI Result ID: {ai_result_id}")
            
            # Prepare data for Python script
            python_data = {
                "title": presentation_data.get('title', 'Presentation'),
                "slides": presentation_data.get('slides', []),
                "template": template,
                "color_scheme": color_scheme,
                "font_style": font_style,
                "user_id": user_id,
                "ai_result_id": ai_result_id
            }
            
            # Create temporary file for data
            temp_file = f"temp_presentation_{ai_result_id}_{int(time.time())}.json"
            with open(temp_file, 'w') as f:
                json.dump(python_data, f)
            
            # Get the path to the Python script
            script_path = Path(__file__).parent.parent / "python" / "generate_presentation.py"
            
            if not script_path.exists():
                self.send_json_response({"error": "Python script not found"}, 500)
                return
            
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
                            
                            response_data = {
                                "success": True,
                                "data": {
                                    "file_path": result_data['data']['file_path'],
                                    "file_size": result_data['data'].get('file_size', 0),
                                    "download_url": f"/download/{user_id}/{ai_result_id}"
                                },
                                "message": "Presentation exported successfully via Simple HTTP Server"
                            }
                            self.send_json_response(response_data)
                        else:
                            self.send_json_response({"error": result_data.get('error', 'Export failed')}, 500)
                    except json.JSONDecodeError:
                        self.send_json_response({"error": "Invalid response from Python script"}, 500)
                else:
                    error_msg = result.stderr or "Unknown error"
                    self.send_json_response({"error": f"Python script failed: {error_msg}"}, 500)
                    
            except subprocess.TimeoutExpired:
                self.send_json_response({"error": "Export timeout"}, 500)
            finally:
                # Clean up temp file
                if os.path.exists(temp_file):
                    os.remove(temp_file)
                
        except Exception as e:
            logger.error(f"Export failed: {str(e)}")
            self.send_json_response({"error": f"Export failed: {str(e)}"}, 500)
    
    def handle_download(self, parsed_path):
        """Handle file download"""
        try:
            # Extract user_id and ai_result_id from path
            path_parts = parsed_path.path.split('/')
            if len(path_parts) < 4:
                self.send_error(400, "Invalid download path")
                return
            
            user_id = path_parts[2]
            ai_result_id = path_parts[3]
            
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
                self.send_error(404, "Presentation file not found")
                return
            
            # Send file
            self.send_response(200)
            self.send_header('Content-Type', 'application/vnd.openxmlformats-officedocument.presentationml.presentation')
            self.send_header('Content-Disposition', f'attachment; filename="{file_found.name}"')
            self.send_cors_headers()
            self.end_headers()
            
            with open(file_found, 'rb') as f:
                self.wfile.write(f.read())
            
        except Exception as e:
            logger.error(f"Download failed: {str(e)}")
            self.send_error(500, f"Download failed: {str(e)}")
    
    def log_message(self, format, *args):
        """Override to use our logger"""
        logger.info(f"{self.address_string()} - {format % args}")

def run_server(port=8001):
    """Run the HTTP server"""
    # Create output directory
    os.makedirs("generated_presentations", exist_ok=True)
    
    server_address = ('0.0.0.0', port)
    httpd = HTTPServer(server_address, PresentationHandler)
    
    logger.info(f"Starting Simple HTTP Presentation Microservice on port {port}")
    logger.info(f"Health check: http://localhost:{port}/health")
    logger.info(f"Templates: http://localhost:{port}/templates")
    logger.info(f"Export: http://localhost:{port}/export")
    
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        logger.info("Server stopped by user")
        httpd.shutdown()

if __name__ == "__main__":
    run_server()


