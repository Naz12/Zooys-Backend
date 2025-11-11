# Postman Collections Guide - Zooys Presentation & Math Modules

## 📦 Files Created

1. **ZOOYS_PRESENTATION_POSTMAN_COLLECTION.json** - Presentation module API collection
2. **ZOOYS_PRESENTATION_POSTMAN_ENVIRONMENT.json** - Presentation module environment variables
3. **ZOOYS_MATH_POSTMAN_COLLECTION.json** - Math module API collection
4. **ZOOYS_MATH_POSTMAN_ENVIRONMENT.json** - Math module environment variables

---

## 🚀 How to Import

### **Step 1: Import Collections**

1. Open Postman
2. Click **Import** button (top left)
3. Select **Files** tab
4. Choose:
   - `ZOOYS_PRESENTATION_POSTMAN_COLLECTION.json`
   - `ZOOYS_MATH_POSTMAN_COLLECTION.json`
5. Click **Import**

### **Step 2: Import Environments**

1. Click **Environments** (left sidebar)
2. Click **Import** button
3. Choose:
   - `ZOOYS_PRESENTATION_POSTMAN_ENVIRONMENT.json`
   - `ZOOYS_MATH_POSTMAN_ENVIRONMENT.json`
4. Click **Import**

### **Step 3: Select Environment**

1. Click the environment dropdown (top right)
2. Select:
   - **Zooys Presentation Environment** (for presentation requests)
   - **Zooys Math Environment** (for math requests)

---

## ⚙️ Environment Variables

### **Presentation Environment Variables**

| Variable | Description | Default Value |
|----------|-------------|---------------|
| `base_url` | API base URL | `http://localhost:8000/api` |
| `auth_token` | Bearer token for authentication | (empty - set your token) |
| `ai_result_id` | Presentation result ID | (empty - set after creating) |
| `file_id` | Uploaded file ID | (empty - set after uploading) |
| `job_id` | Job ID for async operations | (empty - set from response) |
| `filename` | Presentation filename | (empty - set from export) |
| `presentation_microservice_url` | Presentation microservice URL | `http://localhost:8001` |
| `presentation_microservice_api_key` | Presentation microservice API key | (empty - set your key) |

### **Math Environment Variables**

| Variable | Description | Default Value |
|----------|-------------|---------------|
| `base_url` | API base URL | `http://localhost:8000/api` |
| `auth_token` | Bearer token for authentication | (empty - set your token) |
| `problem_id` | Math problem ID | (empty - set after creating) |
| `file_id` | Uploaded file ID | (empty - set after uploading) |
| `job_id` | Job ID for async operations | (empty - set from response) |
| `math_microservice_url` | Math microservice URL | `http://localhost:8002` |
| `math_microservice_api_key` | Math microservice API key | (empty - set your key) |

---

## 📋 Collection Structure

### **Presentation Collection**

#### **Templates**
- Get Templates

#### **Outline Generation**
- Generate Outline (Text)
- Generate Outline (File)
- Generate Outline (URL)
- Generate Outline (YouTube)
- Update Outline

#### **Content Generation**
- Generate Content

#### **Export & Download**
- Export Presentation
- Generate PowerPoint
- Download Presentation

#### **Management**
- List Presentations
- Get Presentation Data
- Get Presentation
- Save Presentation
- Delete Presentation

#### **Status & Health**
- Get Progress Status
- Check Microservice Status
- Get Text Job Status
- Get Text Job Result
- Get File Job Status
- Get File Job Result

---

### **Math Collection**

#### **Solve Problems**
- Solve Math Problem (Text)
- Solve Math Problem (Image)
- Solve Word Problem

#### **Client API (Aliases)**
- Generate Math Solution
- Get Math Help

#### **Problem Management**
- List Math Problems
- Get Math Problem
- Delete Math Problem

#### **History & Statistics**
- Get Math History
- Get Math History (Client)
- Get Math Statistics
- Get Math Statistics (Client)

#### **Job Status & Results**
- Get Text Job Status
- Get Text Job Result
- Get Image Job Status
- Get Image Job Result

---

## 🔧 Setup Instructions

### **1. Update Base URL**

If your API is not running on `localhost:8000`, update the `base_url` variable:

1. Select the environment
2. Click **Edit** (or double-click the variable)
3. Update the value
4. Click **Save**

### **2. Set Authentication Token**

1. Login to get your Bearer token
2. In the environment, set `auth_token` to your token
3. Token will be automatically added to requests that require authentication

### **3. Set Microservice API Keys**

1. Get your microservice API keys
2. Set:
   - `presentation_microservice_api_key` (for Presentation environment)
   - `math_microservice_api_key` (for Math environment)

---

## 📝 Usage Examples

### **Example 1: Generate Presentation Outline**

1. Select **Zooys Presentation Environment**
2. Go to **Outline Generation** → **Generate Outline (Text)**
3. Update request body with your topic
4. Click **Send**
5. Copy `ai_result_id` from response to environment variable

### **Example 2: Solve Math Problem**

1. Select **Zooys Math Environment**
2. Go to **Solve Problems** → **Solve Math Problem (Text)**
3. Update request body with your problem
4. Click **Send**
5. For image problems, copy `job_id` from response to check status

### **Example 3: Check Job Status**

1. After submitting an async job, copy the `job_id` from response
2. Set `job_id` in environment variables
3. Go to **Job Status & Results** → **Get Image Job Status**
4. Click **Send** to check progress

---

## 🔄 Workflow Examples

### **Presentation Workflow**

1. **Get Templates** → See available templates
2. **Generate Outline (Text)** → Create outline
3. Copy `ai_result_id` from response
4. **Generate Content** → Add content to slides
5. **Export Presentation** → Create PowerPoint file
6. **Download Presentation** → Download the file

### **Math Workflow (Text)**

1. **Solve Math Problem (Text)** → Get immediate solution
2. View solution with steps and explanation

### **Math Workflow (Image)**

1. **Solve Math Problem (Image)** → Submit image (returns `job_id`)
2. Copy `job_id` from response
3. **Get Image Job Status** → Check processing status
4. **Get Image Job Result** → Get solution when completed

---

## 💡 Tips

1. **Use Variables**: Always use environment variables instead of hardcoding values
2. **Save Responses**: Use Postman's "Save Response" feature to save example responses
3. **Tests**: Add tests to automatically extract IDs from responses
4. **Pre-request Scripts**: Use scripts to automatically set variables
5. **Folders**: Organize requests in folders for better navigation

---

## 🐛 Troubleshooting

### **401 Unauthorized**
- Check that `auth_token` is set correctly
- Verify token hasn't expired
- Ensure token format: `Bearer {token}`

### **404 Not Found**
- Verify `base_url` is correct
- Check that endpoint path is correct
- Ensure API server is running

### **422 Validation Error**
- Check request body format
- Verify required fields are present
- Check field types and values

### **500 Internal Server Error**
- Check microservice API keys are set
- Verify microservices are running
- Check server logs for details

---

## 📚 Additional Resources

- **API Documentation**: See `PRESENTATION_MATH_ENDPOINTS.md`
- **Microservice Docs**: See `PRESENTATION_MATH_MICROSERVICES_DOCUMENTATION.md`
- **Environment Setup**: See `.env` file configuration

---

**Last Updated:** November 2025  
**Postman Version:** 10.0.0+

