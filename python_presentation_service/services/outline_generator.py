"""
Outline Generator Service

Handles presentation outline generation using OpenAI
"""

import time
import logging
from typing import Dict, Any, Optional

from services.openai_service import OpenAIService
from services.error_handler import handle_generation_error, log_error_context
from services.progress_tracker import create_progress_tracker, complete_progress, fail_progress

logger = logging.getLogger(__name__)

class OutlineGenerator:
    """Service for generating presentation outlines"""
    
    def __init__(self, openai_service: OpenAIService):
        self.openai_service = openai_service
    
    def generate_outline(
        self,
        content: str,
        language: str = "English",
        tone: str = "Professional",
        length: str = "Medium",
        model: Optional[str] = None
    ) -> Dict[str, Any]:
        """
        Generate presentation outline from content
        
        Args:
            content: The content to create outline from
            language: Language for the presentation
            tone: Tone of the presentation
            length: Length of presentation (Short/Medium/Long)
            model: OpenAI model to use
            
        Returns:
            Dict: Outline generation result
        """
        operation_id = f"outline_{int(time.time())}"
        
        try:
            # Create progress tracker
            tracker = create_progress_tracker(operation_id)
            tracker.start()
            
            # Validate input
            if not content or not content.strip():
                fail_progress(operation_id, "Content is empty or invalid")
                return {
                    'success': False,
                    'error': {
                        'code': 'VALIDATION_ERROR',
                        'message': 'Content is required and cannot be empty',
                        'recoverable': False
                    }
                }
            
            # Generate outline using OpenAI
            result = self.openai_service.generate_outline(
                content=content,
                language=language,
                tone=tone,
                length=length,
                model=model,
                operation_id=operation_id
            )
            
            if result['success']:
                complete_progress(operation_id)
                return result
            else:
                fail_progress(operation_id, result.get('error', {}).get('message', 'Unknown error'))
                return result
                
        except Exception as e:
            log_error_context("outline_generation", e, {
                'content_length': len(content),
                'language': language,
                'tone': tone,
                'length': length
            })
            fail_progress(operation_id, str(e))
            return handle_generation_error(e, "outline generation")
    
    def validate_outline(self, outline: Dict[str, Any]) -> Dict[str, Any]:
        """
        Validate outline structure
        
        Args:
            outline: The outline to validate
            
        Returns:
            Dict: Validation result
        """
        try:
            # Check required fields
            if not outline.get('title'):
                return {
                    'success': False,
                    'error': {
                        'code': 'VALIDATION_ERROR',
                        'message': 'Outline must have a title',
                        'recoverable': False
                    }
                }
            
            slides = outline.get('slides', [])
            if not slides or not isinstance(slides, list):
                return {
                    'success': False,
                    'error': {
                        'code': 'VALIDATION_ERROR',
                        'message': 'Outline must have slides',
                        'recoverable': False
                    }
                }
            
            # Validate each slide
            for i, slide in enumerate(slides):
                if not isinstance(slide, dict):
                    return {
                        'success': False,
                        'error': {
                            'code': 'VALIDATION_ERROR',
                            'message': f'Slide {i+1} must be a valid object',
                            'recoverable': False
                        }
                    }
                
                if not slide.get('header'):
                    return {
                        'success': False,
                        'error': {
                            'code': 'VALIDATION_ERROR',
                            'message': f'Slide {i+1} must have a header',
                            'recoverable': False
                        }
                    }
                
                subheaders = slide.get('subheaders', [])
                if not isinstance(subheaders, list):
                    return {
                        'success': False,
                        'error': {
                            'code': 'VALIDATION_ERROR',
                            'message': f'Slide {i+1} subheaders must be a list',
                            'recoverable': False
                        }
                    }
            
            return {
                'success': True,
                'message': 'Outline validation successful'
            }
            
        except Exception as e:
            log_error_context("outline_validation", e)
            return {
                'success': False,
                'error': {
                    'code': 'VALIDATION_ERROR',
                    'message': f'Outline validation failed: {str(e)}',
                    'recoverable': False
                }
            }
    
    def enhance_outline(
        self,
        outline: Dict[str, Any],
        enhancements: Dict[str, Any]
    ) -> Dict[str, Any]:
        """
        Enhance an existing outline with additional information
        
        Args:
            outline: The original outline
            enhancements: Enhancement options
            
        Returns:
            Dict: Enhanced outline result
        """
        try:
            # Create a copy of the outline
            enhanced_outline = outline.copy()
            
            # Apply enhancements
            if enhancements.get('add_speaker_notes'):
                enhanced_outline = self._add_speaker_notes(enhanced_outline)
            
            if enhancements.get('add_timing'):
                enhanced_outline = self._add_timing(enhanced_outline)
            
            if enhancements.get('add_slide_types'):
                enhanced_outline = self._add_slide_types(enhanced_outline)
            
            return {
                'success': True,
                'outline': enhanced_outline,
                'enhancements_applied': list(enhancements.keys())
            }
            
        except Exception as e:
            log_error_context("outline_enhancement", e)
            return {
                'success': False,
                'error': {
                    'code': 'GENERATION_ERROR',
                    'message': f'Outline enhancement failed: {str(e)}',
                    'recoverable': True
                }
            }
    
    def _add_speaker_notes(self, outline: Dict[str, Any]) -> Dict[str, Any]:
        """Add speaker notes to outline"""
        for slide in outline.get('slides', []):
            if not slide.get('speaker_notes'):
                slide['speaker_notes'] = f"Discuss: {slide.get('header', '')}"
        return outline
    
    def _add_timing(self, outline: Dict[str, Any]) -> Dict[str, Any]:
        """Add timing information to outline"""
        for slide in outline.get('slides', []):
            if not slide.get('estimated_time'):
                slide['estimated_time'] = "2-3 minutes"
        return outline
    
    def _add_slide_types(self, outline: Dict[str, Any]) -> Dict[str, Any]:
        """Add slide type information"""
        for i, slide in enumerate(outline.get('slides', [])):
            if not slide.get('slide_type'):
                if i == 0:
                    slide['slide_type'] = 'title'
                elif i == len(outline.get('slides', [])) - 1:
                    slide['slide_type'] = 'conclusion'
                else:
                    slide['slide_type'] = 'content'
        return outline
