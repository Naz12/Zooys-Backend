#!/usr/bin/env python3
"""
AI Presentation Generator - Python Script
Generates PowerPoint presentations from AI-generated outlines using python-pptx
"""

import json
import sys
import os
import time
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.enum.text import PP_ALIGN
from pptx.dml.color import RGBColor
from pptx.enum.shapes import MSO_SHAPE
import logging

# Configure logging to stderr only
logging.basicConfig(level=logging.INFO, stream=sys.stderr)
logger = logging.getLogger(__name__)

class PresentationGenerator:
    def __init__(self):
        self.templates = {
            'corporate_blue': {
                'primary_color': RGBColor(0, 51, 102),      # Dark blue
                'secondary_color': RGBColor(0, 102, 204),   # Light blue
                'accent_color': RGBColor(255, 255, 255),    # White
                'text_color': RGBColor(51, 51, 51),         # Dark gray
                'background_color': RGBColor(248, 249, 250), # Light gray
                'title_color': RGBColor(0, 51, 102),        # Dark blue
                'subtitle_color': RGBColor(0, 102, 204)     # Light blue
            },
            'modern_white': {
                'primary_color': RGBColor(51, 51, 51),      # Dark gray
                'secondary_color': RGBColor(102, 102, 102), # Medium gray
                'accent_color': RGBColor(0, 0, 0),          # Black
                'text_color': RGBColor(51, 51, 51),         # Dark gray
                'background_color': RGBColor(255, 255, 255), # White
                'title_color': RGBColor(0, 0, 0),           # Black
                'subtitle_color': RGBColor(51, 51, 51)      # Dark gray
            },
            'creative_colorful': {
                'primary_color': RGBColor(255, 87, 34),     # Orange
                'secondary_color': RGBColor(33, 150, 243),  # Blue
                'accent_color': RGBColor(76, 175, 80),      # Green
                'text_color': RGBColor(51, 51, 51),         # Dark gray
                'background_color': RGBColor(255, 255, 255), # White
                'title_color': RGBColor(255, 87, 34),       # Orange
                'subtitle_color': RGBColor(33, 150, 243)    # Blue
            },
            'minimalist_gray': {
                'primary_color': RGBColor(97, 97, 97),      # Medium gray
                'secondary_color': RGBColor(158, 158, 158), # Light gray
                'accent_color': RGBColor(33, 33, 33),       # Dark gray
                'text_color': RGBColor(33, 33, 33),         # Dark gray
                'background_color': RGBColor(250, 250, 250), # Very light gray
                'title_color': RGBColor(33, 33, 33),        # Dark gray
                'subtitle_color': RGBColor(97, 97, 97)      # Medium gray
            },
            'academic_formal': {
                'primary_color': RGBColor(33, 33, 33),      # Dark gray/black
                'secondary_color': RGBColor(97, 97, 97),    # Medium gray
                'accent_color': RGBColor(255, 255, 255),    # White
                'text_color': RGBColor(33, 33, 33),         # Dark gray
                'background_color': RGBColor(255, 255, 255), # White
                'title_color': RGBColor(33, 33, 33),        # Dark gray
                'subtitle_color': RGBColor(97, 97, 97)      # Medium gray
            },
            'tech_modern': {
                'primary_color': RGBColor(0, 150, 136),     # Teal
                'secondary_color': RGBColor(76, 175, 80),   # Green
                'accent_color': RGBColor(255, 193, 7),      # Amber
                'text_color': RGBColor(33, 33, 33),         # Dark gray
                'background_color': RGBColor(250, 250, 250), # Light gray
                'title_color': RGBColor(0, 150, 136),       # Teal
                'subtitle_color': RGBColor(76, 175, 80)     # Green
            },
            'elegant_purple': {
                'primary_color': RGBColor(103, 58, 183),    # Purple
                'secondary_color': RGBColor(156, 39, 176),  # Deep purple
                'accent_color': RGBColor(255, 255, 255),    # White
                'text_color': RGBColor(51, 51, 51),         # Dark gray
                'background_color': RGBColor(248, 247, 252), # Light purple
                'title_color': RGBColor(103, 58, 183),      # Purple
                'subtitle_color': RGBColor(156, 39, 176)    # Deep purple
            },
            'professional_green': {
                'primary_color': RGBColor(46, 125, 50),     # Dark green
                'secondary_color': RGBColor(76, 175, 80),   # Light green
                'accent_color': RGBColor(255, 255, 255),    # White
                'text_color': RGBColor(51, 51, 51),         # Dark gray
                'background_color': RGBColor(248, 252, 248), # Light green
                'title_color': RGBColor(46, 125, 50),       # Dark green
                'subtitle_color': RGBColor(76, 175, 80)     # Light green
            }
        }
        
        self.font_styles = {
            'modern': {'font_name': 'Calibri', 'title_size': 44, 'content_size': 24},
            'classic': {'font_name': 'Times New Roman', 'title_size': 40, 'content_size': 22},
            'minimalist': {'font_name': 'Arial', 'title_size': 36, 'content_size': 20},
            'creative': {'font_name': 'Segoe UI', 'title_size': 42, 'content_size': 24}
        }

    def generate_presentation(self, data):
        """Main method to generate PowerPoint presentation"""
        try:
            logger.info("Starting presentation generation")
            logger.info(f"Received data: {data}")
            
            # Extract data
            outline = data.get('outline', {})
            template_name = data.get('template', 'corporate_blue')
            color_scheme = data.get('color_scheme', 'blue')
            font_style = data.get('font_style', 'modern')
            user_id = data.get('user_id')
            ai_result_id = data.get('ai_result_id')
            
            logger.info(f"Template name: {template_name}")
            logger.info(f"Available templates: {list(self.templates.keys())}")
            
            # Create new presentation
            prs = Presentation()
            
            # Apply template colors and fonts
            template_colors = self.templates.get(template_name, self.templates['corporate_blue'])
            font_config = self.font_styles.get(font_style, self.font_styles['modern'])
            
            # Generate slides
            slides_data = outline.get('slides', [])
            title = outline.get('title', 'Untitled Presentation')
        
            # Create title slide
            self.create_title_slide(prs, title, template_colors, font_config)
        
            # Create content slides
            for slide_data in slides_data:
                if slide_data.get('slide_type') == 'title':
                    continue  # Skip title slides as we already created one
                
                self.create_content_slide(
                    prs, 
                    slide_data, 
                    template_colors, 
                    font_config
                )
            
            # Save presentation
            output_path = self.save_presentation(prs, user_id, ai_result_id)
            
            # Get actual file size
            file_size = 0
            if os.path.exists(output_path):
                file_size = os.path.getsize(output_path)
            
            return {
                'success': True,
                'file_path': output_path,
                'file_size': file_size,
                'download_url': f'/api/files/download/{os.path.basename(output_path)}',
                'slide_count': len(prs.slides)
            }
        
        except Exception as e:
            logger.error(f"Presentation generation failed: {str(e)}")
            return {
                'success': False,
                'error': str(e)
            }

    def create_title_slide(self, prs, title, colors, fonts):
        """Create title slide"""
        slide_layout = prs.slide_layouts[0]  # Title slide layout
        slide = prs.slides.add_slide(slide_layout)
        
        # Set title
        title_shape = slide.shapes.title
        title_shape.text = title
        title_paragraph = title_shape.text_frame.paragraphs[0]
        title_paragraph.font.name = fonts['font_name']
        title_paragraph.font.size = Pt(fonts['title_size'])
        title_paragraph.font.color.rgb = colors['title_color']
        title_paragraph.font.bold = True
        title_paragraph.alignment = PP_ALIGN.CENTER
        
        # Set background color
        background = slide.background
        fill = background.fill
        fill.solid()
        fill.fore_color.rgb = colors['background_color']

    def create_content_slide(self, prs, slide_data, colors, fonts):
        """Create content slide with full content"""
        slide_layout = prs.slide_layouts[1]  # Content slide layout
        slide = prs.slides.add_slide(slide_layout)
        
        # Set background color
        background = slide.background
        fill = background.fill
        fill.solid()
        fill.fore_color.rgb = colors['background_color']
        
        # Set title
        title_shape = slide.shapes.title
        title_shape.text = slide_data.get('header', 'Untitled Slide')
        title_paragraph = title_shape.text_frame.paragraphs[0]
        title_paragraph.font.name = fonts['font_name']
        title_paragraph.font.size = Pt(fonts['title_size'] - 8)
        title_paragraph.font.color.rgb = colors['title_color']
        title_paragraph.font.bold = True
        
        # Set content
        content_shape = slide.placeholders[1]
        text_frame = content_shape.text_frame
        text_frame.clear()
        
        # Use full content if available, otherwise fall back to subheaders
        content_items = slide_data.get('content', slide_data.get('subheaders', []))
        
        # Log what we're working with for debugging
        logger.info(f"Slide: {slide_data.get('header', 'Unknown')}")
        logger.info(f"Has content: {bool(slide_data.get('content'))}")
        logger.info(f"Has subheaders: {bool(slide_data.get('subheaders'))}")
        logger.info(f"Content items count: {len(content_items) if isinstance(content_items, list) else 0}")
        
        # If we have content array, use it; otherwise use subheaders
        if isinstance(content_items, list) and len(content_items) > 0:
            # Process ALL content items, not just the first one
            for i, content_item in enumerate(content_items):
                if content_item and content_item.strip():  # Skip empty items
                    if i == 0:
                        p = text_frame.paragraphs[0]
                    else:
                        p = text_frame.add_paragraph()
                    
                    # Clean up bullet points
                    text = content_item.strip()
                    if not text.startswith('•'):
                        text = f"• {text}"
                    
                    p.text = text
                    p.font.name = fonts['font_name']
                    p.font.size = Pt(fonts['content_size'])
                    p.font.color.rgb = colors['text_color']
                    p.level = 0
                    
                    logger.info(f"Added content item {i}: {text[:50]}...")
        else:
            # Fallback to subheaders if no content
            subheaders = slide_data.get('subheaders', [])
            logger.info(f"Using subheaders fallback: {len(subheaders)} items")
            
            for i, item in enumerate(subheaders):
                if item and item.strip():  # Skip empty items
                    if i == 0:
                        p = text_frame.paragraphs[0]
                    else:
                        p = text_frame.add_paragraph()
                    
                    # Clean up bullet points
                    text = item.strip()
                    if not text.startswith('•'):
                        text = f"• {text}"
                    
                    p.text = text
                    p.font.name = fonts['font_name']
                    p.font.size = Pt(fonts['content_size'])
                    p.font.color.rgb = colors['text_color']
                    p.level = 0
                    
                    logger.info(f"Added subheader item {i}: {text[:50]}...")

    def save_presentation(self, prs, user_id, ai_result_id):
        """Save presentation to storage directory"""
        try:
            # Create storage directory if it doesn't exist
            storage_dir = os.path.join(os.path.dirname(__file__), '..', 'storage', 'app', 'presentations')
            os.makedirs(storage_dir, exist_ok=True)
            
            # Generate filename
            filename = f"presentation_{user_id}_{ai_result_id}_{int(time.time())}.pptx"
            filepath = os.path.join(storage_dir, filename)
        
            # Save presentation
            prs.save(filepath)
            
            logger.info(f"Presentation saved to: {filepath}")
            return filepath
        
        except Exception as e:
            logger.error(f"Failed to save presentation: {str(e)}")
            raise

def main():
    """Main entry point"""
    if len(sys.argv) != 2:
        print(json.dumps({
            'success': False,
            'error': 'Usage: python generate_presentation.py <data_file>'
        }))
        sys.exit(1)
    
    data_file = sys.argv[1]
    
    try:
        # Read data from file (handle UTF-8 BOM)
        with open(data_file, 'r', encoding='utf-8-sig') as f:
            data = json.load(f)
        
        # Generate presentation
        generator = PresentationGenerator()
        result = generator.generate_presentation(data)
        
        # Output result as JSON
        print(json.dumps(result))
        
    except Exception as e:
        logger.error(f"Script execution failed: {str(e)}")
        print(json.dumps({
            'success': False,
            'error': str(e)
        }))
        sys.exit(1)

if __name__ == '__main__':
    main()