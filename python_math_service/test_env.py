#!/usr/bin/env python3
"""
Test script to check environment variable loading
"""

import os
from dotenv import load_dotenv

print("Before load_dotenv():")
print(f"OPENAI_API_KEY: {os.getenv('OPENAI_API_KEY', 'NOT_FOUND')[:20]}...")

print("\nLoading .env file...")
load_dotenv()

print("After load_dotenv():")
api_key = os.getenv('OPENAI_API_KEY')
print(f"OPENAI_API_KEY: {api_key[:20] if api_key else 'NOT_FOUND'}...")
print(f"API Key length: {len(api_key) if api_key else 0}")
print(f"Is valid: {api_key is not None and api_key != 'your_openai_api_key_here' and len(api_key) > 20}")

# Test OpenAI import
try:
    import openai
    print("OpenAI library imported successfully")
    
    if api_key and len(api_key) > 20:
        client = openai.OpenAI(api_key=api_key)
        print("OpenAI client created successfully")
    else:
        print("Cannot create OpenAI client - invalid API key")
        
except ImportError as e:
    print(f"Failed to import OpenAI: {e}")
except Exception as e:
    print(f"Failed to create OpenAI client: {e}")


