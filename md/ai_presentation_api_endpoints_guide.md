# 🎨 AI Presentation Tool - API Endpoints Guide

## Overview

This guide covers all API endpoints for the AI Presentation Tool and how the frontend should integrate with them for the new JSON-based editing workflow.

## 🔗 Base URL
```
http://localhost:8000/api
```

## 🔐 Authentication
All endpoints require authentication. Include the Bearer token in headers:
```javascript
headers: {
  'Authorization': `Bearer ${token}`,
  'Content-Type': 'application/json'
}
```

---

## 📋 **Core Presentation Endpoints**

### **1. Generate Presentation Outline**
**Endpoint:** `POST /api/presentations/generate-outline`

**Purpose:** Generate AI-powered presentation outline from user input

**Request Body:**
```json
{
  "input_type": "text",
  "content": "The Future of Artificial Intelligence in Business",
  "language": "English",
  "tone": "Professional",
  "length": "Medium",
  "model": "Basic Model"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "ai_result_id": 123,
    "title": "The Future of Artificial Intelligence in Business",
    "slides": [
      {
        "slide_number": 1,
        "header": "Introduction",
        "subheaders": ["Definition of AI", "Business Impact", "Purpose"],
        "slide_type": "title"
      }
    ],
    "estimated_duration": "30 minutes",
    "slide_count": 10
  }
}
```

**Frontend Usage:**
```javascript
const generateOutline = async (inputData) => {
  const response = await fetch('/api/presentations/generate-outline', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(inputData)
  });
  
  const result = await response.json();
  if (result.success) {
    // Store ai_result_id for future operations
    setAiResultId(result.data.ai_result_id);
    setPresentationData(result.data);
  }
  return result;
};
```

---

### **2. Update Presentation Outline**
**Endpoint:** `PUT /api/presentations/{aiResultId}/update-outline`

**Purpose:** Update the AI-generated outline with user modifications

**Request Body:**
```json
{
  "title": "Updated Presentation Title",
  "slides": [
    {
      "slide_number": 1,
      "header": "Updated Introduction",
      "subheaders": ["New Point 1", "New Point 2"],
      "slide_type": "title"
    }
  ],
  "estimated_duration": "35 minutes",
  "slide_count": 12
}
```

**Frontend Usage:**
```javascript
const updateOutline = async (aiResultId, outlineData) => {
  const response = await fetch(`/api/presentations/${aiResultId}/update-outline`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(outlineData)
  });
  
  return response.json();
};
```

---

### **3. Generate Content for Slides**
**Endpoint:** `POST /api/presentations/{aiResultId}/generate-content`

**Purpose:** Generate detailed content for each slide using OpenAI

**Request Body:** (Empty - uses existing outline data)

**Response:**
```json
{
  "success": true,
  "data": {
    "slides": [
      {
        "slide_number": 1,
        "header": "Introduction",
        "subheaders": ["Definition of AI", "Business Impact"],
        "slide_type": "title",
        "content": [
          "• Artificial Intelligence refers to computer systems that can perform tasks typically requiring human intelligence",
          "• AI is transforming business operations across all industries",
          "• This presentation explores the opportunities and challenges"
        ]
      }
    ],
    "ai_result_id": 123
  }
}
```

**Frontend Usage:**
```javascript
const generateContent = async (aiResultId) => {
  const response = await fetch(`/api/presentations/${aiResultId}/generate-content`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const result = await response.json();
  if (result.success) {
    setPresentationData(prev => ({
      ...prev,
      slides: result.data.slides
    }));
  }
  return result;
};
```

---

## 🎨 **Frontend Editing Endpoints (JSON-based)**

### **4. Save Presentation Data**
**Endpoint:** `POST /api/presentations/{aiResultId}/save`

**Purpose:** Save presentation data for frontend editing (JSON format)

**Request Body:**
```json
{
  "presentation_data": {
    "title": "My Presentation",
    "slides": [
      {
        "id": "slide_1",
        "type": "title",
        "content": {
          "title": "Welcome to Our Company",
          "subtitle": "Building the Future"
        },
        "template": "corporate_blue",
        "layout": "title_slide"
      },
      {
        "id": "slide_2",
        "type": "content",
        "content": {
          "title": "Our Mission",
          "bullets": [
            "Innovation in technology",
            "Customer satisfaction",
            "Sustainable growth"
          ]
        },
        "template": "corporate_blue",
        "layout": "content_slide"
      }
    ],
    "template": "corporate_blue",
    "color_scheme": "blue",
    "font_style": "modern",
    "metadata": {
      "created_at": "2025-01-09T10:00:00Z",
      "updated_at": "2025-01-09T10:30:00Z",
      "version": 3
    }
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "presentation_data": { ... },
    "user_id": 21,
    "ai_result_id": 123,
    "saved_at": 1641729600
  },
  "message": "Presentation saved successfully"
}
```

**Frontend Usage:**
```javascript
const savePresentation = async (aiResultId, presentationData) => {
  const response = await fetch(`/api/presentations/${aiResultId}/save`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ presentation_data: presentationData })
  });
  
  const result = await response.json();
  if (result.success) {
    // Show success message
    showNotification('Presentation saved successfully!');
  }
  return result;
};

// Auto-save functionality
const autoSave = useCallback(
  debounce((data) => {
    if (aiResultId) {
      savePresentation(aiResultId, data);
    }
  }, 2000),
  [aiResultId]
);
```

---

### **5. Get Presentation Data**
**Endpoint:** `GET /api/presentations/{aiResultId}/data`

**Purpose:** Load presentation data for frontend editing

**Response:**
```json
{
  "success": true,
  "data": {
    "title": "My Presentation",
    "slides": [ ... ],
    "template": "corporate_blue",
    "color_scheme": "blue",
    "font_style": "modern"
  },
  "metadata": {
    "created_at": "2025-01-09T10:00:00Z",
    "updated_at": "2025-01-09T10:30:00Z",
    "version": 3
  }
}
```

**Frontend Usage:**
```javascript
const loadPresentation = async (aiResultId) => {
  const response = await fetch(`/api/presentations/${aiResultId}/data`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const result = await response.json();
  if (result.success) {
    setPresentationData(result.data);
    setMetadata(result.metadata);
  }
  return result;
};

// Load presentation on component mount
useEffect(() => {
  if (aiResultId) {
    loadPresentation(aiResultId);
  }
}, [aiResultId]);
```

---

### **6. Export to PowerPoint**
**Endpoint:** `POST /api/presentations/{aiResultId}/export`

**Purpose:** Export presentation data to PowerPoint file (on-demand)

**Request Body:**
```json
{
  "presentation_data": {
    "title": "My Presentation",
    "slides": [ ... ],
    "template": "corporate_blue",
    "color_scheme": "blue",
    "font_style": "modern"
  },
  "template": "elegant_purple",
  "color_scheme": "purple",
  "font_style": "modern"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "file_path": "/storage/app/presentations/presentation_21_123_1641729600.pptx",
    "download_url": "/api/files/download/presentation_21_123_1641729600.pptx",
    "slide_count": 10,
    "exported_at": 1641729600
  },
  "message": "Presentation exported successfully"
}
```

**Frontend Usage:**
```javascript
const exportToPowerPoint = async (aiResultId, presentationData, templateOverrides = null) => {
  const requestBody = { presentation_data: presentationData };
  
  if (templateOverrides) {
    Object.assign(requestBody, templateOverrides);
  }
  
  const response = await fetch(`/api/presentations/${aiResultId}/export`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(requestBody)
  });
  
  const result = await response.json();
  if (result.success) {
    // Trigger download
    window.open(result.data.download_url, '_blank');
    showNotification('PowerPoint exported successfully!');
  }
  return result;
};

// Export with different template
const exportWithTemplate = async (templateId) => {
  const templateOverrides = {
    template: templateId,
    color_scheme: getColorScheme(templateId),
    font_style: 'modern'
  };
  
  await exportToPowerPoint(aiResultId, presentationData, templateOverrides);
};
```

---

## 🎨 **Template Management**

### **7. Get Available Templates**
**Endpoint:** `GET /api/presentations/templates`

**Response:**
```json
{
  "success": true,
  "data": {
    "corporate_blue": {
      "name": "Corporate Blue",
      "description": "Professional blue theme for business presentations",
      "color_scheme": "blue",
      "category": "business"
    },
    "modern_white": {
      "name": "Modern White",
      "description": "Clean white theme with modern typography",
      "color_scheme": "white",
      "category": "modern"
    },
    "elegant_purple": {
      "name": "Elegant Purple",
      "description": "Sophisticated purple theme for elegant presentations",
      "color_scheme": "purple",
      "category": "elegant"
    }
  }
}
```

**Frontend Usage:**
```javascript
const loadTemplates = async () => {
  const response = await fetch('/api/presentations/templates', {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const result = await response.json();
  if (result.success) {
    setTemplates(result.data);
  }
  return result;
};

// Template switcher component
const TemplateSwitcher = ({ currentTemplate, onTemplateChange }) => {
  return (
    <div className="template-grid">
      {Object.entries(templates).map(([id, template]) => (
        <div
          key={id}
          className={`template-card ${currentTemplate === id ? 'active' : ''}`}
          onClick={() => onTemplateChange(id)}
        >
          <div className="template-preview" style={{ backgroundColor: template.color_scheme }}>
            <h3>{template.name}</h3>
            <p>{template.description}</p>
          </div>
        </div>
      ))}
    </div>
  );
};
```

---

## 📊 **Presentation Management**

### **8. Get User Presentations**
**Endpoint:** `GET /api/presentations`

**Query Parameters:**
- `page` (optional): Page number for pagination
- `limit` (optional): Number of presentations per page

**Response:**
```json
{
  "success": true,
  "data": {
    "presentations": [
      {
        "id": 123,
        "title": "AI in Business",
        "slide_count": 10,
        "created_at": "2025-01-09T10:00:00Z",
        "updated_at": "2025-01-09T10:30:00Z",
        "template": "corporate_blue",
        "status": "completed"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "total_presentations": 50
    }
  }
}
```

**Frontend Usage:**
```javascript
const loadPresentations = async (page = 1, limit = 10) => {
  const response = await fetch(`/api/presentations?page=${page}&limit=${limit}`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const result = await response.json();
  if (result.success) {
    setPresentations(result.data.presentations);
    setPagination(result.data.pagination);
  }
  return result;
};
```

---

### **9. Get Single Presentation**
**Endpoint:** `GET /api/presentations/{aiResultId}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "title": "AI in Business",
    "slide_count": 10,
    "created_at": "2025-01-09T10:00:00Z",
    "updated_at": "2025-01-09T10:30:00Z",
    "template": "corporate_blue",
    "status": "completed",
    "result_data": { ... }
  }
}
```

---

### **10. Delete Presentation**
**Endpoint:** `DELETE /api/presentations/{aiResultId}`

**Response:**
```json
{
  "success": true,
  "message": "Presentation deleted successfully"
}
```

**Frontend Usage:**
```javascript
const deletePresentation = async (aiResultId) => {
  if (confirm('Are you sure you want to delete this presentation?')) {
    const response = await fetch(`/api/presentations/${aiResultId}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    
    const result = await response.json();
    if (result.success) {
      // Remove from local state
      setPresentations(prev => prev.filter(p => p.id !== aiResultId));
      showNotification('Presentation deleted successfully!');
    }
    return result;
  }
};
```

---

## 🔧 **System Status**

### **11. Check Microservice Status**
**Endpoint:** `GET /api/presentations/microservice-status`

**Response:**
```json
{
  "success": true,
  "data": {
    "microservice_available": true,
    "status": "online"
  }
}
```

**Frontend Usage:**
```javascript
const checkMicroserviceStatus = async () => {
  const response = await fetch('/api/presentations/microservice-status', {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const result = await response.json();
  if (result.success) {
    setMicroserviceStatus(result.data.status);
  }
  return result;
};
```

---

## 🎯 **Complete Frontend Integration Example**

### **React Hook for Presentation Management**
```javascript
import { useState, useEffect, useCallback } from 'react';
import { debounce } from 'lodash';

const usePresentation = (aiResultId) => {
  const [presentationData, setPresentationData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Load presentation data
  const loadPresentation = useCallback(async () => {
    if (!aiResultId) return;
    
    setLoading(true);
    try {
      const response = await fetch(`/api/presentations/${aiResultId}/data`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      
      const result = await response.json();
      if (result.success) {
        setPresentationData(result.data);
      } else {
        setError(result.error);
      }
    } catch (err) {
      setError('Failed to load presentation');
    } finally {
      setLoading(false);
    }
  }, [aiResultId]);

  // Auto-save presentation data
  const autoSave = useCallback(
    debounce(async (data) => {
      if (!aiResultId) return;
      
      try {
        await fetch(`/api/presentations/${aiResultId}/save`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ presentation_data: data })
        });
      } catch (err) {
        console.error('Auto-save failed:', err);
      }
    }, 2000),
    [aiResultId]
  );

  // Update presentation data
  const updatePresentation = useCallback((updates) => {
    setPresentationData(prev => {
      const newData = { ...prev, ...updates };
      autoSave(newData);
      return newData;
    });
  }, [autoSave]);

  // Export to PowerPoint
  const exportToPowerPoint = useCallback(async (templateOverrides = null) => {
    if (!aiResultId || !presentationData) return;
    
    try {
      const requestBody = { presentation_data: presentationData };
      if (templateOverrides) {
        Object.assign(requestBody, templateOverrides);
      }
      
      const response = await fetch(`/api/presentations/${aiResultId}/export`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestBody)
      });
      
      const result = await response.json();
      if (result.success) {
        window.open(result.data.download_url, '_blank');
        return true;
      }
      return false;
    } catch (err) {
      console.error('Export failed:', err);
      return false;
    }
  }, [aiResultId, presentationData]);

  useEffect(() => {
    loadPresentation();
  }, [loadPresentation]);

  return {
    presentationData,
    loading,
    error,
    updatePresentation,
    exportToPowerPoint,
    reload: loadPresentation
  };
};
```

### **Main Presentation Editor Component**
```javascript
const PresentationEditor = ({ aiResultId }) => {
  const {
    presentationData,
    loading,
    error,
    updatePresentation,
    exportToPowerPoint
  } = usePresentation(aiResultId);

  const handleSlideUpdate = (slideId, updates) => {
    updatePresentation({
      slides: presentationData.slides.map(slide =>
        slide.id === slideId ? { ...slide, ...updates } : slide
      )
    });
  };

  const handleTemplateChange = (templateId) => {
    updatePresentation({ template: templateId });
  };

  if (loading) return <div>Loading presentation...</div>;
  if (error) return <div>Error: {error}</div>;
  if (!presentationData) return <div>No presentation data</div>;

  return (
    <div className="presentation-editor">
      <header className="editor-header">
        <h1>{presentationData.title}</h1>
        <div className="editor-actions">
          <TemplateSwitcher
            currentTemplate={presentationData.template}
            onTemplateChange={handleTemplateChange}
          />
          <button onClick={() => exportToPowerPoint()}>
            Export PowerPoint
          </button>
        </div>
      </header>
      
      <div className="slides-container">
        {presentationData.slides.map(slide => (
          <SlideEditor
            key={slide.id}
            slide={slide}
            onUpdate={(updates) => handleSlideUpdate(slide.id, updates)}
          />
        ))}
      </div>
    </div>
  );
};
```

---

## 🚀 **Best Practices**

### **1. Error Handling**
```javascript
const handleApiCall = async (apiCall) => {
  try {
    const result = await apiCall();
    if (!result.success) {
      throw new Error(result.error);
    }
    return result;
  } catch (error) {
    console.error('API Error:', error);
    showNotification(`Error: ${error.message}`, 'error');
    throw error;
  }
};
```

### **2. Loading States**
```javascript
const [loading, setLoading] = useState(false);

const performAction = async () => {
  setLoading(true);
  try {
    await apiCall();
  } finally {
    setLoading(false);
  }
};
```

### **3. Auto-save Implementation**
```javascript
const autoSave = useCallback(
  debounce(async (data) => {
    try {
      await savePresentation(aiResultId, data);
      setLastSaved(new Date());
    } catch (error) {
      console.error('Auto-save failed:', error);
    }
  }, 2000),
  [aiResultId]
);
```

### **4. Optimistic Updates**
```javascript
const updateSlide = (slideId, updates) => {
  // Update UI immediately
  setPresentationData(prev => ({
    ...prev,
    slides: prev.slides.map(slide =>
      slide.id === slideId ? { ...slide, ...updates } : slide
    )
  }));
  
  // Save to backend
  autoSave(presentationData);
};
```

---

## 🎉 **Summary**

The AI Presentation Tool provides a complete set of APIs for:

- ✅ **Generating AI outlines** from user input
- ✅ **Editing presentations** in real-time with JSON storage
- ✅ **Template management** with live switching
- ✅ **On-demand PowerPoint export** with professional output
- ✅ **Presentation management** (save, load, delete)
- ✅ **System monitoring** and status checks

**The frontend can now build a powerful, real-time presentation editor with professional PowerPoint output capabilities!** 🚀✨
