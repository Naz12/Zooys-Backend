# Math Solver Microservice Setup Guide

## Prerequisites

### System Requirements
- **Python**: 3.8 or higher
- **Operating System**: Windows, macOS, or Linux
- **Memory**: Minimum 2GB RAM (4GB recommended)
- **Storage**: 1GB free space for dependencies

### Required Software
- **Python 3.8+**: [Download from python.org](https://www.python.org/downloads/)
- **Git**: [Download from git-scm.com](https://git-scm.com/downloads)
- **Tesseract OCR** (Optional): [Download from GitHub](https://github.com/UB-Mannheim/tesseract/wiki)

### API Keys
- **OpenAI API Key**: [Get from OpenAI](https://platform.openai.com/api-keys)

## Installation Steps

### 1. Clone or Download the Project

If you have the project files, navigate to the `python_math_service` directory:

```bash
cd python_math_service
```

### 2. Create Virtual Environment

**Windows:**
```cmd
python -m venv venv
venv\Scripts\activate
```

**macOS/Linux:**
```bash
python3 -m venv venv
source venv/bin/activate
```

### 3. Install Dependencies

```bash
pip install --upgrade pip
pip install -r requirements.txt
```

### 4. Configure Environment

**Copy the example environment file:**
```bash
# Windows
copy env.example .env

# macOS/Linux
cp env.example .env
```

**Edit the `.env` file with your configuration:**
```bash
# Required: OpenAI API Key
OPENAI_API_KEY=your_openai_api_key_here

# Optional: Customize settings
OPENAI_MODEL=gpt-3.5-turbo
OPENAI_VISION_MODEL=gpt-4o
PORT=8002
```

### 5. Install Tesseract OCR (Optional)

**Windows:**
1. Download Tesseract from [GitHub releases](https://github.com/UB-Mannheim/tesseract/wiki)
2. Install the executable
3. Add Tesseract to your PATH or set `TESSERACT_PATH` in `.env`

**macOS:**
```bash
brew install tesseract
```

**Linux (Ubuntu/Debian):**
```bash
sudo apt-get install tesseract-ocr
```

### 6. Test the Installation

**Run the test suite:**
```bash
python test_solvers.py
```

**Start the microservice:**
```bash
python main.py
```

The service should start on `http://localhost:8002`

## Laravel Integration Setup

### 1. Update Laravel Configuration

**Add to your Laravel `.env` file:**
```bash
# Math Microservice Configuration
MATH_MICROSERVICE_URL=http://localhost:8002
MATH_MICROSERVICE_TIMEOUT=60
```

**The `config/services.php` file should already be updated with:**
```php
'math_microservice' => [
    'url' => env('MATH_MICROSERVICE_URL', 'http://localhost:8002'),
    'timeout' => env('MATH_MICROSERVICE_TIMEOUT', 60),
],
```

### 2. Test Laravel Integration

**Run the integration test:**
```bash
php test/test_math_microservice.php
```

### 3. Start All Services

**Use the updated startup script:**
```bash
# Windows
start_both_servers.bat

# This will start:
# - Laravel: http://localhost:8000
# - Presentation Service: http://localhost:8001  
# - Math Service: http://localhost:8002
```

## Quick Start Scripts

### Windows Setup

**1. Run the installation script:**
```cmd
cd python_math_service
install_deps.bat
```

**2. Configure your environment:**
- Copy `env.example` to `.env`
- Add your OpenAI API key to `.env`

**3. Start the service:**
```cmd
start.bat
```

### Manual Setup

**1. Create virtual environment:**
```bash
python -m venv venv
```

**2. Activate virtual environment:**
```bash
# Windows
venv\Scripts\activate

# macOS/Linux
source venv/bin/activate
```

**3. Install dependencies:**
```bash
pip install -r requirements.txt
```

**4. Configure environment:**
```bash
cp env.example .env
# Edit .env with your OpenAI API key
```

**5. Start the service:**
```bash
uvicorn main:app --host 0.0.0.0 --port 8002 --reload
```

## Verification

### 1. Health Check

Visit `http://localhost:8002/health` in your browser or use curl:

```bash
curl http://localhost:8002/health
```

Expected response:
```json
{
  "status": "healthy",
  "services": {
    "solvers": {
      "algebra": true,
      "calculus": true,
      "geometry": true,
      "statistics": true,
      "arithmetic": true
    },
    "external_services": {
      "openai": true,
      "tesseract": true
    }
  }
}
```

### 2. Test Problem Solving

**Test with curl:**
```bash
curl -X POST http://localhost:8002/solve \
  -H "Content-Type: application/json" \
  -d '{
    "problem_text": "2x + 5 = 13",
    "subject_area": "algebra",
    "difficulty_level": "intermediate"
  }'
```

**Expected response:**
```json
{
  "success": true,
  "solution": {
    "answer": "x = 4",
    "method": "algebraic_solving",
    "steps": [
      {
        "step_number": 1,
        "description": "Subtract 5 from both sides",
        "expression": "2x = 8"
      },
      {
        "step_number": 2,
        "description": "Divide by 2",
        "expression": "x = 4"
      }
    ]
  }
}
```

### 3. Test with Explanation

```bash
curl -X POST http://localhost:8002/explain \
  -H "Content-Type: application/json" \
  -d '{
    "problem_text": "2x + 5 = 13",
    "subject_area": "algebra",
    "difficulty_level": "intermediate",
    "include_explanation": true
  }'
```

## Troubleshooting

### Common Issues

#### 1. OpenAI API Key Not Working
**Error**: `OpenAI service not available`

**Solution**:
- Verify your API key is correct in `.env`
- Check if you have sufficient OpenAI credits
- Ensure the API key has the correct permissions

#### 2. Tesseract Not Found
**Error**: `Tesseract OCR not available`

**Solution**:
- Install Tesseract OCR
- Add Tesseract to your system PATH
- Or set `TESSERACT_PATH` in `.env`

#### 3. Port Already in Use
**Error**: `Port 8002 is already in use`

**Solution**:
- Change the port in `.env`: `PORT=8003`
- Or stop the service using the port
- Check for other running instances

#### 4. Dependencies Installation Failed
**Error**: `Failed to install dependencies`

**Solution**:
- Update pip: `pip install --upgrade pip`
- Use Python 3.8 or higher
- Check your internet connection
- Try installing dependencies individually

#### 5. Virtual Environment Issues
**Error**: `Virtual environment not found`

**Solution**:
- Recreate virtual environment: `python -m venv venv`
- Activate properly: `venv\Scripts\activate` (Windows) or `source venv/bin/activate` (macOS/Linux)
- Ensure Python is in your PATH

### Debug Mode

**Enable debug logging:**
```bash
# Set in .env
LOG_LEVEL=debug

# Or start with debug
uvicorn main:app --host 0.0.0.0 --port 8002 --reload --log-level debug
```

### Performance Issues

**If the service is slow:**
- Check OpenAI API response times
- Monitor memory usage
- Consider upgrading hardware
- Optimize problem complexity

## Production Deployment

### Environment Variables for Production

```bash
# Production settings
HOST=0.0.0.0
PORT=8002
RELOAD=false
LOG_LEVEL=info

# Security
OPENAI_API_KEY=your_production_key
MAX_FILE_SIZE=5242880  # 5MB limit

# Performance
TEMP_FILE_CLEANUP=true
TEMP_FILE_MAX_AGE=1800  # 30 minutes
```

### Using Gunicorn (Production)

```bash
# Install gunicorn
pip install gunicorn

# Start with gunicorn
gunicorn main:app -w 4 -k uvicorn.workers.UvicornWorker --bind 0.0.0.0:8002
```

### Docker Deployment (Future)

```dockerfile
FROM python:3.9-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install -r requirements.txt

COPY . .

EXPOSE 8002

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8002"]
```

## Support

### Getting Help

1. **Check the logs**: Look at the console output for error messages
2. **Run tests**: Use `python test_solvers.py` to verify functionality
3. **Check configuration**: Verify your `.env` file settings
4. **Test endpoints**: Use the health check and test endpoints

### Useful Commands

```bash
# Check Python version
python --version

# Check installed packages
pip list

# Test specific solver
python -c "from solvers.algebra_solver import AlgebraSolver; print(AlgebraSolver().solve_with_timeout('2x + 5 = 13'))"

# Check service status
curl http://localhost:8002/health

# View API documentation
# Visit http://localhost:8002/docs in your browser
```

### Log Files

The service logs to the console by default. For production, consider redirecting to log files:

```bash
# Redirect logs to file
uvicorn main:app --host 0.0.0.0 --port 8002 > math_service.log 2>&1
```








