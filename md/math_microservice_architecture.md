# Math Solver Microservice Architecture

## Overview

The Math Solver Microservice is a standalone Python FastAPI application that provides comprehensive mathematical problem-solving capabilities. It uses specialized Python libraries (SymPy, NumPy, SciPy) for exact mathematical computations and integrates with OpenAI for educational explanations.

## Architecture Diagram

```
┌─────────────────┐    HTTP/REST    ┌──────────────────────┐
│   Laravel App   │ ──────────────► │  Math Microservice   │
│                 │                 │     (FastAPI)        │
└─────────────────┘                 └──────────────────────┘
                                             │
                                             ▼
                                    ┌──────────────────────┐
                                    │   Problem Parser     │
                                    │  (Classification)    │
                                    └──────────────────────┘
                                             │
                                             ▼
                                    ┌──────────────────────┐
                                    │   Specialized        │
                                    │    Solvers           │
                                    │ ┌─────────────────┐  │
                                    │ │ Algebra Solver  │  │
                                    │ │ Calculus Solver │  │
                                    │ │ Geometry Solver │  │
                                    │ │ Stats Solver    │  │
                                    │ │ Arithmetic      │  │
                                    │ └─────────────────┘  │
                                    └──────────────────────┘
                                             │
                                             ▼
                                    ┌──────────────────────┐
                                    │   OpenAI Service     │
                                    │  (Explanations)      │
                                    └──────────────────────┘
```

## Core Components

### 1. Problem Parser (`services/problem_parser.py`)

**Purpose**: Multi-layer problem classification and routing

**Features**:
- **Layer 1**: Keyword-based classification for speed
- **Layer 2**: Mathematical expression analysis using SymPy
- **Layer 3**: Data structure analysis for statistics/arithmetic
- **Fallback**: User-provided subject area hints

**Classification Process**:
1. Extract mathematical expressions and symbols
2. Analyze problem structure and complexity
3. Identify mathematical concepts and operations
4. Route to appropriate specialized solver

### 2. Specialized Solvers (`solvers/`)

#### Algebra Solver (`algebra_solver.py`)
- **Capabilities**: Linear equations, quadratic equations, polynomial expressions
- **Library**: SymPy for symbolic mathematics
- **Step Extraction**: Manual tracking of algebraic transformations
- **Examples**: `2x + 5 = 13` → `x = 4`

#### Calculus Solver (`calculus_solver.py`)
- **Capabilities**: Derivatives, integrals, limits, differential equations
- **Library**: SymPy for symbolic calculus
- **Step Extraction**: Rule-based step tracking (power rule, chain rule, etc.)
- **Examples**: `d/dx(x^2)` → `2x`

#### Geometry Solver (`geometry_solver.py`)
- **Capabilities**: Area, volume, perimeter calculations, angle problems
- **Library**: NumPy for numerical calculations
- **Step Extraction**: Formula application and calculation steps
- **Examples**: Circle area with radius 5 → `78.54`

#### Statistics Solver (`statistics_solver.py`)
- **Capabilities**: Mean, median, mode, standard deviation, probability
- **Library**: NumPy/SciPy for statistical functions
- **Step Extraction**: Formula application and data processing steps
- **Examples**: Mean of [1,2,3,4,5] → `3.0`

#### Arithmetic Solver (`arithmetic_solver.py`)
- **Capabilities**: Basic operations, percentages, fractions, PEMDAS
- **Library**: Python built-in math and fractions
- **Step Extraction**: Order of operations and calculation steps
- **Examples**: `15 + 27` → `42`

### 3. OpenAI Service (`services/openai_service.py`)

**Purpose**: Generate educational explanations from Python solver results

**Features**:
- **Token Efficiency**: Works with pre-solved problems (70-80% token reduction)
- **Educational Focus**: Explains mathematical concepts and reasoning
- **Retry Logic**: Exponential backoff for API failures
- **Vision API**: Handles handwritten mathematical images

**Explanation Process**:
1. Receive solved problem with steps from Python solver
2. Build educational prompt with mathematical context
3. Generate explanation using OpenAI API
4. Return structured explanation with metadata

### 4. Image Processor (`services/image_processor.py`)

**Purpose**: Extract mathematical content from images

**Features**:
- **Dual OCR**: Tesseract for printed text, OpenAI Vision for handwritten
- **Confidence Scoring**: Automatic fallback based on OCR confidence
- **Image Optimization**: Contrast enhancement, resizing, format conversion
- **Auto-cleanup**: Temporary file management

**Processing Flow**:
1. Validate and optimize image
2. Try Tesseract OCR first
3. If confidence < 80%, use OpenAI Vision
4. Extract and clean mathematical expressions
5. Clean up temporary files

### 5. Solution Formatter (`services/solution_formatter.py`)

**Purpose**: Standardize API responses and LaTeX rendering

**Features**:
- **Consistent Structure**: Standardized response format
- **LaTeX Support**: Mathematical expression rendering
- **Error Handling**: Graceful error responses
- **Metadata**: Processing time, solver info, token usage

## API Endpoints

### Core Endpoints

#### `POST /solve`
- **Purpose**: Solve mathematical problems (solution + steps only)
- **Input**: Problem text/image, subject area, difficulty level
- **Output**: Solution with step-by-step breakdown
- **Performance**: Fast (no AI explanation)

#### `POST /explain`
- **Purpose**: Solve with AI explanation
- **Input**: Same as `/solve` + explanation preferences
- **Output**: Solution + steps + educational explanation
- **Performance**: Slower (includes AI processing)

#### `POST /latex`
- **Purpose**: Convert mathematical expressions to LaTeX
- **Input**: Problem text and solution
- **Output**: LaTeX formatted expressions

### Utility Endpoints

#### `GET /health`
- **Purpose**: Service health check
- **Output**: Service status, solver availability, external service status

#### `GET /solvers`
- **Purpose**: Get available solvers and capabilities
- **Output**: Solver information, capabilities, configuration

#### `POST /upload-image`
- **Purpose**: Upload and process mathematical images
- **Input**: Image file
- **Output**: Extracted mathematical text

## Data Flow

### Text Problem Flow
1. **Laravel** receives problem → validates → sends to microservice
2. **Problem Parser** classifies problem → routes to appropriate solver
3. **Specialized Solver** solves problem → extracts step-by-step solution
4. **OpenAI Service** generates educational explanation (if requested)
5. **Solution Formatter** structures response → returns to Laravel
6. **Laravel** saves to database → returns to frontend

### Image Problem Flow
1. **Laravel** receives image → validates → sends to microservice
2. **Image Processor** extracts mathematical content from image
3. **Problem Parser** classifies extracted content → routes to solver
4. **Specialized Solver** solves problem → extracts steps
5. **OpenAI Service** generates explanation (if requested)
6. **Solution Formatter** structures response → returns to Laravel
7. **Laravel** saves to database → returns to frontend

## Configuration

### Environment Variables

```bash
# OpenAI Configuration
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_MODEL=gpt-3.5-turbo
OPENAI_VISION_MODEL=gpt-4o
OPENAI_MAX_TOKENS=1500
OPENAI_TEMPERATURE=0.3

# Server Configuration
HOST=0.0.0.0
PORT=8002
RELOAD=false
LOG_LEVEL=info

# File Processing
TEMP_FILE_CLEANUP=true
TEMP_FILE_MAX_AGE=3600
MAX_FILE_SIZE=10485760

# OCR Configuration
TESSERACT_PATH=
MIN_OCR_CONFIDENCE=0.8
```

### Laravel Integration

```php
// config/services.php
'math_microservice' => [
    'url' => env('MATH_MICROSERVICE_URL', 'http://localhost:8002'),
    'timeout' => env('MATH_MICROSERVICE_TIMEOUT', 60),
],
```

## Performance Characteristics

### Token Savings
- **Traditional Approach**: 100% OpenAI tokens for solving + explanation
- **Microservice Approach**: ~20-30% OpenAI tokens (explanation only)
- **Savings**: 70-80% reduction in OpenAI costs

### Processing Times
- **Simple Arithmetic**: < 1 second
- **Algebra Problems**: 1-3 seconds
- **Calculus Problems**: 2-5 seconds
- **With AI Explanation**: +5-15 seconds

### Accuracy Improvements
- **Exact Solutions**: Python libraries provide mathematically exact results
- **Step-by-Step**: Manual step extraction ensures educational value
- **Verification**: Built-in solution verification

## Error Handling & Fallbacks

### Microservice Unavailable
- **Laravel Fallback**: Direct OpenAI solving (current behavior)
- **Logging**: Error tracking for monitoring
- **Graceful Degradation**: Service continues with reduced functionality

### Solver Failures
- **Fallback Chain**: Try multiple solvers for ambiguous problems
- **OpenAI Fallback**: Full OpenAI solution if Python solvers fail
- **Error Logging**: Problem type tracking for solver improvements

### OpenAI API Failures
- **Retry Logic**: Exponential backoff with max retries
- **Fallback Response**: Return Python solution without explanation
- **Template Explanations**: Basic explanations from templates

## Security Considerations

### API Security
- **Input Validation**: Pydantic models for request validation
- **File Upload Limits**: Size and type restrictions
- **Timeout Protection**: Prevents long-running operations

### Data Privacy
- **Temporary Files**: Auto-cleanup after processing
- **No Persistent Storage**: Microservice doesn't store user data
- **API Key Security**: Environment variable configuration

## Monitoring & Metrics

### Health Monitoring
- **Service Status**: Health check endpoint
- **Solver Availability**: Individual solver status
- **External Services**: OpenAI, Tesseract availability

### Performance Metrics
- **Processing Times**: Per-solver performance tracking
- **Token Usage**: OpenAI API usage monitoring
- **Error Rates**: Failure tracking and analysis

### Logging
- **Structured Logging**: JSON format for easy parsing
- **Error Tracking**: Detailed error information
- **Performance Logs**: Processing time and resource usage

## Deployment

### Development
```bash
# Install dependencies
python -m venv venv
source venv/bin/activate  # Linux/Mac
# or
venv\Scripts\activate     # Windows
pip install -r requirements.txt

# Configure environment
cp env.example .env
# Edit .env with your OpenAI API key

# Start service
uvicorn main:app --host 0.0.0.0 --port 8002 --reload
```

### Production
```bash
# Use production WSGI server
gunicorn main:app -w 4 -k uvicorn.workers.UvicornWorker --bind 0.0.0.0:8002
```

### Docker (Future)
```dockerfile
FROM python:3.9-slim
COPY requirements.txt .
RUN pip install -r requirements.txt
COPY . .
EXPOSE 8002
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8002"]
```

## Testing

### Unit Tests
- **Solver Tests**: Individual solver functionality
- **Service Tests**: Component integration
- **API Tests**: Endpoint functionality

### Integration Tests
- **End-to-End**: Complete problem solving flow
- **Laravel Integration**: Microservice communication
- **Error Handling**: Failure scenarios

### Performance Tests
- **Load Testing**: Concurrent request handling
- **Memory Usage**: Resource consumption monitoring
- **Response Times**: Performance benchmarking

## Future Enhancements

### Planned Features
- **Redis Caching**: Solution caching for repeated problems
- **Rate Limiting**: API usage controls
- **Batch Processing**: Multiple problem solving
- **Webhook Support**: Async processing notifications

### Advanced Capabilities
- **Graph Theory**: Network analysis and graph algorithms
- **Linear Algebra**: Matrix operations and systems
- **Differential Equations**: Advanced calculus problems
- **Optimization**: Mathematical optimization problems

### Scalability
- **Horizontal Scaling**: Multiple microservice instances
- **Load Balancing**: Request distribution
- **Queue System**: Async processing for heavy computations
- **Database Integration**: Solution persistence and analytics








