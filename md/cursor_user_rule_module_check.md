# 🎯 **Cursor User Rule: Module Check & Code Reuse**

## **RULE: Always Check Existing Modules Before Implementing New Code**

### **📋 Pre-Implementation Checklist**

Before writing any new code, ALWAYS:

1. **Search for existing modules** that might already solve the problem
2. **Check the ModuleRegistry** for available services
3. **Review the modular architecture** to understand dependencies
4. **Consider extending existing modules** instead of creating new ones
5. **Follow the established patterns** and architecture

---

## 🔍 **Step-by-Step Module Check Process**

### **Step 1: Search Existing Services**
```
Search in: app/Services/
Look for: Similar functionality, related services, existing implementations
Check: Service names, class descriptions, method signatures
```

### **Step 2: Check Module Registry**
```
File: app/Services/Modules/ModuleRegistry.php
Look for: Registered modules, dependencies, configurations
Check: Available modules, their capabilities, integration points
```

### **Step 3: Review Controllers**
```
Search in: app/Http/Controllers/
Look for: Similar API endpoints, existing functionality
Check: Controller patterns, service usage, response formats
```

### **Step 4: Check Models**
```
Search in: app/Models/
Look for: Related data models, existing relationships
Check: Model structure, relationships, validation rules
```

---

## 🏗️ **Existing Module Categories**

### **Core Architecture Modules**
- `ModuleRegistry` - Centralized module management
- `UnifiedProcessingService` - Processing pipeline orchestration
- `ContentChunkingService` - Smart content chunking
- `AISummarizationService` - AI-powered summarization
- `ContentExtractionService` - Unified content extraction

### **AI Processing Modules**
- `AIMathService` - Mathematical problem solving
- `OpenAIService` - OpenAI API integration
- `FlashcardGenerationService` - AI flashcard generation
- `VectorDatabaseService` - Vector database operations

### **Content Processing Modules**
- `YouTubeService` - YouTube video processing
- `EnhancedPDFProcessingService` - PDF document processing
- `WebScrapingService` - Web content scraping
- `EnhancedDocumentProcessingService` - Multi-format documents
- `WordProcessingService` - Microsoft Word processing
- `PythonYouTubeService` - Python-based YouTube processing

### **Data Management Modules**
- `FileUploadService` - Universal file upload handling
- `AIResultService` - AI result persistence
- `VectorDatabaseService` - Vector database integration

### **Utility Services**
- `FileUploadService` - File handling and storage
- `AIResultService` - Result management
- `OpenAIService` - AI API integration

---

## 🚫 **What NOT to Do**

### **❌ Don't Create Duplicate Services**
- Don't create a new file upload service if `FileUploadService` exists
- Don't create a new AI service if `OpenAIService` exists
- Don't create a new content processing service if similar ones exist

### **❌ Don't Bypass the Module System**
- Don't implement functionality directly in controllers
- Don't create standalone services without registering them
- Don't ignore existing dependencies and relationships

### **❌ Don't Reinvent the Wheel**
- Don't implement file upload logic when `FileUploadService` exists
- Don't create new AI integration when `OpenAIService` exists
- Don't implement content processing when modular services exist

---

## ✅ **What TO Do Instead**

### **✅ Extend Existing Modules**
- Add new methods to existing services
- Extend existing functionality
- Use existing services as dependencies

### **✅ Use the Module Registry**
- Register new modules properly
- Define dependencies correctly
- Follow established patterns

### **✅ Follow Established Patterns**
- Use the same service structure
- Follow the same error handling patterns
- Use the same response formats

### **✅ Leverage Existing Infrastructure**
- Use existing file upload system
- Use existing AI integration
- Use existing database models

---

## 🔧 **Implementation Guidelines**

### **When Adding New Functionality:**

1. **Check if similar functionality exists**
   ```bash
   # Search for existing services
   grep -r "function_name" app/Services/
   grep -r "similar_feature" app/Services/
   ```

2. **Review existing module capabilities**
   ```php
   // Check ModuleRegistry for available modules
   $modules = ModuleRegistry::getAllModules();
   $enabled = ModuleRegistry::getEnabledModules();
   ```

3. **Consider extending existing services**
   ```php
   // Instead of creating new service, extend existing one
   class ExistingService {
       public function newMethod() {
           // Add new functionality here
       }
   }
   ```

4. **Use existing dependencies**
   ```php
   // Use existing services as dependencies
   public function __construct(
       ExistingService $existingService,
       FileUploadService $fileUploadService
   ) {
       // Use existing services
   }
   ```

---

## 📚 **Common Patterns to Follow**

### **Service Pattern**
```php
class NewService {
    private $existingService;
    private $fileUploadService;
    
    public function __construct(
        ExistingService $existingService,
        FileUploadService $fileUploadService
    ) {
        $this->existingService = $existingService;
        $this->fileUploadService = $fileUploadService;
    }
    
    public function newMethod() {
        // Use existing services
        $result = $this->existingService->existingMethod();
        return $result;
    }
}
```

### **Controller Pattern**
```php
class NewController extends Controller {
    private $newService;
    private $aiResultService;
    
    public function __construct(
        NewService $newService,
        AIResultService $aiResultService
    ) {
        $this->newService = $newService;
        $this->aiResultService = $aiResultService;
    }
    
    public function process(Request $request) {
        // Use existing patterns
        $result = $this->newService->process($request->all());
        
        // Save result using existing service
        $this->aiResultService->saveResult(/*...*/);
        
        return response()->json($result);
    }
}
```

---

## 🎯 **Quick Reference Commands**

### **Search for Existing Functionality**
```bash
# Search for similar services
grep -r "keyword" app/Services/
grep -r "function_name" app/Services/

# Check existing controllers
grep -r "endpoint" app/Http/Controllers/

# Check existing models
grep -r "model_name" app/Models/
```

### **Check Module Registry**
```php
// In tinker or code
$modules = ModuleRegistry::getAllModules();
$enabled = ModuleRegistry::getEnabledModules();
$dependencies = ModuleRegistry::getModuleDependencies('module_name');
```

---

## 🚨 **Red Flags to Watch For**

### **🚩 Duplicate Functionality**
- Creating a new file upload service when `FileUploadService` exists
- Creating a new AI service when `OpenAIService` exists
- Creating a new content processing service when modular services exist

### **🚩 Bypassing Architecture**
- Implementing functionality directly in controllers
- Creating standalone services without proper integration
- Ignoring existing dependencies and relationships

### **🚩 Inconsistent Patterns**
- Using different error handling patterns
- Using different response formats
- Using different service structures

---

## 📋 **Pre-Implementation Checklist**

Before writing any new code, ask:

1. **Does similar functionality already exist?**
2. **Can I extend an existing service instead?**
3. **What existing services can I use as dependencies?**
4. **How does this fit into the modular architecture?**
5. **What patterns should I follow?**
6. **How should I register this in the ModuleRegistry?**
7. **What existing infrastructure can I leverage?**

---

## 🎉 **Benefits of Following This Rule**

- **Code Reuse:** Leverage existing, tested functionality
- **Consistency:** Follow established patterns and architecture
- **Maintainability:** Easier to maintain and extend
- **Performance:** Use optimized, existing services
- **Reliability:** Build on proven, working code
- **Scalability:** Follow scalable architecture patterns

---

## 📝 **Remember**

**Always check existing modules first!** The system has 24 well-architected modules covering most common functionality. Before implementing new code, make sure you're not duplicating existing work or bypassing the established architecture.

**The goal is to build on existing foundations, not recreate them.**
