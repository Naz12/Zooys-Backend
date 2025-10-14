#!/usr/bin/env python3
"""
Simple test for image processing
"""

import os
from dotenv import load_dotenv

# Load environment variables first
load_dotenv()

print("Testing environment variables...")
api_key = os.getenv('OPENAI_API_KEY')
print(f"API Key found: {api_key is not None}")
print(f"API Key length: {len(api_key) if api_key else 0}")

if api_key and len(api_key) > 20:
    print("✅ OpenAI API key is valid")
    
    try:
        import openai
        client = openai.OpenAI(api_key=api_key)
        print("✅ OpenAI client created successfully")
        
        # Test a simple API call
        response = client.chat.completions.create(
            model="gpt-3.5-turbo",
            messages=[{"role": "user", "content": "Hello"}],
            max_tokens=10
        )
        print("✅ OpenAI API call successful")
        print(f"Response: {response.choices[0].message.content}")
        
    except Exception as e:
        print(f"❌ OpenAI test failed: {e}")
else:
    print("❌ OpenAI API key is invalid or missing")


