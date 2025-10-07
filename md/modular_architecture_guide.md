# 🏗️ **Modular Architecture Guide**

## **Overview**

The new modular architecture provides a clean, extensible system for AI content processing. Each module has a specific responsibility and can be easily added, removed, or modified without affecting other modules.

## **🏛️ Architecture Components**

### **1. Core Modules**

#### **Content Chunking Module** (`ContentChunkingService`)
- **Purpose**: Smart text splitting for large content
- **Features**:
  - Sentence boundary detection
  - Speaker-aware transcript chunking
  - Paragraph-aware document chunking
  - Overlap preservation for context
- **Configuration**: `config/ai.php` → `chunking`

#### **AI Summarization Module** (`AISummarizationService`)
- **Purpose**: AI-powered content summarization
- **Features**:
  - Chunked processing for large texts
  - Progressive summarization
  - Key point extraction
  - Multi-language support
- **Configuration**: `config/ai.php` → `summarization`

#### **Content Extraction Module** (`ContentExtractionService`)
- **Purpose**: Unified content extraction from various sources
- **Features**:
  - YouTube video processing
  - PDF document extraction
  - Web content scraping
  - Text content processing
- **Configuration**: `config/ai.php` → `content_extraction`

### **2. Module Registry** (`ModuleRegistry`)
- **Purpose**: Centralized module management
- **Features**:
  - Module registration and discovery
  - Dependency management
  - Configuration management
  - Module statistics

### **3. Unified Processing Service** (`UnifiedProcessingService`)
- **Purpose**: Orchestrates the complete processing pipeline
- **Features**:
  - Content extraction → Chunking → Summarization
  - Error handling and logging
  - Result persistence
  - Statistics collection

## **🔧 Module Configuration**

### **Chunking Configuration**
```php
'chunking' => [
    'max_size' => 3000,        // Maximum chunk size in characters
    'overlap_size' => 200,     // Overlap between chunks
    'min_size' => 500,         // Minimum chunk size
    'enabled' => true,         // Enable/disable chunking
],
```

### **Summarization Configuration**
```php
'summarization' => [
    'max_tokens' => 1000,      // Maximum tokens per request
    'temperature' => 0.7,     // AI creativity level
    'enabled' => true,        // Enable/disable summarization
],
```

### **Content Extraction Configuration**
```php
'content_extraction' => [
    'supported_types' => ['text', 'youtube', 'pdf', 'url', 'document'],
    'max_file_size' => '10MB',
    'timeout' => 30,
],
```

## **🚀 Adding New Modules**

### **Step 1: Create Module Class**
```php
<?php

namespace App\Services\Modules;

class MyNewModule
{
    public function process($input, $options = [])
    {
        // Your processing logic here
        return [
            'success' => true,
            'result' => $processedData,
            'metadata' => $metadata
        ];
    }
}
```

### **Step 2: Register Module**
```php
// In ModuleRegistry::registerCustomModules()
self::registerModule('my_new_module', [
    'class' => MyNewModule::class,
    'description' => 'My new processing module',
    'dependencies' => ['content_extunking'],
    'config' => [
        'my_setting' => 'value',
    ]
]);
```

### **Step 3: Add Configuration**
```php
// In config/ai.php
'modules' => [
    'my_new_module' => [
        'enabled' => env('AI_MY_NEW_MODULE_ENABLED', true),
        'my_setting' => env('AI_MY_NEW_MODULE_SETTING', 'default'),
    ],
],
```

### **Step 4: Use in Controllers**
```php
public function process(Request $request)
{
    $result = $this->unifiedProcessingService->processContent(
        $request->input,
        'my_new_type',
        $request->all()
    );
    
    return response()->json($result);
}
```

## **📊 Module Statistics**

### **Get Module Stats**
```php
$stats = ModuleRegistry::getModuleStats();
// Returns: total_modules, enabled_modules, disabled_modules, modules
```

### **Get Processing Stats**
```php
$stats = $unifiedProcessingService->getProcessingStats($result);
// Returns: processing_method, chunks_processed, total_characters, etc.
```

## **🔍 Module Dependencies**

### **Check Dependencies**
```php
$missing = ModuleRegistry::validateDependencies('my_module');
if (!empty($missing)) {
    // Handle missing dependencies
}
```

### **Enable/Disable Modules**
```php
ModuleRegistry::enableModule('my_module');
ModuleRegistry::disableModule('my_module');
```

## **⚡ Performance Optimization**

### **Chunking Thresholds**
- **Small content** (< 8,000 chars): Direct processing
- **Medium content** (8,000-50,000 chars): Smart chunking
- **Large content** (> 50,000 chars): Progressive chunking

### **Parallel Processing**
```php
'processing' => [
    'parallel_processing' => true,  // Enable parallel chunk processing
    'max_workers' => 4,           // Maximum parallel workers
],
```

## **🛠️ Troubleshooting**

### **Common Issues**

1. **Module Not Found**
   ```php
   // Check if module is registered
   if (!ModuleRegistry::hasModule('my_module')) {
       // Register module
   }
   ```

2. **Dependency Issues**
   ```php
   // Validate dependencies
   $missing = ModuleRegistry::validateDependencies('my_module');
   ```

3. **Configuration Issues**
   ```php
   // Check module configuration
   $config = ModuleRegistry::getModuleConfig('my_module');
   ```

### **Debugging**

1. **Enable Logging**
   ```php
   Log::info('Module processing started', ['module' => 'my_module']);
   ```

2. **Check Module Status**
   ```php
   $enabled = ModuleRegistry::isModuleEnabled('my_module');
   ```

3. **Get Module Info**
   ```php
   $module = ModuleRegistry::getAllModules()['my_module'];
   ```

## **📈 Best Practices**

### **1. Module Design**
- Keep modules focused on single responsibility
- Use dependency injection
- Handle errors gracefully
- Provide comprehensive metadata

### **2. Configuration**
- Use environment variables for sensitive settings
- Provide sensible defaults
- Document all configuration options

### **3. Testing**
- Test modules in isolation
- Test module interactions
- Test error scenarios
- Test performance with large content

### **4. Documentation**
- Document module purpose and usage
- Provide configuration examples
- Include troubleshooting guides
- Update when adding new features

## **🔄 Migration Guide**

### **From Old Architecture**

1. **Update Controllers**
   ```php
   // Old way
   $result = $this->youtubeService->process($input);
   
   // New way
   $result = $this->unifiedProcessingService->processYouTubeVideo($input);
   ```

2. **Update Services**
   ```php
   // Old way
   $content = $this->extractContent($input);
   $summary = $this->summarize($content);
   
   // New way
   $result = $this->unifiedProcessingService->processContent($input, 'youtube');
   ```

3. **Update Configuration**
   ```php
   // Move configuration to config/ai.php
   // Use environment variables for sensitive settings
   ```

## **🎯 Future Enhancements**

### **Planned Features**
- **Vector Database Integration**: For semantic chunking
- **Multi-language Processing**: Enhanced language detection
- **Real-time Processing**: WebSocket support
- **Batch Processing**: Queue-based processing
- **API Rate Limiting**: Intelligent rate limiting
- **Caching**: Redis-based result caching

### **Extensibility Points**
- **Custom Chunking Strategies**: Implement domain-specific chunking
- **Custom Summarization**: Add specialized summarization models
- **Custom Extractors**: Add new content source types
- **Custom Processors**: Add new processing pipelines

---

## **📞 Support**

For questions or issues with the modular architecture:

1. Check the troubleshooting section
2. Review module documentation
3. Check configuration settings
4. Enable debug logging
5. Contact the development team

---

**Last Updated**: January 2025  
**Version**: 1.0.0  
**Maintainer**: Development Team
