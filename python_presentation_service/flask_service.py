"""
Flask Presentation Microservice
Simple and compatible PowerPoint generation service
"""

from flask import Flask, request, jsonify, send_file
from flask_cors import CORS
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

app = Flask(__name__)
CORS(app)  # Enable CORS for all routes

# Initialize presentation generator
presentation_generator = PresentationGenerator() if PresentationGenerator else None

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        "status": "healthy",
        "message": "Presentation microservice is running",
        "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
    })

@app.route('/templates', methods=['GET'])
def get_templates():
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
            "colors": ["#F5F5F5", "#E0E0E0", "#757575"],
            "preview": "minimalist_gray_preview.png"
        }
    ]
    return jsonify({"success": True, "templates": templates})

@app.route('/export', methods=['POST'])
def export_presentation():
    """Export presentation to PowerPoint"""
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({"success": False, "error": "No data provided"}), 400
        
        user_id = data.get('user_id')
        ai_result_id = data.get('ai_result_id')
        presentation_data = data.get('presentation_data', {})
        
        logger.info(f"Exporting presentation for user {user_id}, AI result {ai_result_id}")
        
        if not presentation_generator:
            return jsonify({"success": False, "error": "Presentation generator not available"}), 500
        
        # Prepare data for the presentation generator
        python_data = {
            "title": presentation_data.get('title', 'Presentation'),
            "slides": presentation_data.get('slides', []),
            "template": data.get('template', 'corporate_blue'),
            "color_scheme": data.get('color_scheme', 'blue'),
            "font_style": data.get('font_style', 'modern'),
            "user_id": user_id,
            "ai_result_id": ai_result_id
        }
        
        # Generate PowerPoint file
        result = presentation_generator.generate_presentation(python_data)
        
        if result.get('success'):
            return jsonify({
                "success": True,
                "data": {
                    "file_path": result['data']['file_path'],
                    "file_size": result['data'].get('file_size', 0),
                    "download_url": f"/download/{user_id}/{ai_result_id}"
                },
                "message": "Presentation exported successfully"
            })
        else:
            return jsonify({"success": False, "error": result.get('error', 'Export failed')}), 500
            
    except Exception as e:
        logger.error(f"Export failed: {str(e)}")
        return jsonify({"success": False, "error": f"Export failed: {str(e)}"}), 500

@app.route('/download/<int:user_id>/<int:ai_result_id>', methods=['GET'])
def download_presentation(user_id, ai_result_id):
    """Download generated presentation file"""
    try:
        # Construct file path
        filename = f"presentation_{user_id}_{ai_result_id}.pptx"
        file_path = Path("generated_presentations") / filename
        
        if not file_path.exists():
            return jsonify({"success": False, "error": "Presentation file not found"}), 404
        
        return send_file(
            str(file_path),
            as_attachment=True,
            download_name=filename,
            mimetype="application/vnd.openxmlformats-officedocument.presentationml.presentation"
        )
        
    except Exception as e:
        logger.error(f"Download failed: {str(e)}")
        return jsonify({"success": False, "error": f"Download failed: {str(e)}"}), 500

@app.route('/generate', methods=['POST'])
def generate_presentation():
    """Generate presentation (alias for export)"""
    return export_presentation()

if __name__ == "__main__":
    # Create output directory
    os.makedirs("generated_presentations", exist_ok=True)
    
    # Start the server
    app.run(host="0.0.0.0", port=8001, debug=True)


