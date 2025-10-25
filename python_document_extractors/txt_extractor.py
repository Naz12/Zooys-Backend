#!/usr/bin/env python3
# Python Text File Extractor Service
# Extracts text from plain text files (.txt) with encoding detection
import sys
import json
import os
import chardet

def detect_encoding(filepath):
    """Detect file encoding"""
    try:
        with open(filepath, 'rb') as file:
            raw_data = file.read()
            result = chardet.detect(raw_data)
            return result['encoding']
    except Exception as e:
        return 'utf-8'  # Default fallback

def extract_text_with_encoding(filepath, encoding):
    """Extract text with specific encoding"""
    try:
        with open(filepath, 'r', encoding=encoding) as file:
            text = file.read()
        return {"text": text, "encoding": encoding, "success": True}
    except Exception as e:
        return {"text": "", "encoding": encoding, "success": False, "error": str(e)}

def extract_text_from_txt(filepath):
    """Extract text from plain text file with encoding detection"""
    # Detect encoding
    detected_encoding = detect_encoding(filepath)
    
    # Try different encodings
    encodings_to_try = [detected_encoding, 'utf-8', 'utf-16', 'latin-1', 'cp1252', 'iso-8859-1']
    results = []
    
    for encoding in encodings_to_try:
        if encoding:
            result = extract_text_with_encoding(filepath, encoding)
            results.append(result)
            
            # If we got good text, use it
            if result["success"] and result["text"].strip():
                break
    
    # Choose the best result
    successful_results = [r for r in results if r["success"] and r["text"].strip()]
    if not successful_results:
        return {
            "success": False,
            "text": "",
            "lines": 0,
            "metadata": {},
            "word_count": 0,
            "character_count": 0,
            "error": "Could not extract text with any encoding"
        }
    
    best_result = max(successful_results, key=lambda x: len(x["text"].strip()))
    
    # Get file metadata
    try:
        stat = os.stat(filepath)
        metadata = {
            "file_size": stat.st_size,
            "encoding": best_result["encoding"],
            "detected_encoding": detected_encoding
        }
    except:
        metadata = {"encoding": best_result["encoding"]}
    
    # Count words, characters, and lines
    text = best_result["text"]
    lines = len(text.split('\n'))
    word_count = len(text.split())
    character_count = len(text)
    
    return {
        "success": True,
        "text": text,
        "lines": lines,
        "metadata": metadata,
        "word_count": word_count,
        "character_count": character_count,
        "extraction_method": f"text-{best_result['encoding']}",
        "all_encodings_tried": [{"encoding": r["encoding"], "success": r["success"], "text_length": len(r["text"]), "error": r.get("error")} for r in results]
    }

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "No file path provided", "text": "", "lines": 0, "metadata": {}, "word_count": 0, "character_count": 0}))
        sys.exit(1)
    
    txt_filepath = sys.argv[1]
    if not os.path.exists(txt_filepath):
        print(json.dumps({"success": False, "error": f"File not found: {txt_filepath}", "text": "", "lines": 0, "metadata": {}, "word_count": 0, "character_count": 0}))
        sys.exit(1)
    
    result = extract_text_from_txt(txt_filepath)
    print(json.dumps(result, indent=2))


