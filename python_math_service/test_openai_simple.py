#!/usr/bin/env python3
"""
Simple OpenAI test without environment variables
"""

import os

# Clear any problematic environment variables
if 'OPENAI_URL' in os.environ:
    del os.environ['OPENAI_URL']

# Set the API key directly
api_key = "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"

print("Testing OpenAI with direct API key...")

try:
    import openai
    print("✅ OpenAI library imported successfully")
    
    client = openai.OpenAI(api_key=api_key)
    print("✅ OpenAI client created successfully")
    
    # Test a simple API call
    response = client.chat.completions.create(
        model="gpt-3.5-turbo",
        messages=[{"role": "user", "content": "Say hello"}],
        max_tokens=10
    )
    print("✅ OpenAI API call successful")
    print(f"Response: {response.choices[0].message.content}")
    
except Exception as e:
    print(f"❌ OpenAI test failed: {e}")
    import traceback
    traceback.print_exc()


