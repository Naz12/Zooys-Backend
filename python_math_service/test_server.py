#!/usr/bin/env python3
"""
Simple test server for image processing
"""

import uvicorn
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
import os
from dotenv import load_dotenv
import logging

# Load environment variables
load_dotenv()

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(
    title="Test Math Solver Microservice",
    description="A test FastAPI microservice for image processing",
    version="1.0.0",
)

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.get("/health")
async def health_check():
    """Health check endpoint"""
    api_key = os.getenv('OPENAI_API_KEY')
    return {
        "status": "healthy",
        "openai_available": api_key is not None and len(api_key) > 20,
        "api_key_length": len(api_key) if api_key else 0
    }

@app.post("/test-image")
async def test_image_processing():
    """Test image processing"""
    try:
        import openai
        
        api_key = os.getenv('OPENAI_API_KEY')
        if not api_key or len(api_key) <= 20:
            return {"error": "OpenAI API key not available"}
        
        client = openai.OpenAI(api_key=api_key)
        
        # Test a simple API call
        response = client.chat.completions.create(
            model="gpt-3.5-turbo",
            messages=[{"role": "user", "content": "Hello"}],
            max_tokens=10
        )
        
        return {
            "success": True,
            "message": "OpenAI connection working",
            "response": response.choices[0].message.content
        }
        
    except Exception as e:
        logger.error(f"Test failed: {e}")
        return {"error": str(e)}

if __name__ == "__main__":
    print("Starting Test Math Solver Microservice on http://localhost:8002")
    uvicorn.run(app, host="0.0.0.0", port=8002)