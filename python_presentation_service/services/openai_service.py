"""
OpenAI Service for Presentation Microservice

Handles OpenAI API integration for generating presentation outlines and content
"""

import os
import time
import logging
from typing import Dict, Any, Optional, List
import openai
from openai import OpenAI
from dotenv import load_dotenv

from services.error_handler import handle_openai_error, log_error_context
from services.progress_tracker import update_progress

# Load environment variables
load_dotenv()

logger = logging.getLogger(__name__)

class OpenAIService:
    """Service for OpenAI API integration in presentation microservice"""
    
    def __init__(self):
        """Initialize OpenAI service with configuration"""
        self.api_key = os.getenv('OPENAI_API_KEY')
        self.model = os.getenv('OPENAI_MODEL', 'gpt-3.5-turbo')
        self.max_tokens = int(os.getenv('OPENAI_MAX_TOKENS', '2000'))
        self.temperature = float(os.getenv('OPENAI_TEMPERATURE', '0.7'))
        
        # Initialize OpenAI client
        if self.api_key:
            self.client = OpenAI(api_key=self.api_key)
        else:
            self.client = None
            logger.warning("OpenAI API key not found. Service will be disabled.")
        
        # Retry configuration
        self.max_retries = 3
        self.base_delay = 1.0
        self.timeout = 60
    
    def is_available(self) -> bool:
        """Check if OpenAI service is available"""
        return self.client is not None and self.api_key is not None
    
    def generate_outline(
        self,
        content: str,
        language: str = "English",
        tone: str = "Professional",
        length: str = "Medium",
        model: Optional[str] = None,
        operation_id: Optional[str] = None
    ) -> Dict[str, Any]:
        """
        Generate presentation outline from content
        
        Args:
            content: The content to create outline from
            language: Language for the presentation
            tone: Tone of the presentation
            length: Length of presentation (Short/Medium/Long)
            model: OpenAI model to use (can be frontend model name or actual model ID)
            operation_id: Progress tracking ID
            
        Returns:
            Dict: Outline generation result
        """
        if not self.is_available():
            return {
                'success': False,
                'error': {
                    'code': 'SERVICE_UNAVAILABLE',
                    'message': 'OpenAI service not available',
                    'recoverable': False
                }
            }
        
        try:
            if operation_id:
                update_progress(operation_id, "generation", "Generating presentation outline")
            
            # Map frontend model names to actual OpenAI model IDs
            actual_model = self._map_model_name(model or self.model)
            
            # Determine slide count based on length
            slide_count = self._get_slide_count(length)
            
            # Build outline generation prompt
            prompt = self._build_outline_prompt(content, language, tone, slide_count)
            
            # Generate outline with retry logic
            response = self._generate_with_retry(prompt, actual_model, operation_id)
            
            if response:
                # Parse the response
                outline_data = self._parse_outline_response(response)
                
                if outline_data:
                    if operation_id:
                        update_progress(operation_id, "processing", "Processing outline structure")
                    
                    return {
                        'success': True,
                        'outline': outline_data,
                        'metadata': {
                            'tokens_used': self._estimate_tokens(prompt + response),
                            'model': model or self.model,
                            'language': language,
                            'tone': tone,
                            'length': length
                        }
                    }
                else:
                    return {
                        'success': False,
                        'error': {
                            'code': 'GENERATION_ERROR',
                            'message': 'Failed to parse outline response',
                            'recoverable': True
                        }
                    }
            else:
                return {
                    'success': False,
                    'error': {
                        'code': 'GENERATION_ERROR',
                        'message': 'Failed to generate outline',
                        'recoverable': True
                    }
                }
                
        except Exception as e:
            log_error_context("outline_generation", e, {
                'content_length': len(content),
                'language': language,
                'tone': tone,
                'length': length
            })
            return handle_openai_error(e)
    
    def generate_content(
        self,
        outline: Dict[str, Any],
        language: str = "English",
        tone: str = "Professional",
        detail_level: str = "detailed",
        operation_id: Optional[str] = None
    ) -> Dict[str, Any]:
        """
        Generate detailed content for presentation slides
        
        Args:
            outline: The presentation outline
            language: Language for the content
            tone: Tone of the content
            detail_level: Level of detail (brief/detailed/comprehensive)
            operation_id: Progress tracking ID
            
        Returns:
            Dict: Content generation result
        """
        if not self.is_available():
            return {
                'success': False,
                'error': {
                    'code': 'SERVICE_UNAVAILABLE',
                    'message': 'OpenAI service not available',
                    'recoverable': False
                }
            }
        
        try:
            if operation_id:
                update_progress(operation_id, "generation", "Generating slide content")
            
            # Generate content for each slide
            slides_with_content = []
            total_slides = len(outline.get('slides', []))
            
            for i, slide in enumerate(outline.get('slides', [])):
                if operation_id:
                    progress = 40 + (i / total_slides) * 40  # 40-80% range
                    update_progress(
                        operation_id,
                        "generation",
                        f"Generating content for slide {i+1}/{total_slides}"
                    )
                
                # Generate content for this slide
                slide_content = self._generate_slide_content(
                    slide, outline.get('title', ''), language, tone, detail_level
                )
                
                if slide_content:
                    slide['content'] = slide_content
                    slides_with_content.append(slide)
                else:
                    # Fallback content if generation fails
                    slide['content'] = self._get_fallback_slide_content(slide)
                    slides_with_content.append(slide)
            
            if operation_id:
                update_progress(operation_id, "processing", "Finalizing presentation content")
            
            return {
                'success': True,
                'presentation': {
                    'title': outline.get('title', ''),
                    'slides': slides_with_content,
                    'estimated_duration': outline.get('estimated_duration', ''),
                    'slide_count': len(slides_with_content)
                },
                'metadata': {
                    'tokens_used': self._estimate_tokens(str(outline) + str(slides_with_content)),
                    'model': self.model,
                    'language': language,
                    'tone': tone,
                    'detail_level': detail_level
                }
            }
                
        except Exception as e:
            log_error_context("content_generation", e, {
                'outline_title': outline.get('title', ''),
                'slide_count': len(outline.get('slides', [])),
                'language': language,
                'tone': tone
            })
            return handle_openai_error(e)
    
    def _build_outline_prompt(self, content: str, language: str, tone: str, slide_count: int) -> str:
        """Build prompt for outline generation"""
        
        return f"""Create a {tone.lower()} presentation outline in {language} for the following content:

CONTENT:
{content}

REQUIREMENTS:
- Create exactly {slide_count} slides
- Use {tone.lower()} tone throughout
- Structure should be logical and engaging
- Include a title slide and conclusion slide
- Each slide should have a clear header and 2-4 subheaders

Return ONLY a valid JSON response in this exact format:
{{
  "title": "Presentation Title",
  "slides": [
    {{
      "slide_number": 1,
      "header": "Slide Title",
      "subheaders": ["Point 1", "Point 2", "Point 3"],
      "slide_type": "title"
    }},
    {{
      "slide_number": 2,
      "header": "Slide Title",
      "subheaders": ["Point 1", "Point 2"],
      "slide_type": "content"
    }}
  ],
  "estimated_duration": "{slide_count * 2} minutes",
  "slide_count": {slide_count}
}}

IMPORTANT: Return ONLY the JSON, no additional text or explanations."""

    def _build_slide_content_prompt(
        self,
        slide: Dict[str, Any],
        presentation_title: str,
        language: str,
        tone: str,
        detail_level: str
    ) -> str:
        """Build prompt for individual slide content generation"""
        
        detail_instruction = {
            'brief': 'Provide brief, concise content (1-2 sentences per subheader)',
            'detailed': 'Provide detailed content (2-3 sentences per subheader)',
            'comprehensive': 'Provide comprehensive content (3-4 sentences per subheader)'
        }.get(detail_level, 'Provide detailed content')
        
        return f"""Generate {detail_level} content for this presentation slide in {language} with a {tone.lower()} tone.

PRESENTATION TITLE: {presentation_title}
SLIDE: {slide.get('header', '')}
SUBHEADERS: {', '.join(slide.get('subheaders', []))}

REQUIREMENTS:
- {detail_instruction}
- Use {tone.lower()} tone
- Make content engaging and informative
- Ensure content flows logically
- Use bullet points or short paragraphs

Return ONLY the content text, no additional formatting or explanations."""

    def _generate_slide_content(
        self,
        slide: Dict[str, Any],
        presentation_title: str,
        language: str,
        tone: str,
        detail_level: str
    ) -> Optional[str]:
        """Generate content for a single slide"""
        
        try:
            prompt = self._build_slide_content_prompt(
                slide, presentation_title, language, tone, detail_level
            )
            
            response = self._generate_with_retry(prompt, self.model)
            return response.strip() if response else None
            
        except Exception as e:
            logger.warning(f"Failed to generate content for slide {slide.get('header', '')}: {e}")
            return None
    
    def _parse_outline_response(self, response: str) -> Optional[Dict[str, Any]]:
        """Parse outline response from OpenAI"""
        
        try:
            # Try to extract JSON from response
            import json
            
            # Find JSON in response
            start_idx = response.find('{')
            end_idx = response.rfind('}') + 1
            
            if start_idx == -1 or end_idx == 0:
                return None
            
            json_str = response[start_idx:end_idx]
            data = json.loads(json_str)
            
            # Validate required fields
            if not data.get('title') or not data.get('slides'):
                return None
            
            return data
            
        except Exception as e:
            logger.error(f"Failed to parse outline response: {e}")
            return None
    
    def _generate_with_retry(
        self,
        prompt: str,
        model: str,
        operation_id: Optional[str] = None
    ) -> Optional[str]:
        """Generate response with retry logic"""
        
        for attempt in range(self.max_retries):
            try:
                if operation_id and attempt > 0:
                    update_progress(
                        operation_id,
                        "generation",
                        f"Retrying OpenAI request (attempt {attempt + 1}/{self.max_retries})"
                    )
                
                response = self.client.chat.completions.create(
                    model=model,
                    messages=[
                        {
                            "role": "user",
                            "content": prompt
                        }
                    ],
                    max_tokens=self.max_tokens,
                    temperature=self.temperature,
                    timeout=self.timeout
                )
                
                if response.choices and response.choices[0].message.content:
                    content = response.choices[0].message.content.strip()
                    logger.info(f"OpenAI API success on attempt {attempt + 1}")
                    return content
                
            except Exception as e:
                logger.warning(f"OpenAI API attempt {attempt + 1} failed: {e}")
                
                if attempt < self.max_retries - 1:
                    delay = self.base_delay * (2 ** attempt)  # Exponential backoff
                    logger.info(f"Waiting {delay} seconds before retry...")
                    time.sleep(delay)
        
        logger.error("OpenAI API failed after all retries")
        return None
    
    def _get_slide_count(self, length: str) -> int:
        """Get slide count based on length"""
        counts = {
            'short': 8,
            'medium': 12,
            'long': 18
        }
        return counts.get(length.lower(), 12)
    
    def _map_model_name(self, model_name: str) -> str:
        """Map frontend model names to actual OpenAI model IDs"""
        model_mapping = {
            'Basic Model': 'gpt-3.5-turbo',
            'Advanced Model': 'gpt-4',
            'Premium Model': 'gpt-4o',
            'gpt-3.5-turbo': 'gpt-3.5-turbo',
            'gpt-4': 'gpt-4',
            'gpt-4o': 'gpt-4o'
        }
        return model_mapping.get(model_name, 'gpt-3.5-turbo')
    
    def _get_fallback_slide_content(self, slide: Dict[str, Any]) -> str:
        """Get fallback content when generation fails"""
        
        header = slide.get('header', 'Slide')
        subheaders = slide.get('subheaders', [])
        
        content = f"**{header}**\n\n"
        
        for subheader in subheaders:
            content += f"• {subheader}\n"
        
        content += "\n*Content will be expanded based on the presentation topic.*"
        
        return content
    
    def _estimate_tokens(self, text: str) -> int:
        """Estimate token count for text"""
        # Rough estimation: 1 token ≈ 4 characters for English text
        return len(text) // 4
    
    def get_service_info(self) -> Dict[str, Any]:
        """Get OpenAI service information"""
        return {
            'available': self.is_available(),
            'model': self.model,
            'max_tokens': self.max_tokens,
            'temperature': self.temperature,
            'max_retries': self.max_retries,
            'timeout': self.timeout
        }
