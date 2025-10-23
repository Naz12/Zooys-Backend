# 🚀 Postman Testing Guide for Text Summarization Endpoint

## 🔑 **Authentication Token**
```
178|llkCJsMgmEs3ObYQio1xHjzyRRrct30R6mE8a3ae14717449
```

## 📋 **Step-by-Step Postman Setup**

### **1. Create New Request**
- Open Postman
- Click "New" → "Request"
- Name: "Text Summarization Test"

### **2. Configure Request**
- **Method:** `POST`
- **URL:** `http://localhost:8000/api/summarize/async/text`

### **3. Set Headers**
Go to **Headers** tab and add:

| Key | Value |
|-----|-------|
| `Authorization` | `Bearer 178|llkCJsMgmEs3ObYQio1xHjzyRRrct30R6mE8a3ae14717449` |
| `Content-Type` | `application/json` |
| `Accept` | `application/json` |

### **4. Set Request Body**
Go to **Body** tab:
- Select **"raw"**
- Select **"JSON"** from dropdown
- Add this JSON payload:

```json
{
    "text": "Born into a wealthy family in New York City, Trump graduated from the University of Pennsylvania in 1968 with a bachelor's degree in economics. He became the president of his family's real estate business in 1971, renamed it the Trump Organization, and began acquiring and building skyscrapers, hotels, casinos, and golf courses. He launched side ventures, many licensing the Trump name, and filed for six business bankruptcies in the 1990s and 2000s",
    "options": {
        "format": "detailed",
        "language": "en",
        "focus": "summary"
    }
}
```

### **5. Send Request**
Click **"Send"** button

## 📊 **Expected Response**

### **Initial Response (HTTP 202):**
```json
{
    "success": true,
    "message": "Job created successfully",
    "job_id": "479cbb8b-8ddc-4f46-a213-9ecb451cdd9b",
    "status": "pending"
}
```

## 🔄 **Testing Job Status**

### **1. Check Job Status**
- **Method:** `GET`
- **URL:** `http://localhost:8000/api/summarize/status/{job_id}`
- **Headers:** Same as above (Authorization required)

**Example:**
```
GET http://localhost:8000/api/summarize/status/479cbb8b-8ddc-4f46-a213-9ecb451cdd9b
```

**Status Response:**
```json
{
    "job_id": "479cbb8b-8ddc-4f46-a213-9ecb451cdd9b",
    "status": "running",
    "progress": 75,
    "stage": "processing",
    "error": null
}
```

### **2. Get Final Result**
- **Method:** `GET`
- **URL:** `http://localhost:8000/api/summarize/result/{job_id}`
- **Headers:** Same as above (Authorization required)

**Example:**
```
GET http://localhost:8000/api/summarize/result/479cbb8b-8ddc-4f46-a213-9ecb451cdd9b
```

**Result Response:**
```json
{
    "success": true,
    "data": {
        "summary": "Donald Trump became a real estate magnate after taking over his family's business, renamed it the Trump Organization, and began acquiring and building skyscrapers, hotels, casinos, and golf courses. He launched side ventures, many licensing the Trump name, and filed for six business bankruptcies in the 1990s and 2000s",
        "key_points": [
            "Born into New York City's elite",
            "Graduated from Penn University in '68 with an economics degree",
            "Took the helm at Trump Organization, renamed and expanded internationally via skyscrapers & more"
        ],
        "confidence_score": 0.8,
        "model_used": "ollama:phi3:mini"
    }
}
```

## 🎯 **Complete Testing Workflow**

### **Step 1: Start Text Summarization**
```bash
POST http://localhost:8000/api/summarize/async/text
Authorization: Bearer 178|llkCJsMgmEs3ObYQio1xHjzyRRrct30R6mE8a3ae14717449
Content-Type: application/json

{
    "text": "Your text here...",
    "options": {
        "format": "detailed",
        "language": "en",
        "focus": "summary"
    }
}
```

### **Step 2: Poll Job Status (Repeat until completed)**
```bash
GET http://localhost:8000/api/summarize/status/{job_id}
Authorization: Bearer 178|llkCJsMgmEs3ObYQio1xHjzyRRrct30R6mE8a3ae14717449
```

### **Step 3: Get Final Result**
```bash
GET http://localhost:8000/api/summarize/result/{job_id}
Authorization: Bearer 178|llkCJsMgmEs3ObYQio1xHjzyRRrct30R6mE8a3ae14717449
```

## ⚠️ **Important Notes**

1. **Queue Worker Must Be Running:**
   ```bash
   php artisan queue:work --daemon
   ```

2. **Job Processing Time:** Usually 10-30 seconds

3. **Status Values:**
   - `pending` → `running` → `completed`
   - `failed` (if error occurs)

4. **Progress Values:**
   - `0%` → `25%` → `50%` → `75%` → `100%`

## 🚨 **Troubleshooting**

### **If Job Stays "pending":**
- Check if queue worker is running
- Run: `php artisan queue:work --once`

### **If Authentication Fails:**
- Verify token is correct
- Check Authorization header format: `Bearer {token}`

### **If Job Fails:**
- Check the `error` field in status response
- Common issues: AI Manager unavailable, network timeouts

## 🎉 **Success Indicators**

✅ **HTTP 202** response from initial request  
✅ **Job status** progresses from pending → running → completed  
✅ **Final result** contains AI-generated summary and key points  
✅ **Confidence score** and model information included  

**The text endpoint is fully functional!** 🚀


