#!/usr/bin/env python3
"""
Startup script for Document Extraction Microservice
"""

import os
import sys
import subprocess
import time
from pathlib import Path

def check_dependencies():
    """Check if required dependencies are installed"""
    try:
        import fastapi
        import uvicorn
        print("✅ FastAPI dependencies found")
        return True
    except ImportError as e:
        print(f"❌ Missing dependencies: {e}")
        print("Installing dependencies...")
        try:
            subprocess.check_call([sys.executable, "-m", "pip", "install", "-r", "requirements_fastapi.txt"])
            print("✅ Dependencies installed successfully")
            return True
        except subprocess.CalledProcessError:
            print("❌ Failed to install dependencies")
            return False

def start_service():
    """Start the FastAPI service"""
    print("🚀 Starting Document Extraction Microservice...")
    print("📍 Service will run on: http://localhost:8003")
    print("📚 API Documentation: http://localhost:8003/docs")
    print("🔍 Health Check: http://localhost:8003/health")
    print("\n" + "="*50)
    
    try:
        import uvicorn
        uvicorn.run(
            "main:app",
            host="0.0.0.0",
            port=8003,
            reload=True,
            log_level="info"
        )
    except KeyboardInterrupt:
        print("\n🛑 Service stopped by user")
    except Exception as e:
        print(f"❌ Error starting service: {e}")

if __name__ == "__main__":
    print("Document Extraction Microservice")
    print("="*40)
    
    # Check if we're in the right directory
    if not os.path.exists("main.py"):
        print("❌ main.py not found. Please run this script from the python_document_extractors directory")
        sys.exit(1)
    
    # Check dependencies
    if not check_dependencies():
        print("❌ Cannot start service due to missing dependencies")
        sys.exit(1)
    
    # Start the service
    start_service()


