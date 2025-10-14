#!/usr/bin/env python3
"""
Test the expression cleaning function
"""

import re

def _clean_math_expression(text: str) -> str:
    """Clean the extracted text to get just the mathematical expression"""
    
    # Remove common prefixes and suffixes
    text = text.strip()
    
    # Remove LaTeX formatting
    text = re.sub(r'\\\[', '', text)  # Remove \[
    text = re.sub(r'\\\]', '', text)  # Remove \]
    text = re.sub(r'\\div', '/', text)  # Replace \div with /
    
    # Handle fractions - try multiple patterns
    text = re.sub(r'\\frac\{([^}]+)\}\{([^}]+)\}', r'(\1)/(\2)', text)  # Replace \frac{a}{b} with (a)/(b)
    text = re.sub(r'\\frac\{([^}]+)\}\{([^}]+)\}', r'(\1)/(\2)', text)  # Replace \frac{a}{b} with (a)/(b)
    text = re.sub(r'\\frac\{([^}]+)\}\{([^}]+)\}', r'(\1)/(\2)', text)  # Replace \frac{a}{b} with (a)/(b)
    
    # Handle any remaining LaTeX commands
    text = re.sub(r'\\[a-zA-Z]+\{[^}]*\}', '', text)  # Remove any remaining LaTeX commands
    
    # Remove common prefixes
    prefixes_to_remove = [
        "The mathematical expression in the image is:",
        "The expression is:",
        "The equation is:",
        "The problem is:",
        "The math problem is:",
        "The mathematical problem is:",
        "The expression:",
        "The equation:",
        "The problem:",
        "The math problem:",
        "The mathematical problem:",
    ]
    
    for prefix in prefixes_to_remove:
        if text.lower().startswith(prefix.lower()):
            text = text[len(prefix):].strip()
            break
    
    # Remove any remaining non-math text
    lines = text.split('\n')
    for line in lines:
        line = line.strip()
        # Look for lines that contain mathematical symbols
        if any(char in line for char in ['+', '-', '*', '/', '=', '(', ')', '^', 'x', 'y', 'z']):
            text = line
            break
    
    # Clean up extra whitespace
    text = re.sub(r'\s+', ' ', text).strip()
    
    print(f"Cleaned expression: '{text}' from original: '{text[:50]}...'")
    return text

# Test with the problematic text
test_text = """The mathematical expression in the image is:
\[ 9 - 3 \div \frac{1}{3} + 1"""

print("Testing expression cleaning...")
print(f"Original: {test_text}")
result = _clean_math_expression(test_text)
print(f"Result: {result}")
