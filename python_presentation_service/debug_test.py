#!/usr/bin/env python3
"""
Debug script to test outline generation
"""

import sys
import os
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from services.outline_generator import OutlineGenerator
from services.openai_service import OpenAIService
import traceback

def test_outline_generation():
    """Test outline generation with detailed error reporting"""
    try:
        print("Testing outline generation...")
        
        # Initialize services
        print("Initializing OpenAI service...")
        openai_service = OpenAIService()
        print(f"OpenAI available: {openai_service.is_available()}")
        print(f"API Key set: {bool(openai_service.api_key)}")
        
        print("Initializing outline generator...")
        generator = OutlineGenerator(openai_service)
        
        # Test outline generation
        print("Generating outline...")
        result = generator.generate_outline(
            content="amrica bs uk",
            language="English",
            tone="Professional",
            length="Medium",
            model="Basic Model"
        )
        
        print(f"Result: {result}")
        
        if result['success']:
            print("✅ Outline generation successful!")
            print(f"Title: {result['outline']['title']}")
            print(f"Slides: {len(result['outline']['slides'])}")
        else:
            print("❌ Outline generation failed!")
            print(f"Error: {result.get('error', 'Unknown error')}")
            
    except Exception as e:
        print(f"❌ Exception occurred: {str(e)}")
        print("Traceback:")
        traceback.print_exc()

if __name__ == "__main__":
    test_outline_generation()
