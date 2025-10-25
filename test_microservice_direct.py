#!/usr/bin/env python3
"""
Direct test of the Document Extraction Microservice
This script tests the microservice without Laravel dependencies
"""

import requests
import json
import os
import sys

def test_microservice():
    base_url = "http://localhost:8003"
    
    print("=== Document Extraction Microservice Test ===\n")
    
    # Test 1: Health Check
    print("1. Testing Health Check...")
    try:
        response = requests.get(f"{base_url}/health", timeout=5)
        if response.status_code == 200:
            data = response.json()
            print(f"✅ Microservice is healthy")
            print(f"   Status: {data.get('status', 'unknown')}")
            print(f"   Version: {data.get('version', 'unknown')}")
        else:
            print(f"❌ Health check failed: HTTP {response.status_code}")
    except requests.exceptions.RequestException as e:
        print(f"❌ Cannot connect to microservice: {e}")
        return False
    print()
    
    # Test 2: Service Info
    print("2. Testing Service Info...")
    try:
        response = requests.get(f"{base_url}/", timeout=5)
        if response.status_code == 200:
            data = response.json()
            print(f"✅ Service info retrieved")
            print(f"   Service: {data.get('service', 'unknown')}")
            print(f"   Version: {data.get('version', 'unknown')}")
        else:
            print(f"❌ Service info failed: HTTP {response.status_code}")
    except requests.exceptions.RequestException as e:
        print(f"❌ Cannot get service info: {e}")
    print()
    
    # Test 3: PDF Extraction
    print("3. Testing PDF Extraction...")
    pdf_file = "test files/test.pdf"
    if os.path.exists(pdf_file):
        try:
            response = requests.post(
                f"{base_url}/extract",
                data={
                    'file_path': os.path.abspath(pdf_file),
                    'file_type': 'pdf',
                    'options': json.dumps({'language': 'en'})
                },
                timeout=30
            )
            
            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    print(f"✅ PDF extraction successful")
                    print(f"   Word count: {data.get('word_count', 0)}")
                    print(f"   Character count: {data.get('character_count', 0)}")
                    print(f"   Text preview: {data.get('text', '')[:100]}...")
                else:
                    print(f"❌ PDF extraction failed: {data.get('error', 'Unknown error')}")
            else:
                print(f"❌ HTTP Error: {response.status_code}")
                print(f"   Response: {response.text}")
        except requests.exceptions.RequestException as e:
            print(f"❌ PDF extraction request failed: {e}")
    else:
        print(f"❌ Test PDF file not found: {pdf_file}")
    print()
    
    print("=== Test Complete ===")
    return True

if __name__ == "__main__":
    test_microservice()


