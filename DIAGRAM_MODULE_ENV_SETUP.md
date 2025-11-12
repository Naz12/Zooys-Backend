# Diagram Module - Environment Variables Setup

## ⚡ Quick Setup

Add these lines to your `.env` file:

```env
# ===========================================
# Diagram Microservice Configuration
# ===========================================

# Microservice URL (Local Development)
DIAGRAM_MICROSERVICE_URL=http://localhost:8005

# API Key for authentication
DIAGRAM_MICROSERVICE_API_KEY=diagram-service-api-key-12345

# Request timeout in seconds (default: 120)
DIAGRAM_MICROSERVICE_TIMEOUT=120
```

---

## 📝 Instructions

### **Step 1: Open your `.env` file**
```bash
# In your project root
notepad .env
# or
code .env
```

### **Step 2: Scroll to the bottom**

### **Step 3: Add the variables**
Copy the entire block above (from `# Diagram Microservice Configuration` to the end) and paste it at the bottom of your `.env` file.

### **Step 4: Update the API key (if needed)**
If your microservice uses a different API key, update it:
```env
DIAGRAM_MICROSERVICE_API_KEY=your-actual-api-key-here
```

### **Step 5: Save the file**

### **Step 6: Clear config cache**
```bash
php artisan config:clear
php artisan cache:clear
```

---

## 🔧 Configuration Details

### **DIAGRAM_MICROSERVICE_URL**
- **Local Development**: `http://localhost:8005`
- **Production**: Update to your production microservice URL
- Base URL for the Diagram Generation Microservice

### **DIAGRAM_MICROSERVICE_API_KEY**
- **Default**: `diagram-service-api-key-12345`
- API key for authenticating requests to the microservice
- **KEEP THIS SECURE** - Never commit to version control
- Used in `X-API-Key` header for all microservice requests

### **DIAGRAM_MICROSERVICE_TIMEOUT**
- **Default**: `120` seconds (2 minutes)
- HTTP timeout for microservice requests
- Increase for large/complex diagrams
- Maximum recommended: 300 seconds (5 minutes)

---

## 🧪 Test Configuration

After adding the variables, test the connection:

```bash
# Test via tinker
php artisan tinker
```

```php
$diagramService = app(\App\Services\AIDiagramService::class);
$available = $diagramService->isMicroserviceAvailable();
// Should return: true
```

Or test via API:

```bash
curl -X GET http://localhost:8000/api/diagram/health \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response:**
```json
{
  "success": true,
  "available": true,
  "message": "Microservice is available"
}
```

---

## ⚠️ Security Notes

1. **Never commit** `.env` file to version control
2. **Rotate API keys** regularly in production
3. **Use different keys** for development and production
4. **Restrict network access** to the microservice (internal only if possible)

---

## 📋 Supported Diagram Types

### **Graph-based Diagrams** (Graphviz)
- `flowchart` - Flow charts
- `sequence` - Sequence diagrams
- `class` - Class diagrams
- `state` - State diagrams
- `er` - Entity-relationship diagrams
- `user_journey` - User journey maps
- `block` - Block diagrams
- `mindmap` - Mind maps

### **Chart-based Diagrams** (matplotlib/plotly)
- `pie` - Pie charts
- `quadrant` - Quadrant charts
- `timeline` - Timeline charts
- `sankey` - Sankey diagrams
- `xy` - XY scatter/line charts

---

## 🚀 Quick Start

1. **Copy the variables** into your `.env` file
2. **Update API key** if different from default
3. **Verify URL** matches your microservice deployment
4. **Clear config cache**: `php artisan config:clear`
5. **Test connection**: Use health endpoint or tinker

---

## ✅ Verification

After adding variables, verify configuration:

```bash
php artisan tinker
```

```php
config('services.diagram_microservice.url')
// Should output: http://localhost:8005

config('services.diagram_microservice.api_key')
// Should output: diagram-service-api-key-12345
```

---

**Last Updated:** November 2025  
**Status:** ✅ Ready to Use

