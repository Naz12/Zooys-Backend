"""
OpenAI Service

Handles OpenAI API integration for generating educational explanations
from Python solver results. Provides token-efficient explanations by
working with pre-solved mathematical problems.
"""

import os
import time
import logging
from typing import Dict, List, Any, Optional, Union
import openai
from openai import OpenAI
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

logger = logging.getLogger(__name__)

class OpenAIService:
    """
    Service for OpenAI API integration including:
    - Educational explanation generation
    - Image analysis using Vision API
    - Token usage tracking
    - Retry logic with exponential backoff
    """
    
    def __init__(self):
        """Initialize OpenAI service with configuration"""
        self.api_key = os.getenv('OPENAI_API_KEY')
        self.model = os.getenv('OPENAI_MODEL', 'gpt-3.5-turbo')
        self.vision_model = os.getenv('OPENAI_VISION_MODEL', 'gpt-4o')
        self.max_tokens = int(os.getenv('OPENAI_MAX_TOKENS', '1500'))
        self.temperature = float(os.getenv('OPENAI_TEMPERATURE', '0.3'))
        
        # Initialize OpenAI client
        if self.api_key:
            self.client = OpenAI(api_key=self.api_key)
        else:
            self.client = None
            logger.warning("OpenAI API key not found. Service will be disabled.")
        
        # Retry configuration
        self.max_retries = 3
        self.base_delay = 1.0
        self.timeout = 30
    
    def is_available(self) -> bool:
        """Check if OpenAI service is available"""
        return self.client is not None and self.api_key is not None
    
    def explain_solution(self, problem_text: str, solution_data: Dict[str, Any], 
                        subject_area: str = None, difficulty_level: str = 'intermediate') -> Dict[str, Any]:
        """
        Generate educational explanation for a solved mathematical problem
        
        Args:
            problem_text: Original problem text
            solution_data: Solution data from Python solver
            subject_area: Subject area (algebra, calculus, etc.)
            difficulty_level: Difficulty level (beginner, intermediate, advanced)
            
        Returns:
            Dict: Explanation result with educational content
        """
        if not self.is_available():
            return {
                'success': False,
                'error': 'OpenAI service not available',
                'explanation': self._get_fallback_explanation(solution_data)
            }
        
        try:
            # Build explanation prompt
            prompt = self._build_explanation_prompt(
                problem_text, solution_data, subject_area, difficulty_level
            )
            
            # Generate explanation with retry logic
            explanation = self._generate_with_retry(prompt)
            
            if explanation:
                return {
                    'success': True,
                    'explanation': explanation,
                    'method': 'openai_generated',
                    'tokens_used': self._estimate_tokens(prompt + explanation)
                }
            else:
                return {
                    'success': False,
                    'error': 'Failed to generate explanation',
                    'explanation': self._get_fallback_explanation(solution_data)
                }
                
        except Exception as e:
            logger.error(f"OpenAI explanation failed: {e}")
            return {
                'success': False,
                'error': f'OpenAI explanation failed: {str(e)}',
                'explanation': self._get_fallback_explanation(solution_data)
            }
    
    def analyze_image(self, image_path: str, prompt: str = None) -> Dict[str, Any]:
        """
        Analyze mathematical image using OpenAI Vision API
        
        Args:
            image_path: Path to the image file
            prompt: Optional custom prompt for image analysis
            
        Returns:
            Dict: Image analysis result
        """
        if not self.is_available():
            return {
                'success': False,
                'error': 'OpenAI Vision service not available'
            }
        
        try:
            # Use default prompt if none provided
            if not prompt:
                prompt = self._get_default_image_prompt()
            
            # Read and encode image
            import base64
            with open(image_path, 'rb') as image_file:
                base64_image = base64.b64encode(image_file.read()).decode('utf-8')
            
            # Prepare messages for Vision API
            messages = [
                {
                    "role": "user",
                    "content": [
                        {
                            "type": "text",
                            "text": prompt
                        },
                        {
                            "type": "image_url",
                            "image_url": {
                                "url": f"data:image/jpeg;base64,{base64_image}"
                            }
                        }
                    ]
                }
            ]
            
            # Generate analysis with retry logic
            response = self._generate_vision_with_retry(messages)
            
            if response:
                return {
                    'success': True,
                    'analysis': response,
                    'method': 'openai_vision',
                    'tokens_used': self._estimate_tokens(prompt + response)
                }
            else:
                return {
                    'success': False,
                    'error': 'Failed to analyze image'
                }
                
        except Exception as e:
            logger.error(f"OpenAI Vision analysis failed: {e}")
            return {
                'success': False,
                'error': f'OpenAI Vision analysis failed: {str(e)}'
            }
    
    def _build_explanation_prompt(self, problem_text: str, solution_data: Dict[str, Any], 
                                subject_area: str, difficulty_level: str) -> str:
        """Build educational explanation prompt"""
        
        # Extract solution information
        answer = solution_data.get('answer', '')
        steps = solution_data.get('steps', [])
        method = solution_data.get('method', '')
        
        # Format steps for the prompt
        steps_text = ""
        if steps:
            steps_text = "\n".join([
                f"Step {i+1}: {getattr(step, 'description', '')} - {getattr(step, 'expression', '')}"
                for i, step in enumerate(steps)
            ])
        
        # Build subject-specific context
        subject_context = self._get_subject_context(subject_area)
        difficulty_context = self._get_difficulty_context(difficulty_level)
        
        prompt = f"""You are an expert mathematics tutor. Provide an educational explanation for this solved mathematical problem.

PROBLEM: {problem_text}

SOLUTION PROVIDED:
- Answer: {answer}
- Method Used: {method}
- Steps Taken:
{steps_text}

CONTEXT:
- Subject Area: {subject_area} {subject_context}
- Difficulty Level: {difficulty_level} {difficulty_context}

REQUIREMENTS:
1. **Educational Focus**: Explain the mathematical concepts and reasoning behind each step
2. **Clear Language**: Use language appropriate for the difficulty level
3. **Conceptual Understanding**: Help the student understand WHY each step is taken
4. **Mathematical Principles**: Explain the mathematical rules and principles being applied
5. **Learning Value**: Make this educational and help the student learn, not just get the answer

FORMAT YOUR RESPONSE AS:
**Solution process**
[Brief explanation of the method used]

**Step-by-Step Explanation:**
[Show each step with clear reasoning]

**Answer**
[Final result]

Keep the explanation simple and direct, similar to a textbook example."""

        return prompt
    
    def _get_subject_context(self, subject_area: str) -> str:
        """Get subject-specific context for prompts"""
        contexts = {
            'algebra': '(focus on equations, variables, and algebraic manipulation)',
            'geometry': '(focus on shapes, angles, areas, and geometric relationships)',
            'calculus': '(focus on derivatives, integrals, and limits)',
            'statistics': '(focus on data analysis, probability, and statistical methods)',
            'trigonometry': '(focus on angles, triangles, and trigonometric functions)',
            'arithmetic': '(focus on basic operations and number properties)',
            'maths': '(general mathematics covering multiple areas)'
        }
        return contexts.get(subject_area, '(general mathematics)')
    
    def _get_difficulty_context(self, difficulty_level: str) -> str:
        """Get difficulty-specific context for prompts"""
        contexts = {
            'beginner': '(provide clear, simple explanations suitable for learning)',
            'intermediate': '(provide detailed explanations with mathematical reasoning)',
            'advanced': '(provide comprehensive analysis with advanced mathematical concepts)'
        }
        return contexts.get(difficulty_level, '(provide appropriate level explanations)')
    
    def _get_default_image_prompt(self) -> str:
        """Get default prompt for image analysis"""
        return """Extract the mathematical expression from this image. Return ONLY the clean mathematical expression.

RULES:
1. Use only these symbols: + - * / ( ) = ^ ÷
2. Use / for fractions, not ÷ unless clearly shown
3. Use ^ for exponents (like 3^2)
4. Use * for multiplication
5. NO extra text, explanations, or descriptions
6. NO question marks or words like "solve"
7. If you see unclear symbols, use the closest standard symbol
8. If multiple expressions, pick the main one

EXAMPLES:
- Image shows "9 - 3 ÷ (1/3) + 1 = ?" → Return: "9 - 3 / (1/3) + 1"
- Image shows "2x + 5 = 13" → Return: "2x + 5 = 13"  
- Image shows "3² + 4" → Return: "3^2 + 4"
- Image shows "√16" → Return: "sqrt(16)"

Return ONLY the mathematical expression."""
    
    def _generate_with_retry(self, prompt: str) -> Optional[str]:
        """Generate response with retry logic"""
        for attempt in range(self.max_retries):
            try:
                logger.info(f"OpenAI API attempt {attempt + 1}/{self.max_retries}")
                
                response = self.client.chat.completions.create(
                    model=self.model,
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
    
    def _generate_vision_with_retry(self, messages: List[Dict]) -> Optional[str]:
        """Generate vision response with retry logic"""
        for attempt in range(self.max_retries):
            try:
                logger.info(f"OpenAI Vision API attempt {attempt + 1}/{self.max_retries}")
                
                response = self.client.chat.completions.create(
                    model=self.vision_model,
                    messages=messages,
                    max_tokens=self.max_tokens,
                    temperature=self.temperature,
                    timeout=self.timeout
                )
                
                if response.choices and response.choices[0].message.content:
                    content = response.choices[0].message.content.strip()
                    logger.info(f"OpenAI Vision API success on attempt {attempt + 1}")
                    return content
                
            except Exception as e:
                logger.warning(f"OpenAI Vision API attempt {attempt + 1} failed: {e}")
                
                if attempt < self.max_retries - 1:
                    delay = self.base_delay * (2 ** attempt)  # Exponential backoff
                    logger.info(f"Waiting {delay} seconds before retry...")
                    time.sleep(delay)
        
        logger.error("OpenAI Vision API failed after all retries")
        return None
    
    def _get_fallback_explanation(self, solution_data: Dict[str, Any]) -> str:
        """Get fallback explanation when OpenAI is unavailable"""
        answer = solution_data.get('answer', '')
        method = solution_data.get('method', 'mathematical analysis')
        steps = solution_data.get('steps', [])
        
        explanation = f"**Mathematical Solution**\n\n"
        explanation += f"**Answer**: {answer}\n\n"
        explanation += f"**Method Used**: {method}\n\n"
        
        if steps:
            explanation += "**Solution Steps**:\n"
            for i, step in enumerate(steps, 1):
                description = getattr(step, 'description', '')
                expression = getattr(step, 'expression', '')
                explanation += f"{i}. {description}: {expression}\n"
        
        explanation += f"\n**Verification**: Please verify by substituting the answer back into the original problem."
        
        return explanation
    
    def _estimate_tokens(self, text: str) -> int:
        """Estimate token count for text"""
        # Rough estimation: 1 token ≈ 4 characters for English text
        return len(text) // 4
    
    def get_service_info(self) -> Dict[str, Any]:
        """Get OpenAI service information"""
        return {
            'available': self.is_available(),
            'model': self.model,
            'vision_model': self.vision_model,
            'max_tokens': self.max_tokens,
            'temperature': self.temperature,
            'max_retries': self.max_retries,
            'timeout': self.timeout
        }




