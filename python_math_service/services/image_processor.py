"""
Image Processor Service

Handles image processing for mathematical problems including:
- OCR using Tesseract for printed text
- OpenAI Vision API for handwritten math
- Image preprocessing and optimization
- Temporary file management with auto-cleanup
"""

import os
import time
import base64
import logging
from typing import Dict, List, Any, Optional, Union, Tuple
from PIL import Image, ImageEnhance, ImageFilter
import pytesseract
from pathlib import Path

from services.openai_service import OpenAIService

logger = logging.getLogger(__name__)

class ImageProcessor:
    """
    Service for processing mathematical images including:
    - OCR text extraction using Tesseract
    - Handwritten math recognition using OpenAI Vision
    - Image preprocessing and optimization
    - Confidence scoring and fallback mechanisms
    """
    
    def __init__(self, openai_service: OpenAIService = None):
        """Initialize image processor with services"""
        self.openai_service = openai_service or OpenAIService()
        self.temp_dir = Path("temp")
        self.temp_dir.mkdir(exist_ok=True)
        
        # Configure Tesseract path for Windows
        import pytesseract
        # Try multiple possible paths for Tesseract
        possible_paths = [
            os.getenv('TESSERACT_PATH'),
            r'C:\Program Files\Tesseract-OCR\tesseract.exe',
            r'C:\Program Files (x86)\Tesseract-OCR\tesseract.exe',
            'tesseract'  # If it's in PATH
        ]
        
        tesseract_path = None
        for path in possible_paths:
            if path and (path == 'tesseract' or os.path.exists(path)):
                tesseract_path = path
                break
        
        if tesseract_path:
            pytesseract.pytesseract.tesseract_cmd = tesseract_path
            logger.info(f"Tesseract configured at: {tesseract_path}")
            self.tesseract_available = True
        else:
            logger.warning("Tesseract not found in any expected location")
            self.tesseract_available = False
        
        # OCR configuration
        self.tesseract_config = '--oem 3 --psm 6 -c tessedit_char_whitelist=0123456789+-*/=()[]{}.,abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZπ∞∑∫∂√∝≤≥≠≈'
        self.min_confidence = 0.8  # Minimum confidence for Tesseract
        self.max_file_size = 10 * 1024 * 1024  # 10MB max file size
        
        # Supported image formats
        self.supported_formats = {'.jpg', '.jpeg', '.png', '.bmp', '.tiff', '.webp'}
    
    def process_image(self, image_data: Union[str, bytes], 
                     image_type: str = 'auto') -> Dict[str, Any]:
        """
        Process mathematical image and extract text
        
        Args:
            image_data: Image data as base64 string or file path
            image_type: Type of image ('printed', 'handwritten', 'auto')
            
        Returns:
            Dict: Processing result with extracted text and metadata
        """
        try:
            # Step 1: Validate and prepare image
            image_path = self._prepare_image(image_data)
            if not image_path:
                return {
                    'success': False,
                    'error': 'Failed to prepare image'
                }
            
            # Step 2: Auto-detect image type if needed
            if image_type == 'auto':
                image_type = self._detect_image_type(image_path)
            
            # Step 3: Extract text based on image type
            if image_type == 'printed':
                if self._check_tesseract_availability():
                    result = self._extract_printed_text(image_path)
                else:
                    logger.info("Tesseract not available, using OpenAI Vision for printed text")
                    result = self._extract_handwritten_text(image_path)
            elif image_type == 'handwritten':
                result = self._extract_handwritten_text(image_path)
            else:
                # Try printed first (if Tesseract available), fallback to handwritten
                if self._check_tesseract_availability():
                    result = self._extract_printed_text(image_path)
                    if result['confidence'] < self.min_confidence:
                        logger.info("Low confidence from Tesseract, trying OpenAI Vision")
                        result = self._extract_handwritten_text(image_path)
                else:
                    logger.info("Tesseract not available, using OpenAI Vision directly")
                    result = self._extract_handwritten_text(image_path)
            
            # Step 4: Clean up temporary file
            self._cleanup_temp_file(image_path)
            
            return result
            
        except Exception as e:
            logger.error(f"Image processing failed: {e}")
            return {
                'success': False,
                'error': f'Image processing failed: {str(e)}'
            }
    
    def _prepare_image(self, image_data: Union[str, bytes]) -> Optional[str]:
        """Prepare image for processing"""
        try:
            # Generate unique filename
            timestamp = int(time.time() * 1000)
            filename = f"math_image_{timestamp}.png"
            image_path = self.temp_dir / filename
            
            if isinstance(image_data, str):
                # Handle base64 encoded image
                if image_data.startswith('data:image'):
                    # Remove data URL prefix
                    image_data = image_data.split(',')[1]
                
                # Decode base64
                image_bytes = base64.b64decode(image_data)
            else:
                # Assume bytes
                image_bytes = image_data
            
            # Validate file size
            if len(image_bytes) > self.max_file_size:
                raise ValueError(f"Image too large: {len(image_bytes)} bytes")
            
            # Save image
            with open(image_path, 'wb') as f:
                f.write(image_bytes)
            
            # Validate and optimize image
            self._optimize_image(image_path)
            
            return str(image_path)
            
        except Exception as e:
            logger.error(f"Image preparation failed: {e}")
            return None
    
    def _optimize_image(self, image_path: str) -> None:
        """Optimize image for better OCR results"""
        try:
            with Image.open(image_path) as img:
                # Convert to RGB if necessary
                if img.mode != 'RGB':
                    img = img.convert('RGB')
                
                # Enhance contrast
                enhancer = ImageEnhance.Contrast(img)
                img = enhancer.enhance(1.5)
                
                # Enhance sharpness
                enhancer = ImageEnhance.Sharpness(img)
                img = enhancer.enhance(2.0)
                
                # Resize if too small (minimum 300px width)
                if img.width < 300:
                    ratio = 300 / img.width
                    new_size = (300, int(img.height * ratio))
                    img = img.resize(new_size, Image.Resampling.LANCZOS)
                
                # Save optimized image
                img.save(image_path, 'PNG', optimize=True)
                
        except Exception as e:
            logger.warning(f"Image optimization failed: {e}")
    
    def _detect_image_type(self, image_path: str) -> str:
        """Auto-detect if image contains printed or handwritten text"""
        try:
            # Check if Tesseract is available for detection
            if not self._check_tesseract_availability():
                logger.info("Tesseract not available for image type detection, defaulting to handwritten")
                return 'handwritten'
            
            # Use Tesseract to get confidence score
            with Image.open(image_path) as img:
                # Get OCR data with confidence scores
                data = pytesseract.image_to_data(img, output_type=pytesseract.Output.DICT)
                
                # Calculate average confidence
                confidences = [int(conf) for conf in data['conf'] if int(conf) > 0]
                avg_confidence = sum(confidences) / len(confidences) if confidences else 0
                
                # If confidence is high, likely printed text
                if avg_confidence > 70:
                    return 'printed'
                else:
                    return 'handwritten'
                    
        except Exception as e:
            logger.warning(f"Image type detection failed: {e}")
            return 'handwritten'  # Default to handwritten for better results
    
    def _extract_printed_text(self, image_path: str) -> Dict[str, Any]:
        """Extract text from printed mathematical content using Tesseract"""
        try:
            # Check if Tesseract is available
            if not self._check_tesseract_availability():
                logger.warning("Tesseract not available, falling back to OpenAI Vision for printed text")
                return self._extract_handwritten_text(image_path)
            
            with Image.open(image_path) as img:
                # Extract text with custom configuration
                text = pytesseract.image_to_string(img, config=self.tesseract_config)
                
                # Get confidence data
                data = pytesseract.image_to_data(img, output_type=pytesseract.Output.DICT)
                confidences = [int(conf) for conf in data['conf'] if int(conf) > 0]
                avg_confidence = sum(confidences) / len(confidences) if confidences else 0
                
                # Clean up extracted text
                cleaned_text = self._clean_extracted_text(text)
                
                return {
                    'success': True,
                    'text': cleaned_text,
                    'confidence': avg_confidence / 100.0,  # Convert to 0-1 scale
                    'method': 'tesseract_ocr',
                    'raw_text': text,
                    'metadata': {
                        'image_type': 'printed',
                        'character_count': len(cleaned_text),
                        'word_count': len(cleaned_text.split())
                    }
                }
                
        except Exception as e:
            logger.error(f"Tesseract OCR failed: {e}")
            # Fallback to OpenAI Vision if Tesseract fails
            logger.info("Falling back to OpenAI Vision due to Tesseract failure")
            return self._extract_handwritten_text(image_path)
    
    def _extract_handwritten_text(self, image_path: str) -> Dict[str, Any]:
        """Extract text from handwritten mathematical content using OpenAI Vision"""
        try:
            # Use OpenAI Vision API
            result = self.openai_service.analyze_image(image_path)
            
            if result['success']:
                analysis = result['analysis']
                
                # Extract mathematical content from analysis
                math_text = self._extract_math_from_analysis(analysis)
                
                return {
                    'success': True,
                    'text': math_text,
                    'confidence': 0.9,  # High confidence for OpenAI Vision
                    'method': 'openai_vision',
                    'raw_analysis': analysis,
                    'metadata': {
                        'image_type': 'handwritten',
                        'character_count': len(math_text),
                        'tokens_used': result.get('tokens_used', 0)
                    }
                }
            else:
                return {
                    'success': False,
                    'error': result.get('error', 'OpenAI Vision failed'),
                    'confidence': 0.0
                }
                
        except Exception as e:
            logger.error(f"OpenAI Vision failed: {e}")
            return {
                'success': False,
                'error': f'OpenAI Vision failed: {str(e)}',
                'confidence': 0.0
            }
    
    def _clean_extracted_text(self, text: str) -> str:
        """Clean and normalize extracted text"""
        if not text:
            return ""
        
        # Remove extra whitespace
        text = ' '.join(text.split())
        
        # Fix common OCR errors
        replacements = {
            'O': '0',  # Letter O to number 0 in mathematical context
            'l': '1',  # Lowercase l to number 1
            'I': '1',  # Uppercase I to number 1
            'S': '5',  # Letter S to number 5 (context dependent)
        }
        
        # Apply replacements carefully (only in mathematical contexts)
        for old, new in replacements.items():
            # Only replace if surrounded by numbers or math symbols
            import re
            pattern = f'(?<=[0-9+\\-*/=()\\s]){re.escape(old)}(?=[0-9+\\-*/=()\\s])'
            text = re.sub(pattern, new, text)
        
        # Remove non-mathematical characters at the beginning/end
        text = text.strip('.,!?;:')
        
        return text
    
    def _extract_math_from_analysis(self, analysis: str) -> str:
        """Extract mathematical content from OpenAI Vision analysis"""
        # Look for mathematical expressions in the analysis
        import re
        
        # Find mathematical expressions (numbers, operators, variables)
        math_patterns = [
            r'[0-9+\-*/=()xXyYzZ\s]+',  # Basic math expressions
            r'[a-zA-Z]\s*[=]\s*[0-9+\-*/()]+',  # Variable assignments
            r'[0-9]+\s*[+\-*/]\s*[0-9]+',  # Basic arithmetic
        ]
        
        math_expressions = []
        for pattern in math_patterns:
            matches = re.findall(pattern, analysis)
            math_expressions.extend(matches)
        
        # If we found mathematical expressions, return them
        if math_expressions:
            return ' '.join(math_expressions)
        
        # Otherwise, return the full analysis (it might be a word problem)
        return analysis
    
    def _cleanup_temp_file(self, image_path: str) -> None:
        """Clean up temporary image file"""
        try:
            if os.path.exists(image_path):
                os.remove(image_path)
                logger.debug(f"Cleaned up temp file: {image_path}")
        except Exception as e:
            logger.warning(f"Failed to cleanup temp file {image_path}: {e}")
    
    def validate_image(self, image_data: Union[str, bytes]) -> Dict[str, Any]:
        """Validate image data before processing"""
        try:
            if isinstance(image_data, str):
                # Check if it's base64
                if image_data.startswith('data:image'):
                    # Validate format
                    format_match = re.search(r'data:image/([^;]+)', image_data)
                    if not format_match:
                        return {'valid': False, 'error': 'Invalid data URL format'}
                    
                    format_type = format_match.group(1).lower()
                    if f'.{format_type}' not in self.supported_formats:
                        return {'valid': False, 'error': f'Unsupported format: {format_type}'}
                    
                    # Decode to check size
                    try:
                        image_bytes = base64.b64decode(image_data.split(',')[1])
                    except:
                        return {'valid': False, 'error': 'Invalid base64 data'}
                else:
                    # Assume it's a file path
                    if not os.path.exists(image_data):
                        return {'valid': False, 'error': 'File not found'}
                    
                    # Check file extension
                    ext = Path(image_data).suffix.lower()
                    if ext not in self.supported_formats:
                        return {'valid': False, 'error': f'Unsupported file format: {ext}'}
                    
                    # Check file size
                    file_size = os.path.getsize(image_data)
                    if file_size > self.max_file_size:
                        return {'valid': False, 'error': f'File too large: {file_size} bytes'}
                    
                    return {'valid': True, 'file_size': file_size}
            else:
                # Assume bytes
                if len(image_data) > self.max_file_size:
                    return {'valid': False, 'error': f'Data too large: {len(image_data)} bytes'}
                
                return {'valid': True, 'data_size': len(image_data)}
            
            # For base64 data
            if len(image_bytes) > self.max_file_size:
                return {'valid': False, 'error': f'Image too large: {len(image_bytes)} bytes'}
            
            return {'valid': True, 'data_size': len(image_bytes)}
            
        except Exception as e:
            return {'valid': False, 'error': f'Validation failed: {str(e)}'}
    
    def get_supported_formats(self) -> List[str]:
        """Get list of supported image formats"""
        return list(self.supported_formats)
    
    def get_service_info(self) -> Dict[str, Any]:
        """Get image processor service information"""
        return {
            'tesseract_available': self._check_tesseract_availability(),
            'openai_vision_available': self.openai_service.is_available(),
            'supported_formats': self.get_supported_formats(),
            'max_file_size': self.max_file_size,
            'min_confidence': self.min_confidence,
            'temp_directory': str(self.temp_dir)
        }
    
    def _check_tesseract_availability(self) -> bool:
        """Check if Tesseract is available"""
        try:
            import pytesseract
            # Use the configured availability from initialization
            if hasattr(self, 'tesseract_available') and self.tesseract_available:
                pytesseract.get_tesseract_version()
                return True
            else:
                return False
        except Exception as e:
            logger.warning(f"Tesseract not available: {e}")
            return False

