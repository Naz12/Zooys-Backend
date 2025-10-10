# 🎨 **AI Presentation Generator - Frontend Implementation Prompt**

## 📋 **Project Context**
You are implementing the AI Presentation Generator frontend for an existing Next.js project. There is already an AI presentation page created, and you need to integrate the 4-step workflow without breaking existing functionality.

## 🎯 **Implementation Requirements**

### **1. Preserve Existing Structure**
- **Do not modify** existing AI presentation page structure
- **Extend existing components** rather than replacing them
- **Maintain existing styling** and design patterns
- **Keep existing navigation** and routing intact

### **2. Implement 4-Step Workflow**
Create a step-by-step presentation generation process that follows this exact flow:

#### **Step 1: User Input & AI Outline Generation**
- **Input method selection** (Text, File, Link, YouTube tabs)
- **Topic input field** with character counter (5000 max)
- **Configuration dropdowns**: Language, Tone, Length, Model
- **Quick start examples** for common presentation topics
- **Next Step button** to proceed to outline generation

#### **Step 2: User Review & Edit Outline**
- **Display AI-generated outline** with slide structure
- **Editable slide titles** and content
- **Drag & drop reordering** for slides
- **Add/remove slides** functionality
- **Preview of slide structure**
- **Submit button** to save modifications

#### **Step 3: Template Selection**
- **Template gallery** with visual previews
- **Template categories** (Business, Creative, Academic, etc.)
- **Color scheme options** within templates
- **Font style variations**
- **Template preview** with sample content
- **Select button** to confirm template choice

#### **Step 4: PowerPoint Generation & Editor**
- **Generation progress** indicator
- **PowerPoint preview** when ready
- **Download options** (PowerPoint, PDF, etc.)
- **Edit capabilities** for final customization
- **Share and collaboration** features

### **3. API Integration**
Integrate with these Laravel backend endpoints:

#### **Step 1 API**
- `POST /api/presentations/generate-outline`
- Handle multiple input types (text, file, URL, YouTube)
- Process configuration parameters
- Display generated outline

#### **Step 2 API**
- `PUT /api/presentations/{aiResultId}/update-outline`
- Send modified outline data
- Handle validation errors
- Update presentation state

#### **Step 3 API**
- `GET /api/presentations/templates`
- Display template gallery
- Handle template selection
- Show template previews

#### **Step 4 API**
- `POST /api/presentations/{aiResultId}/generate-powerpoint`
- Send template and styling preferences
- Handle generation progress
- Display download links

#### **Management APIs**
- `GET /api/presentations` - List user presentations
- `GET /api/presentations/{id}` - Get specific presentation
- `DELETE /api/presentations/{id}` - Delete presentation

### **4. State Management**
- **Step progression** state management
- **Form data persistence** across steps
- **AI result tracking** throughout workflow
- **Error state handling** for each step
- **Loading states** for async operations

### **5. User Experience Features**
- **Progress indicator** showing current step
- **Auto-save functionality** for outline editing
- **Real-time validation** for form inputs
- **Error handling** with user-friendly messages
- **Success feedback** for completed actions
- **Responsive design** for mobile and desktop

### **6. File Handling**
- **File upload** for document-based presentations
- **Drag & drop** file upload interface
- **File validation** (size, type, format)
- **Upload progress** indicators
- **File preview** capabilities

### **7. Template System**
- **Visual template gallery** with thumbnails
- **Template categories** and filtering
- **Live preview** of template styles
- **Color scheme customization**
- **Font style selection**

### **8. PowerPoint Integration**
- **PowerPoint preview** in browser
- **Download functionality** for generated files
- **Edit capabilities** for final customization
- **Version control** for presentation changes
- **Collaboration features** for team editing

## 🚫 **Implementation Constraints**

### **Do Not Break Existing Functionality**
- **Preserve existing** AI presentation page structure
- **Maintain existing** navigation and routing
- **Keep existing** styling and design patterns
- **Don't modify** existing components unless necessary

### **Follow Existing Patterns**
- **Use existing** API integration patterns
- **Follow existing** state management approach
- **Maintain existing** error handling patterns
- **Keep existing** loading and success states

### **No Code Examples**
- **Provide implementation guidance** only
- **Focus on architecture** and integration approach
- **Explain component structure** and relationships
- **Describe data flow** and state management

## 🎯 **Success Criteria**

### **Functional Requirements**
- **Complete 4-step workflow** implementation
- **All API endpoints** properly integrated
- **File upload and processing** working
- **Template selection** and preview functional
- **PowerPoint generation** and download working

### **User Experience Requirements**
- **Smooth step progression** without data loss
- **Intuitive interface** following existing design
- **Responsive design** for all screen sizes
- **Error handling** with clear user feedback
- **Loading states** for all async operations

### **Technical Requirements**
- **No breaking changes** to existing functionality
- **Proper error handling** and validation
- **State persistence** across page refreshes
- **Performance optimization** for large files
- **Accessibility compliance** for all components

## 🚀 **Implementation Approach**

### **Phase 1: Foundation**
- **Extend existing** AI presentation page
- **Implement step navigation** and progress tracking
- **Set up state management** for workflow data
- **Create base components** for each step

### **Phase 2: Step Implementation**
- **Implement Step 1** with input handling and API integration
- **Implement Step 2** with outline editing capabilities
- **Implement Step 3** with template selection
- **Implement Step 4** with PowerPoint generation

### **Phase 3: Integration & Polish**
- **Integrate all steps** into cohesive workflow
- **Add error handling** and validation
- **Implement loading states** and progress indicators
- **Test and optimize** performance

### **Phase 4: Advanced Features**
- **Add file upload** capabilities
- **Implement template previews**
- **Add collaboration features**
- **Optimize for mobile** experience

## 📱 **Responsive Design Requirements**
- **Mobile-first approach** for all components
- **Touch-friendly** interface elements
- **Optimized layouts** for different screen sizes
- **Accessible navigation** for all devices

## 🔒 **Security Considerations**
- **Validate all inputs** before API calls
- **Handle authentication** tokens properly
- **Sanitize file uploads** and user content
- **Implement proper error handling** without exposing sensitive data

## 🎨 **Design Integration**
- **Follow existing** design system and patterns
- **Use existing** color schemes and typography
- **Maintain consistency** with other AI tools
- **Ensure accessibility** compliance

## 📊 **Performance Requirements**
- **Optimize API calls** to prevent unnecessary requests
- **Implement caching** for templates and static data
- **Handle large file uploads** efficiently
- **Provide progress feedback** for long operations

## 🧪 **Testing Requirements**
- **Test all workflow steps** thoroughly
- **Validate API integration** with error scenarios
- **Test file upload** with different file types
- **Verify responsive design** on multiple devices
- **Test accessibility** compliance

## 🚀 **Deployment Considerations**
- **Ensure compatibility** with existing deployment pipeline
- **Maintain existing** environment configurations
- **Follow existing** build and deployment processes
- **Test in staging** environment before production

## 📋 **API Endpoint Details**

### **Step 1: Generate Outline**
```http
POST /api/presentations/generate-outline
Content-Type: application/json
Authorization: Bearer {token}

Request Body:
{
    "input_type": "text|file|url|youtube",
    "topic": "string (max 5000 chars)",
    "language": "English|Spanish|French|German|Italian|Portuguese|Chinese|Japanese",
    "tone": "Professional|Casual|Academic|Creative|Formal",
    "length": "Short|Medium|Long",
    "model": "Basic Model|Advanced Model|Premium Model",
    "file": "file (if input_type=file)",
    "url": "string (if input_type=url)",
    "youtube_url": "string (if input_type=youtube)"
}

Response:
{
    "success": true,
    "data": {
        "outline": {
            "title": "string",
            "slides": [
                {
                    "slide_number": 1,
                    "header": "string",
                    "subheaders": ["string"],
                    "slide_type": "title|content|conclusion"
                }
            ],
            "estimated_duration": "string",
            "slide_count": number
        },
        "ai_result_id": number
    }
}
```

### **Step 2: Update Outline**
```http
PUT /api/presentations/{aiResultId}/update-outline
Content-Type: application/json
Authorization: Bearer {token}

Request Body:
{
    "outline": {
        "title": "string",
        "slides": [
            {
                "slide_number": number,
                "header": "string",
                "subheaders": ["string"],
                "slide_type": "string"
            }
        ]
    }
}

Response:
{
    "success": true,
    "data": {
        "outline": {...},
        "ai_result_id": number
    }
}
```

### **Step 3: Get Templates**
```http
GET /api/presentations/templates
Authorization: Bearer {token}

Response:
{
    "success": true,
    "data": {
        "templates": {
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
            "creative_colorful": {
                "name": "Creative Colorful",
                "description": "Vibrant colors for creative presentations",
                "color_scheme": "colorful",
                "category": "creative"
            },
            "minimalist_gray": {
                "name": "Minimalist Gray",
                "description": "Simple gray theme for focused content",
                "color_scheme": "gray",
                "category": "minimalist"
            },
            "academic_formal": {
                "name": "Academic Formal",
                "description": "Formal theme for educational presentations",
                "color_scheme": "dark",
                "category": "academic"
            }
        }
    }
}
```

### **Step 4: Generate PowerPoint**
```http
POST /api/presentations/{aiResultId}/generate-powerpoint
Content-Type: application/json
Authorization: Bearer {token}

Request Body:
{
    "template": "corporate_blue|modern_white|creative_colorful|minimalist_gray|academic_formal",
    "color_scheme": "blue|white|colorful|gray|dark",
    "font_style": "modern|classic|minimalist|creative"
}

Response:
{
    "success": true,
    "data": {
        "powerpoint_file": "string (file path)",
        "download_url": "string (download URL)",
        "ai_result_id": number
    }
}
```

### **Management Endpoints**

#### **Get All Presentations**
```http
GET /api/presentations?per_page=15&search=string
Authorization: Bearer {token}

Response:
{
    "success": true,
    "data": {
        "presentations": [
            {
                "id": number,
                "title": "string",
                "description": "string",
                "tool_type": "presentation",
                "status": "string",
                "created_at": "string",
                "updated_at": "string"
            }
        ],
        "pagination": {
            "current_page": number,
            "last_page": number,
            "per_page": number,
            "total": number
        }
    }
}
```

#### **Get Specific Presentation**
```http
GET /api/presentations/{aiResultId}
Authorization: Bearer {token}

Response:
{
    "success": true,
    "data": {
        "presentation": {
            "id": number,
            "title": "string",
            "description": "string",
            "tool_type": "presentation",
            "input_data": {...},
            "result_data": {...},
            "metadata": {...},
            "status": "string",
            "created_at": "string",
            "updated_at": "string"
        }
    }
}
```

#### **Delete Presentation**
```http
DELETE /api/presentations/{aiResultId}
Authorization: Bearer {token}

Response:
{
    "success": true,
    "message": "Presentation deleted successfully"
}
```

## 🎯 **Component Architecture**

### **Main Components**
- **PresentationWorkflow** - Main container component
- **StepNavigation** - Progress indicator and step navigation
- **InputStep** - Step 1: User input and configuration
- **OutlineStep** - Step 2: Outline review and editing
- **TemplateStep** - Step 3: Template selection
- **GenerationStep** - Step 4: PowerPoint generation and download

### **Supporting Components**
- **FileUpload** - File upload with drag & drop
- **TemplateGallery** - Template selection interface
- **OutlineEditor** - Slide editing and reordering
- **ProgressIndicator** - Loading and progress states
- **ErrorHandler** - Error display and handling

### **State Management**
- **WorkflowState** - Current step and progression
- **FormState** - User input and configuration data
- **OutlineState** - Generated and modified outline data
- **TemplateState** - Selected template and styling
- **GenerationState** - PowerPoint generation status

## 🔄 **Data Flow**

### **Step 1 Flow**
```
User Input → Form Validation → API Call → Outline Generation → State Update → Step 2
```

### **Step 2 Flow**
```
Outline Display → User Editing → Validation → API Update → State Update → Step 3
```

### **Step 3 Flow**
```
Template Gallery → Template Selection → Preview → Confirmation → Step 4
```

### **Step 4 Flow**
```
Template Data → API Call → Generation Progress → File Ready → Download/Edit
```

## 🎨 **UI/UX Guidelines**

### **Step Progression**
- **Clear visual indicators** for current step
- **Disabled navigation** for incomplete steps
- **Progress bar** showing overall completion
- **Step validation** before allowing progression

### **Form Design**
- **Consistent styling** with existing forms
- **Real-time validation** with helpful error messages
- **Auto-save functionality** for user data
- **Responsive layouts** for all screen sizes

### **Loading States**
- **Skeleton loaders** for content areas
- **Progress indicators** for long operations
- **Loading spinners** for quick actions
- **Success animations** for completed steps

### **Error Handling**
- **Inline error messages** for form validation
- **Toast notifications** for API errors
- **Retry mechanisms** for failed operations
- **Fallback content** for missing data

## 🚀 **Implementation Checklist**

### **Phase 1: Setup**
- [ ] Extend existing AI presentation page
- [ ] Set up step navigation component
- [ ] Implement state management structure
- [ ] Create base component architecture

### **Phase 2: Step 1 Implementation**
- [ ] Create input method selection tabs
- [ ] Implement topic input with character counter
- [ ] Add configuration dropdowns
- [ ] Create quick start examples
- [ ] Integrate with generate-outline API

### **Phase 3: Step 2 Implementation**
- [ ] Create outline display component
- [ ] Implement slide editing functionality
- [ ] Add drag & drop reordering
- [ ] Create add/remove slide features
- [ ] Integrate with update-outline API

### **Phase 4: Step 3 Implementation**
- [ ] Create template gallery component
- [ ] Implement template preview functionality
- [ ] Add template selection logic
- [ ] Create template categories and filtering
- [ ] Integrate with get-templates API

### **Phase 5: Step 4 Implementation**
- [ ] Create PowerPoint generation component
- [ ] Implement progress tracking
- [ ] Add download functionality
- [ ] Create edit capabilities
- [ ] Integrate with generate-powerpoint API

### **Phase 6: Integration & Testing**
- [ ] Connect all steps into workflow
- [ ] Implement error handling throughout
- [ ] Add loading states and progress indicators
- [ ] Test responsive design on all devices
- [ ] Validate accessibility compliance

### **Phase 7: Polish & Optimization**
- [ ] Optimize API calls and caching
- [ ] Implement performance optimizations
- [ ] Add advanced features (auto-save, collaboration)
- [ ] Final testing and bug fixes
- [ ] Documentation and deployment

## 🎯 **Success Metrics**

### **Functional Success**
- **All 4 steps** working without errors
- **API integration** functioning correctly
- **File upload** and processing working
- **Template selection** and preview functional
- **PowerPoint generation** and download working

### **User Experience Success**
- **Smooth workflow** progression
- **Intuitive interface** following existing design
- **Responsive design** on all devices
- **Clear error messages** and feedback
- **Fast loading** and responsive interactions

### **Technical Success**
- **No breaking changes** to existing functionality
- **Proper error handling** and validation
- **State persistence** across page refreshes
- **Performance optimization** for large files
- **Accessibility compliance** for all components

This implementation should create a seamless, professional AI Presentation Generator that integrates perfectly with your existing Next.js project while providing users with a powerful tool for creating presentations through AI! 🎯✨
