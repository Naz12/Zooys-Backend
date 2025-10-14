"""
Content Generator Service

Handles detailed content generation for presentation slides
"""

import time
import logging
from typing import Dict, Any, Optional, List

from services.openai_service import OpenAIService
from services.error_handler import handle_generation_error, log_error_context
from services.progress_tracker import create_progress_tracker, complete_progress, fail_progress

logger = logging.getLogger(__name__)

class ContentGenerator:
    """Service for generating detailed presentation content"""
    
    def __init__(self, openai_service: OpenAIService):
        self.openai_service = openai_service
    
    def generate_content(
        self,
        outline: Dict[str, Any],
        language: str = "English",
        tone: str = "Professional",
        detail_level: str = "detailed"
    ) -> Dict[str, Any]:
        """
        Generate detailed content for presentation slides
        
        Args:
            outline: The presentation outline
            language: Language for the content
            tone: Tone of the content
            detail_level: Level of detail (brief/detailed/comprehensive)
            
        Returns:
            Dict: Content generation result
        """
        operation_id = f"content_{int(time.time())}"
        
        try:
            # Create progress tracker
            tracker = create_progress_tracker(operation_id)
            tracker.start()
            
            # Validate outline
            if not outline or not outline.get('slides'):
                fail_progress(operation_id, "Invalid outline provided")
                return {
                    'success': False,
                    'error': {
                        'code': 'VALIDATION_ERROR',
                        'message': 'Valid outline with slides is required',
                        'recoverable': False
                    }
                }
            
            # Generate content using OpenAI
            result = self.openai_service.generate_content(
                outline=outline,
                language=language,
                tone=tone,
                detail_level=detail_level,
                operation_id=operation_id
            )
            
            if result['success']:
                complete_progress(operation_id)
                return result
            else:
                fail_progress(operation_id, result.get('error', {}).get('message', 'Unknown error'))
                return result
                
        except Exception as e:
            log_error_context("content_generation", e, {
                'outline_title': outline.get('title', ''),
                'slide_count': len(outline.get('slides', [])),
                'language': language,
                'tone': tone,
                'detail_level': detail_level
            })
            fail_progress(operation_id, str(e))
            return handle_generation_error(e, "content generation")
    
    def generate_slide_content(
        self,
        slide: Dict[str, Any],
        presentation_title: str,
        language: str = "English",
        tone: str = "Professional",
        detail_level: str = "detailed"
    ) -> Dict[str, Any]:
        """
        Generate content for a single slide
        
        Args:
            slide: The slide outline
            presentation_title: Title of the presentation
            language: Language for the content
            tone: Tone of the content
            detail_level: Level of detail
            
        Returns:
            Dict: Slide content generation result
        """
        try:
            # Validate slide
            if not slide.get('header'):
                return {
                    'success': False,
                    'error': {
                        'code': 'VALIDATION_ERROR',
                        'message': 'Slide must have a header',
                        'recoverable': False
                    }
                }
            
            # Generate content using OpenAI service
            content = self.openai_service._generate_slide_content(
                slide=slide,
                presentation_title=presentation_title,
                language=language,
                tone=tone,
                detail_level=detail_level
            )
            
            if content:
                return {
                    'success': True,
                    'slide': {
                        **slide,
                        'content': content
                    }
                }
            else:
                return {
                    'success': False,
                    'error': {
                        'code': 'GENERATION_ERROR',
                        'message': 'Failed to generate slide content',
                        'recoverable': True
                    }
                }
                
        except Exception as e:
            log_error_context("slide_content_generation", e, {
                'slide_header': slide.get('header', ''),
                'presentation_title': presentation_title
            })
            return handle_generation_error(e, "slide content generation")
    
    def enhance_content(
        self,
        presentation: Dict[str, Any],
        enhancements: Dict[str, Any]
    ) -> Dict[str, Any]:
        """
        Enhance presentation content with additional features
        
        Args:
            presentation: The presentation with content
            enhancements: Enhancement options
            
        Returns:
            Dict: Enhanced presentation result
        """
        try:
            enhanced_presentation = presentation.copy()
            slides = enhanced_presentation.get('slides', [])
            
            # Apply enhancements
            if enhancements.get('add_bullet_points'):
                slides = self._add_bullet_points(slides)
            
            if enhancements.get('add_transitions'):
                slides = self._add_transitions(slides)
            
            if enhancements.get('add_visual_cues'):
                slides = self._add_visual_cues(slides)
            
            if enhancements.get('add_speaker_notes'):
                slides = self._add_speaker_notes(slides)
            
            enhanced_presentation['slides'] = slides
            
            return {
                'success': True,
                'presentation': enhanced_presentation,
                'enhancements_applied': list(enhancements.keys())
            }
            
        except Exception as e:
            log_error_context("content_enhancement", e)
            return {
                'success': False,
                'error': {
                    'code': 'GENERATION_ERROR',
                    'message': f'Content enhancement failed: {str(e)}',
                    'recoverable': True
                }
            }
    
    def _add_bullet_points(self, slides: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """Add bullet point formatting to content"""
        for slide in slides:
            content = slide.get('content', '')
            if content and not content.startswith('•'):
                # Convert content to bullet points
                lines = content.split('\n')
                bullet_lines = []
                for line in lines:
                    line = line.strip()
                    if line:
                        if not line.startswith('•'):
                            bullet_lines.append(f"• {line}")
                        else:
                            bullet_lines.append(line)
                slide['content'] = '\n'.join(bullet_lines)
        return slides
    
    def _add_transitions(self, slides: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """Add transition suggestions to slides"""
        for i, slide in enumerate(slides):
            if i > 0:  # Skip first slide
                slide['transition'] = "Smooth transition from previous slide"
        return slides
    
    def _add_visual_cues(self, slides: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """Add visual cue suggestions to slides"""
        for slide in slides:
            if not slide.get('visual_cues'):
                slide['visual_cues'] = [
                    "Use relevant images or graphics",
                    "Highlight key points with colors",
                    "Consider charts or diagrams if applicable"
                ]
        return slides
    
    def _add_speaker_notes(self, slides: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """Add speaker notes to slides"""
        for slide in slides:
            if not slide.get('speaker_notes'):
                header = slide.get('header', '')
                content = slide.get('content', '')
                slide['speaker_notes'] = f"Key points to discuss: {header}. {content[:100]}..."
        return slides
    
    def validate_content(self, presentation: Dict[str, Any]) -> Dict[str, Any]:
        """
        Validate presentation content
        
        Args:
            presentation: The presentation to validate
            
        Returns:
            Dict: Validation result
        """
        try:
            slides = presentation.get('slides', [])
            
            if not slides:
                return {
                    'success': False,
                    'error': {
                        'code': 'VALIDATION_ERROR',
                        'message': 'Presentation must have slides',
                        'recoverable': False
                    }
                }
            
            # Validate each slide
            for i, slide in enumerate(slides):
                if not slide.get('header'):
                    return {
                        'success': False,
                        'error': {
                            'code': 'VALIDATION_ERROR',
                            'message': f'Slide {i+1} must have a header',
                            'recoverable': False
                        }
                    }
                
                if not slide.get('content'):
                    return {
                        'success': False,
                        'error': {
                            'code': 'VALIDATION_ERROR',
                            'message': f'Slide {i+1} must have content',
                            'recoverable': False
                        }
                    }
            
            return {
                'success': True,
                'message': 'Content validation successful',
                'slide_count': len(slides)
            }
            
        except Exception as e:
            log_error_context("content_validation", e)
            return {
                'success': False,
                'error': {
                    'code': 'VALIDATION_ERROR',
                    'message': f'Content validation failed: {str(e)}',
                    'recoverable': False
                }
            }
