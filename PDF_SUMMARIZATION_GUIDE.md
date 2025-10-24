# 📄 PDF Summarization Frontend Guide

## 🎯 **Endpoint for PDF Summarization**
```
POST /api/summarize/async/file
```

## 🔑 **Authentication Token**
```
180|oxYCHaS6ppHnnZPvyMqnQ6w4sL4LhKrJXyD6fO3X20f02505
```

## 📋 **Frontend Implementation Guide**

### **1. HTML Form Setup**

```html
<form id="pdfSummarizeForm" enctype="multipart/form-data">
    <div class="form-group">
        <label for="pdfFile">Select PDF File:</label>
        <input 
            type="file" 
            id="pdfFile" 
            name="file" 
            accept=".pdf" 
            required
        />
        <small class="form-text text-muted">
            Supported formats: PDF (Max size: 50MB)
        </small>
    </div>
    
    <div class="form-group">
        <label for="language">Language:</label>
        <select id="language" name="language">
            <option value="en">English</option>
            <option value="es">Spanish</option>
            <option value="fr">French</option>
            <option value="de">German</option>
        </select>
    </div>
    
    <div class="form-group">
        <label for="format">Summary Format:</label>
        <select id="format" name="format">
            <option value="detailed">Detailed</option>
            <option value="brief">Brief</option>
            <option value="bullet-points">Bullet Points</option>
        </select>
    </div>
    
    <button type="submit" id="submitBtn">
        <span id="submitText">Summarize PDF</span>
        <span id="loadingSpinner" style="display: none;">⏳ Processing...</span>
    </button>
</form>

<div id="resultContainer" style="display: none;">
    <h3>Summary Result:</h3>
    <div id="summaryResult"></div>
</div>
```

### **2. JavaScript Implementation**

```javascript
class PDFSummarizer {
    constructor() {
        this.apiBaseUrl = 'http://localhost:8000/api';
        this.authToken = '180|oxYCHaS6ppHnnZPvyMqnQ6w4sL4LhKrJXyD6fO3X20f02505';
        this.currentJobId = null;
        this.pollInterval = null;
    }

    // Initialize the form
    init() {
        const form = document.getElementById('pdfSummarizeForm');
        form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    // Handle form submission
    async handleSubmit(event) {
        event.preventDefault();
        
        const fileInput = document.getElementById('pdfFile');
        const language = document.getElementById('language').value;
        const format = document.getElementById('format').value;
        
        if (!fileInput.files[0]) {
            alert('Please select a PDF file');
            return;
        }

        this.showLoading(true);
        
        try {
            // Step 1: Upload file and start summarization
            const jobData = await this.startSummarization(fileInput.files[0], {
                language: language,
                format: format
            });
            
            this.currentJobId = jobData.job_id;
            console.log('Job started:', jobData);
            
            // Step 2: Poll for completion
            this.startPolling();
            
        } catch (error) {
            console.error('Error starting summarization:', error);
            this.showError('Failed to start summarization: ' + error.message);
            this.showLoading(false);
        }
    }

    // Start PDF summarization
    async startSummarization(file, options) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('options', JSON.stringify(options));

        const response = await fetch(`${this.apiBaseUrl}/summarize/async/file`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.authToken}`,
                'Accept': 'application/json'
            },
            body: formData
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Upload failed');
        }

        return await response.json();
    }

    // Poll job status
    startPolling() {
        this.pollInterval = setInterval(async () => {
            try {
                const status = await this.checkJobStatus();
                console.log('Job status:', status);
                
                if (status.status === 'completed') {
                    this.stopPolling();
                    await this.getResult();
                } else if (status.status === 'failed') {
                    this.stopPolling();
                    this.showError('Summarization failed: ' + (status.error || 'Unknown error'));
                    this.showLoading(false);
                } else {
                    this.updateProgress(status.progress || 0);
                }
                
            } catch (error) {
                console.error('Error checking status:', error);
                this.stopPolling();
                this.showError('Failed to check job status: ' + error.message);
                this.showLoading(false);
            }
        }, 3000); // Poll every 3 seconds
    }

    // Check job status
    async checkJobStatus() {
        const response = await fetch(`${this.apiBaseUrl}/summarize/status/${this.currentJobId}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${this.authToken}`,
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Status check failed');
        }

        return await response.json();
    }

    // Get final result
    async getResult() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/summarize/result/${this.currentJobId}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${this.authToken}`,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'Failed to get result');
            }

            const result = await response.json();
            this.displayResult(result);
            this.showLoading(false);
            
        } catch (error) {
            console.error('Error getting result:', error);
            this.showError('Failed to get result: ' + error.message);
            this.showLoading(false);
        }
    }

    // Display the summary result
    displayResult(result) {
        const container = document.getElementById('resultContainer');
        const resultDiv = document.getElementById('summaryResult');
        
        if (result.success && result.data) {
            const data = result.data;
            
            let html = `
                <div class="summary-result">
                    <h4>📄 PDF Summary</h4>
                    <div class="summary-content">
                        <p><strong>Summary:</strong></p>
                        <p>${data.summary || 'No summary available'}</p>
                    </div>
            `;
            
            if (data.key_points && data.key_points.length > 0) {
                html += `
                    <div class="key-points">
                        <p><strong>Key Points:</strong></p>
                        <ul>
                            ${data.key_points.map(point => `<li>${point}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }
            
            if (data.confidence_score) {
                html += `
                    <div class="metadata">
                        <p><strong>Confidence Score:</strong> ${(data.confidence_score * 100).toFixed(1)}%</p>
                        <p><strong>Model Used:</strong> ${data.model_used || 'N/A'}</p>
                    </div>
                `;
            }
            
            html += '</div>';
            resultDiv.innerHTML = html;
        } else {
            resultDiv.innerHTML = '<p class="error">No summary data available</p>';
        }
        
        container.style.display = 'block';
    }

    // Update progress indicator
    updateProgress(progress) {
        const submitText = document.getElementById('submitText');
        submitText.textContent = `Processing... ${progress}%`;
    }

    // Show/hide loading state
    showLoading(show) {
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const loadingSpinner = document.getElementById('loadingSpinner');
        
        if (show) {
            submitBtn.disabled = true;
            submitText.style.display = 'none';
            loadingSpinner.style.display = 'inline';
        } else {
            submitBtn.disabled = false;
            submitText.style.display = 'inline';
            loadingSpinner.style.display = 'none';
            submitText.textContent = 'Summarize PDF';
        }
    }

    // Show error message
    showError(message) {
        alert('Error: ' + message);
    }

    // Stop polling
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', () => {
    const summarizer = new PDFSummarizer();
    summarizer.init();
});
```

### **3. CSS Styling**

```css
.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: bold;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

button {
    background-color: #007bff;
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
}

button:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
}

#resultContainer {
    margin-top: 2rem;
    padding: 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #f8f9fa;
}

.summary-result {
    line-height: 1.6;
}

.key-points ul {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
}

.metadata {
    margin-top: 1rem;
    padding: 0.5rem;
    background-color: #e9ecef;
    border-radius: 4px;
    font-size: 0.9rem;
}

.error {
    color: #dc3545;
    font-weight: bold;
}
```

## 🚀 **Complete Frontend Example**

### **HTML File (index.html)**
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Summarizer</title>
    <style>
        /* Include the CSS from above */
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 PDF Summarizer</h1>
        <p>Upload a PDF file to get an AI-generated summary</p>
        
        <!-- Include the HTML form from above -->
        
        <!-- Include the JavaScript from above -->
    </div>
</body>
</html>
```

## 📊 **Expected API Responses**

### **1. Initial Upload Response (HTTP 202)**
```json
{
    "success": true,
    "message": "Summarization job started",
    "job_id": "abc123-def456-ghi789",
    "status": "pending",
    "poll_url": "http://localhost:8000/api/summarize/status/abc123-def456-ghi789",
    "result_url": "http://localhost:8000/api/summarize/result/abc123-def456-ghi789"
}
```

### **2. Status Check Response**
```json
{
    "job_id": "abc123-def456-ghi789",
    "status": "running",
    "progress": 75,
    "stage": "processing",
    "error": null
}
```

### **3. Final Result Response**
```json
{
    "success": true,
    "data": {
        "summary": "This document discusses the key principles of artificial intelligence and machine learning...",
        "key_points": [
            "AI is transforming various industries",
            "Machine learning algorithms are becoming more sophisticated",
            "Ethical considerations are important in AI development"
        ],
        "confidence_score": 0.85,
        "model_used": "ollama:phi3:mini"
    }
}
```

## ⚠️ **Important Notes**

1. **File Size Limit:** 50MB maximum
2. **Supported Formats:** PDF, DOC, DOCX, TXT, MP3, MP4, AVI, MOV, WAV, M4A
3. **Processing Time:** 30-120 seconds depending on file size
4. **Authentication:** Always include the Bearer token in headers
5. **Error Handling:** Implement proper error handling for network issues

## 🔧 **Troubleshooting**

- **401 Unauthorized:** Check if the token is correct and not expired
- **413 Payload Too Large:** File exceeds 50MB limit
- **422 Validation Error:** Invalid file format or missing required fields
- **Timeout:** Increase polling interval or implement retry logic

**The PDF summarization endpoint is ready for frontend integration!** 🚀



