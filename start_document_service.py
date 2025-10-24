#!/usr/bin/env python3
"""
Standalone Document Extraction Microservice
This script can be run independently from anywhere
"""

import os
import sys
import subprocess
from pathlib import Path

def main():
    # Get the directory where this script is located
    script_dir = Path(__file__).parent.absolute()
    microservice_dir = script_dir / "python_document_extractors"
    
    print("🚀 Starting Document Extraction Microservice...")
    print(f"📍 Service directory: {microservice_dir}")
    print("📍 Service will run on: http://localhost:8003")
    print("📚 API Documentation: http://localhost:8003/docs")
    print("🔍 Health Check: http://localhost:8003/health")
    print("\n" + "="*60)
    
    # Check if the microservice directory exists
    if not microservice_dir.exists():
        print(f"❌ Microservice directory not found: {microservice_dir}")
        print("Please ensure the python_document_extractors folder exists.")
        return 1
    
    # Change to the microservice directory
    os.chdir(microservice_dir)
    
    # Check if main.py exists
    if not Path("main.py").exists():
        print("❌ main.py not found in the microservice directory")
        return 1
    
    # Install dependencies if needed
    print("📦 Checking dependencies...")
    try:
        import fastapi
        import uvicorn
        print("✅ Dependencies found")
    except ImportError:
        print("📦 Installing dependencies...")
        try:
            subprocess.check_call([sys.executable, "-m", "pip", "install", "-r", "requirements_fastapi.txt"])
            print("✅ Dependencies installed")
        except subprocess.CalledProcessError as e:
            print(f"❌ Failed to install dependencies: {e}")
            return 1
    
    # Start the service
    print("\n🚀 Starting FastAPI service...")
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
        return 1
    
    return 0

if __name__ == "__main__":
    sys.exit(main())

