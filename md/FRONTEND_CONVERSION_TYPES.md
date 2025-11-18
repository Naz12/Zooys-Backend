# Frontend Conversion Types - Document Conversion API

This document provides TypeScript types and conversion mappings for the frontend to use with the document conversion endpoint.

---

## 📋 TypeScript Type Definitions

```typescript
// Supported input file types
export type InputFileType = 
  | 'bmp'   // Bitmap images
  | 'doc'   // Word documents (legacy)
  | 'docx'  // Word documents (modern)
  | 'gif'   // GIF images
  | 'htm'   // HTML files
  | 'html'  // HTML files
  | 'jpeg'  // JPEG images
  | 'jpg'   // JPEG images
  | 'md'    // Markdown files
  | 'pdf'   // PDF documents
  | 'png'   // PNG images
  | 'ppt'   // PowerPoint (legacy)
  | 'pptx'  // PowerPoint (modern)
  | 'txt'   // Plain text files
  | 'xls'   // Excel (legacy)
  | 'xlsx'; // Excel (modern)

// Supported output file types
export type OutputFileType =
  | 'doc'   // Word documents (legacy format)
  | 'docx'  // Word documents (modern format)
  | 'html'  // HTML web pages
  | 'jpg'   // JPEG images
  | 'md'    // Markdown files
  | 'pdf'   // PDF documents
  | 'png'   // PNG images
  | 'ppt'   // PowerPoint (legacy format)
  | 'pptx'  // PowerPoint (modern format)
  | 'xls'   // Excel (legacy format)
  | 'xlsx'; // Excel (modern format)

// Conversion request interface
export interface ConversionRequest {
  file_id: string;
  target_format: OutputFileType;
  options?: ConversionOptions;
}

// Conversion options interface
export interface ConversionOptions {
  quality?: 'low' | 'medium' | 'high';
  include_metadata?: boolean;
  dpi?: number; // 72-600
  page_range?: string; // e.g., "1-10", "2-5"
  page_size?: 'A4' | 'Letter' | 'Legal' | 'A3';
  orientation?: 'portrait' | 'landscape';
  margin?: string; // e.g., "1in", "2cm"
  include_speaker_notes?: boolean; // For PPTX conversions
}

// Conversion response interface
export interface ConversionResponse {
  success: boolean;
  message: string;
  job_id: string;
  status: 'pending' | 'running' | 'completed' | 'failed';
  poll_url: string;
  result_url: string;
}

// Status response interface
export interface ConversionStatus {
  job_id: string;
  tool_type: 'document_conversion';
  input_type: 'file';
  status: 'pending' | 'running' | 'completed' | 'failed';
  progress: number; // 0-100
  stage: string | null;
  error: string | null;
  created_at: string;
  updated_at: string;
}

// Result response interface
export interface ConversionResult {
  success: boolean;
  job_id: string;
  tool_type: 'document_conversion';
  input_type: 'file';
  data: {
    file_path: string;
    file_url: string;
    original_format: string;
    target_format: string;
    file_size: number;
    pages?: number;
    conversion_time?: number;
    metadata?: Record<string, any>;
  };
}
```

---

## 🔄 Conversion Matrix

```typescript
// Conversion combinations map
export const CONVERSION_MATRIX: Record<InputFileType, OutputFileType[]> = {
  // Image formats
  'bmp': ['jpg', 'pdf', 'png'],
  'gif': ['jpg', 'pdf', 'png'],
  'jpeg': ['jpg', 'pdf', 'png'],
  'jpg': ['jpg', 'pdf', 'png'],
  'png': ['jpg', 'pdf', 'png'],
  
  // Office documents (legacy)
  'doc': ['html', 'jpg', 'md', 'pdf', 'png', 'pptx', 'xlsx'],
  'ppt': ['docx', 'html', 'jpg', 'md', 'pdf', 'png', 'xlsx'],
  'xls': ['docx', 'html', 'jpg', 'md', 'pdf', 'png', 'pptx'],
  
  // Office documents (modern)
  'docx': ['html', 'jpg', 'md', 'pdf', 'png', 'pptx', 'xlsx'],
  'pptx': ['docx', 'html', 'jpg', 'md', 'pdf', 'png', 'xlsx'],
  'xlsx': ['docx', 'html', 'jpg', 'md', 'pdf', 'png', 'pptx'],
  
  // PDF documents
  'pdf': ['doc', 'docx', 'html', 'jpg', 'md', 'png', 'ppt', 'pptx', 'xls', 'xlsx'],
  
  // Text/Markup formats
  'txt': ['doc', 'docx', 'html', 'jpg', 'md', 'pdf', 'png', 'ppt', 'pptx', 'xls', 'xlsx'],
  'md': ['doc', 'docx', 'html', 'jpg', 'pdf', 'png', 'ppt', 'pptx', 'xls', 'xlsx'],
  'html': ['doc', 'docx', 'jpg', 'md', 'pdf', 'png', 'ppt', 'pptx', 'xls', 'xlsx'],
  'htm': ['docx', 'jpg', 'md', 'pdf', 'png'],
};

// Helper function to get available output formats for an input format
export function getAvailableOutputFormats(inputFormat: InputFileType): OutputFileType[] {
  return CONVERSION_MATRIX[inputFormat] || [];
}

// Helper function to check if a conversion is supported
export function isConversionSupported(
  inputFormat: InputFileType,
  outputFormat: OutputFileType
): boolean {
  const available = CONVERSION_MATRIX[inputFormat] || [];
  return available.includes(outputFormat);
}

// Helper function to check if same-format conversion (not supported)
export function isSameFormatConversion(
  inputFormat: InputFileType,
  outputFormat: OutputFileType
): boolean {
  // Normalize formats for comparison
  const normalizedInput = inputFormat.toLowerCase();
  const normalizedOutput = outputFormat.toLowerCase();
  
  // Handle JPEG/JPG equivalence
  if ((normalizedInput === 'jpeg' || normalizedInput === 'jpg') && 
      (normalizedOutput === 'jpeg' || normalizedOutput === 'jpg')) {
    return true;
  }
  
  return normalizedInput === normalizedOutput;
}
```

---

## 📊 JSON Format (for API/Config)

```json
{
  "supported_inputs": [
    "bmp", "doc", "docx", "gif", "htm", "html", "jpeg", "jpg", 
    "md", "pdf", "png", "ppt", "pptx", "txt", "xls", "xlsx"
  ],
  "supported_outputs": [
    "doc", "docx", "html", "jpg", "md", "pdf", "png", 
    "ppt", "pptx", "xls", "xlsx"
  ],
  "conversion_matrix": {
    "bmp": ["jpg", "pdf", "png"],
    "doc": ["html", "jpg", "md", "pdf", "png", "pptx", "xlsx"],
    "docx": ["html", "jpg", "md", "pdf", "png", "pptx", "xlsx"],
    "gif": ["jpg", "pdf", "png"],
    "htm": ["docx", "jpg", "md", "pdf", "png"],
    "html": ["doc", "docx", "jpg", "md", "pdf", "png", "ppt", "pptx", "xls", "xlsx"],
    "jpeg": ["jpg", "pdf", "png"],
    "jpg": ["jpg", "pdf", "png"],
    "md": ["doc", "docx", "html", "jpg", "pdf", "png", "ppt", "pptx", "xls", "xlsx"],
    "pdf": ["doc", "docx", "html", "jpg", "md", "png", "ppt", "pptx", "xls", "xlsx"],
    "png": ["jpg", "pdf", "png"],
    "ppt": ["docx", "html", "jpg", "md", "pdf", "png", "xlsx"],
    "pptx": ["docx", "html", "jpg", "md", "pdf", "png", "xlsx"],
    "txt": ["doc", "docx", "html", "jpg", "md", "pdf", "png", "ppt", "pptx", "xls", "xlsx"],
    "xls": ["docx", "html", "jpg", "md", "pdf", "png", "pptx"],
    "xlsx": ["docx", "html", "jpg", "md", "pdf", "png", "pptx"]
  },
  "file_limits": {
    "max_file_size_mb": 500,
    "max_pages": 150000,
    "job_ttl_hours": 24
  },
  "restrictions": {
    "same_format_conversion": false,
    "message": "Converting a file to the same format is not supported"
  }
}
```

---

## 🎯 Frontend Usage Examples

### React/TypeScript Example

```typescript
import { 
  InputFileType, 
  OutputFileType, 
  ConversionRequest,
  getAvailableOutputFormats,
  isConversionSupported,
  isSameFormatConversion
} from './conversion-types';

// Get file extension from uploaded file
function getFileExtension(filename: string): InputFileType | null {
  const ext = filename.split('.').pop()?.toLowerCase();
  return ext as InputFileType || null;
}

// Component example
function ConversionForm({ fileId, fileName }: { fileId: string; fileName: string }) {
  const inputFormat = getFileExtension(fileName);
  const [targetFormat, setTargetFormat] = useState<OutputFileType>('pdf');
  
  // Get available output formats for the input file
  const availableFormats = inputFormat 
    ? getAvailableOutputFormats(inputFormat)
    : [];
  
  // Filter out same-format conversions
  const validFormats = availableFormats.filter(format => 
    !isSameFormatConversion(inputFormat!, format)
  );
  
  const handleConvert = async () => {
    if (!isConversionSupported(inputFormat!, targetFormat)) {
      alert('This conversion is not supported');
      return;
    }
    
    if (isSameFormatConversion(inputFormat!, targetFormat)) {
      alert('Cannot convert to the same format');
      return;
    }
    
    const request: ConversionRequest = {
      file_id: fileId,
      target_format: targetFormat,
      options: {
        quality: 'high',
        include_metadata: true
      }
    };
    
    // Make API call
    const response = await fetch('/api/file-processing/convert', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(request)
    });
    
    const data: ConversionResponse = await response.json();
    // Handle response...
  };
  
  return (
    <div>
      <label>Convert to:</label>
      <select 
        value={targetFormat} 
        onChange={(e) => setTargetFormat(e.target.value as OutputFileType)}
      >
        {validFormats.map(format => (
          <option key={format} value={format}>
            {format.toUpperCase()}
          </option>
        ))}
      </select>
      <button onClick={handleConvert}>Convert</button>
    </div>
  );
}
```

---

## 📝 Format Display Names

```typescript
export const FORMAT_DISPLAY_NAMES: Record<InputFileType | OutputFileType, string> = {
  'bmp': 'Bitmap Image',
  'doc': 'Microsoft Word (Legacy)',
  'docx': 'Microsoft Word',
  'gif': 'GIF Image',
  'htm': 'HTML File',
  'html': 'HTML Document',
  'jpeg': 'JPEG Image',
  'jpg': 'JPEG Image',
  'md': 'Markdown',
  'pdf': 'PDF Document',
  'png': 'PNG Image',
  'ppt': 'PowerPoint (Legacy)',
  'pptx': 'PowerPoint',
  'txt': 'Plain Text',
  'xls': 'Excel (Legacy)',
  'xlsx': 'Excel',
};

// Get user-friendly format name
export function getFormatDisplayName(format: InputFileType | OutputFileType): string {
  return FORMAT_DISPLAY_NAMES[format] || format.toUpperCase();
}
```

---

## 🔍 Format Categories

```typescript
export const FORMAT_CATEGORIES = {
  images: ['bmp', 'gif', 'jpeg', 'jpg', 'png'] as InputFileType[],
  documents: ['doc', 'docx', 'pdf'] as InputFileType[],
  presentations: ['ppt', 'pptx'] as InputFileType[],
  spreadsheets: ['xls', 'xlsx'] as InputFileType[],
  text: ['txt', 'md', 'html', 'htm'] as InputFileType[],
};

// Get category for a format
export function getFormatCategory(format: InputFileType): string {
  for (const [category, formats] of Object.entries(FORMAT_CATEGORIES)) {
    if (formats.includes(format)) {
      return category;
    }
  }
  return 'other';
}
```

---

## ⚠️ Validation Rules

```typescript
export const VALIDATION_RULES = {
  // Check if conversion is valid
  validateConversion: (
    inputFormat: InputFileType | null,
    outputFormat: OutputFileType
  ): { valid: boolean; error?: string } => {
    if (!inputFormat) {
      return { valid: false, error: 'Input file format not detected' };
    }
    
    if (isSameFormatConversion(inputFormat, outputFormat)) {
      return { 
        valid: false, 
        error: `Cannot convert ${inputFormat} to ${outputFormat}. Same-format conversion is not supported.` 
      };
    }
    
    if (!isConversionSupported(inputFormat, outputFormat)) {
      return { 
        valid: false, 
        error: `Conversion from ${inputFormat} to ${outputFormat} is not supported.` 
      };
    }
    
    return { valid: true };
  },
  
  // Get suggested formats for an input
  getSuggestedFormats: (inputFormat: InputFileType): OutputFileType[] => {
    const available = getAvailableOutputFormats(inputFormat);
    
    // Prioritize common conversions
    const priority = ['pdf', 'docx', 'png', 'jpg', 'html'];
    const prioritized = available.filter(f => priority.includes(f));
    const others = available.filter(f => !priority.includes(f));
    
    return [...prioritized, ...others];
  }
};
```

---

## 📦 Complete TypeScript File

Save this as `conversion-types.ts`:

```typescript
// conversion-types.ts
export type InputFileType = 
  | 'bmp' | 'doc' | 'docx' | 'gif' | 'htm' | 'html' | 'jpeg' | 'jpg' 
  | 'md' | 'pdf' | 'png' | 'ppt' | 'pptx' | 'txt' | 'xls' | 'xlsx';

export type OutputFileType =
  | 'doc' | 'docx' | 'html' | 'jpg' | 'md' | 'pdf' | 'png' 
  | 'ppt' | 'pptx' | 'xls' | 'xlsx';

export interface ConversionRequest {
  file_id: string;
  target_format: OutputFileType;
  options?: ConversionOptions;
}

export interface ConversionOptions {
  quality?: 'low' | 'medium' | 'high';
  include_metadata?: boolean;
  dpi?: number;
  page_range?: string;
  page_size?: 'A4' | 'Letter' | 'Legal' | 'A3';
  orientation?: 'portrait' | 'landscape';
  margin?: string;
  include_speaker_notes?: boolean;
}

export interface ConversionResponse {
  success: boolean;
  message: string;
  job_id: string;
  status: 'pending' | 'running' | 'completed' | 'failed';
  poll_url: string;
  result_url: string;
}

export interface ConversionStatus {
  job_id: string;
  tool_type: 'document_conversion';
  input_type: 'file';
  status: 'pending' | 'running' | 'completed' | 'failed';
  progress: number;
  stage: string | null;
  error: string | null;
  created_at: string;
  updated_at: string;
}

export interface ConversionResult {
  success: boolean;
  job_id: string;
  tool_type: 'document_conversion';
  input_type: 'file';
  data: {
    file_path: string;
    file_url: string;
    original_format: string;
    target_format: string;
    file_size: number;
    pages?: number;
    conversion_time?: number;
    metadata?: Record<string, any>;
  };
}

export const CONVERSION_MATRIX: Record<InputFileType, OutputFileType[]> = {
  'bmp': ['jpg', 'pdf', 'png'],
  'doc': ['html', 'jpg', 'md', 'pdf', 'png', 'pptx', 'xlsx'],
  'docx': ['html', 'jpg', 'md', 'pdf', 'png', 'pptx', 'xlsx'],
  'gif': ['jpg', 'pdf', 'png'],
  'htm': ['docx', 'jpg', 'md', 'pdf', 'png'],
  'html': ['doc', 'docx', 'jpg', 'md', 'pdf', 'png', 'ppt', 'pptx', 'xls', 'xlsx'],
  'jpeg': ['jpg', 'pdf', 'png'],
  'jpg': ['jpg', 'pdf', 'png'],
  'md': ['doc', 'docx', 'html', 'jpg', 'pdf', 'png', 'ppt', 'pptx', 'xls', 'xlsx'],
  'pdf': ['doc', 'docx', 'html', 'jpg', 'md', 'png', 'ppt', 'pptx', 'xls', 'xlsx'],
  'png': ['jpg', 'pdf', 'png'],
  'ppt': ['docx', 'html', 'jpg', 'md', 'pdf', 'png', 'xlsx'],
  'pptx': ['docx', 'html', 'jpg', 'md', 'pdf', 'png', 'xlsx'],
  'txt': ['doc', 'docx', 'html', 'jpg', 'md', 'pdf', 'png', 'ppt', 'pptx', 'xls', 'xlsx'],
  'xls': ['docx', 'html', 'jpg', 'md', 'pdf', 'png', 'pptx'],
  'xlsx': ['docx', 'html', 'jpg', 'md', 'pdf', 'png', 'pptx'],
};

export const FORMAT_DISPLAY_NAMES: Record<InputFileType | OutputFileType, string> = {
  'bmp': 'Bitmap Image',
  'doc': 'Microsoft Word (Legacy)',
  'docx': 'Microsoft Word',
  'gif': 'GIF Image',
  'htm': 'HTML File',
  'html': 'HTML Document',
  'jpeg': 'JPEG Image',
  'jpg': 'JPEG Image',
  'md': 'Markdown',
  'pdf': 'PDF Document',
  'png': 'PNG Image',
  'ppt': 'PowerPoint (Legacy)',
  'pptx': 'PowerPoint',
  'txt': 'Plain Text',
  'xls': 'Excel (Legacy)',
  'xlsx': 'Excel',
};

export function getAvailableOutputFormats(inputFormat: InputFileType): OutputFileType[] {
  return CONVERSION_MATRIX[inputFormat] || [];
}

export function isConversionSupported(
  inputFormat: InputFileType,
  outputFormat: OutputFileType
): boolean {
  const available = CONVERSION_MATRIX[inputFormat] || [];
  return available.includes(outputFormat);
}

export function isSameFormatConversion(
  inputFormat: InputFileType,
  outputFormat: OutputFileType
): boolean {
  const normalizedInput = inputFormat.toLowerCase();
  const normalizedOutput = outputFormat.toLowerCase();
  
  if ((normalizedInput === 'jpeg' || normalizedInput === 'jpg') && 
      (normalizedOutput === 'jpeg' || normalizedOutput === 'jpg')) {
    return true;
  }
  
  return normalizedInput === normalizedOutput;
}

export function getFormatDisplayName(format: InputFileType | OutputFileType): string {
  return FORMAT_DISPLAY_NAMES[format] || format.toUpperCase();
}
```

---

**Last Updated:** November 17, 2025

