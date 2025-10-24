#!/usr/bin/env python3
# Python PDF Text Extractor Service
# Extracts text from PDF files using multiple Python libraries for better accuracy
import sys
import json
import os

try:
    import pdfplumber
    import fitz # PyMuPDF
    import PyPDF2
    from PIL import Image
    import pytesseract
except ImportError as e:
    # When imported as module, don't exit, just raise the error
    if __name__ == "__main__":
        print(json.dumps({"success": False, "error": f"Missing required Python packages: {str(e)}", "text": "", "pages": 0, "metadata": {}, "word_count": 0, "character_count": 0}))
        sys.exit(1)
    else:
        raise ImportError(f"Missing required Python packages: {str(e)}")

def extract_text_with_pdfplumber(filepath):
    """Extract text using pdfplumber (best for tables and structured content)"""
    try:
        text = ""
        pages = 0
        with pdfplumber.open(filepath) as pdf:
            pages = len(pdf.pages)
            for page in pdf.pages:
                page_text = page.extract_text()
                if page_text:
                    text += page_text + "\n"
        return {"text": text, "pages": pages, "method": "pdfplumber"}
    except Exception as e:
        return {"text": "", "pages": 0, "method": "pdfplumber", "error": str(e)}

def extract_text_with_pymupdf(filepath):
    """Extract text using PyMuPDF (fast and reliable)"""
    try:
        import fitz
        text = ""
        pages = 0
        doc = fitz.open(filepath)
        pages = len(doc)
        for page_num in range(pages):
            page = doc[page_num]
            page_text = page.get_text()
            if page_text:
                text += page_text + "\n"
        doc.close()
        return {"text": text, "pages": pages, "method": "pymupdf"}
    except Exception as e:
        return {"text": "", "pages": 0, "method": "pymupdf", "error": str(e)}

def extract_text_with_pypdf2(filepath):
    """Extract text using PyPDF2 (fallback method)"""
    try:
        text = ""
        pages = 0
        with open(filepath, 'rb') as file:
            pdf_reader = PyPDF2.PdfReader(file)
            pages = len(pdf_reader.pages)
            for page in pdf_reader.pages:
                page_text = page.extract_text()
                if page_text:
                    text += page_text + "\n"
        return {"text": text, "pages": pages, "method": "pypdf2"}
    except Exception as e:
        return {"text": "", "pages": 0, "method": "pypdf2", "error": str(e)}

def extract_text_with_ocr(filepath):
    """Extract text using OCR (for scanned PDFs)"""
    try:
        import fitz
        text = ""
        pages = 0
        doc = fitz.open(filepath)
        pages = len(doc)
        
        for page_num in range(pages):
            page = doc[page_num]
            # Convert page to image
            pix = page.get_pixmap()
            img_data = pix.tobytes("png")
            
            # Use pytesseract for OCR
            from io import BytesIO
            image = Image.open(BytesIO(img_data))
            page_text = pytesseract.image_to_string(image)
            if page_text:
                text += page_text + "\n"
        
        doc.close()
        return {"text": text, "pages": pages, "method": "ocr"}
    except Exception as e:
        return {"text": "", "pages": 0, "method": "ocr", "error": str(e)}

def extract_text_from_pdf(filepath):
    """Extract text from PDF using multiple methods for best results"""
    results = []
    
    # Try different extraction methods
    methods = [
        extract_text_with_pdfplumber,
        extract_text_with_pymupdf,
        extract_text_with_pypdf2
    ]
    
    for method in methods:
        result = method(filepath)
        results.append(result)
        
        # If we got good text, use it
        if result["text"].strip() and len(result["text"].strip()) > 50:
            break
    
    # If no method worked well, try OCR
    if not any(len(r["text"].strip()) > 50 for r in results):
        ocr_result = extract_text_with_ocr(filepath)
        results.append(ocr_result)
    
    # Choose the best result
    best_result = max(results, key=lambda x: len(x["text"].strip()))
    
    # Get metadata
    metadata = {}
    try:
        import fitz
        doc = fitz.open(filepath)
        metadata = doc.metadata
        doc.close()
    except:
        pass
    
    # Count words and characters
    text = best_result["text"]
    word_count = len(text.split())
    character_count = len(text)
    
    return {
        "success": True,
        "text": text,
        "pages": best_result["pages"],
        "metadata": metadata,
        "word_count": word_count,
        "character_count": character_count,
        "extraction_method": best_result["method"],
        "all_methods": [{"method": r["method"], "text_length": len(r["text"]), "error": r.get("error")} for r in results]
    }

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "No file path provided", "text": "", "pages": 0, "metadata": {}, "word_count": 0, "character_count": 0}))
        sys.exit(1)
    
    pdf_filepath = sys.argv[1]
    if not os.path.exists(pdf_filepath):
        print(json.dumps({"success": False, "error": f"File not found: {pdf_filepath}", "text": "", "pages": 0, "metadata": {}, "word_count": 0, "character_count": 0}))
        sys.exit(1)
    
    result = extract_text_from_pdf(pdf_filepath)
    print(json.dumps(result, indent=2))
