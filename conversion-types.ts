/**
 * Document Conversion API - Frontend Type Definitions
 * 
 * This file provides TypeScript types and utilities for the document conversion endpoint.
 * Use this in your frontend application to type-check conversion requests and responses.
 */

// ============================================================================
// TYPE DEFINITIONS
// ============================================================================

/**
 * Supported input file types (16 formats)
 */
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

/**
 * Supported output file types (11 formats)
 */
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

/**
 * Conversion request payload
 */
export interface ConversionRequest {
  file_id: string;
  target_format: OutputFileType;
  options?: ConversionOptions;
}

/**
 * Conversion options
 */
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

/**
 * Conversion job response
 */
export interface ConversionResponse {
  success: boolean;
  message: string;
  job_id: string;
  status: 'pending' | 'running' | 'completed' | 'failed';
  poll_url: string;
  result_url: string;
}

/**
 * Conversion status response
 */
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

/**
 * Conversion result response
 */
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

// ============================================================================
// CONVERSION MATRIX
// ============================================================================

/**
 * Conversion matrix mapping input formats to available output formats
 * Based on microservice capabilities: 16 input types → 11 output types
 */
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

// ============================================================================
// FORMAT DISPLAY NAMES
// ============================================================================

/**
 * User-friendly display names for file formats
 */
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

// ============================================================================
// FORMAT CATEGORIES
// ============================================================================

/**
 * Format categories for grouping in UI
 */
export const FORMAT_CATEGORIES = {
  images: ['bmp', 'gif', 'jpeg', 'jpg', 'png'] as InputFileType[],
  documents: ['doc', 'docx', 'pdf'] as InputFileType[],
  presentations: ['ppt', 'pptx'] as InputFileType[],
  spreadsheets: ['xls', 'xlsx'] as InputFileType[],
  text: ['txt', 'md', 'html', 'htm'] as InputFileType[],
} as const;

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Get available output formats for an input format
 */
export function getAvailableOutputFormats(inputFormat: InputFileType): OutputFileType[] {
  return CONVERSION_MATRIX[inputFormat] || [];
}

/**
 * Check if a conversion is supported
 */
export function isConversionSupported(
  inputFormat: InputFileType,
  outputFormat: OutputFileType
): boolean {
  const available = CONVERSION_MATRIX[inputFormat] || [];
  return available.includes(outputFormat);
}

/**
 * Check if same-format conversion (not supported)
 */
export function isSameFormatConversion(
  inputFormat: InputFileType,
  outputFormat: OutputFileType
): boolean {
  const normalizedInput = inputFormat.toLowerCase();
  const normalizedOutput = outputFormat.toLowerCase();
  
  // Handle JPEG/JPG equivalence
  if ((normalizedInput === 'jpeg' || normalizedInput === 'jpg') && 
      (normalizedOutput === 'jpeg' || normalizedOutput === 'jpg')) {
    return true;
  }
  
  return normalizedInput === normalizedOutput;
}

/**
 * Get user-friendly format name
 */
export function getFormatDisplayName(format: InputFileType | OutputFileType): string {
  return FORMAT_DISPLAY_NAMES[format] || format.toUpperCase();
}

/**
 * Get category for a format
 */
export function getFormatCategory(format: InputFileType): string {
  for (const [category, formats] of Object.entries(FORMAT_CATEGORIES)) {
    if (formats.includes(format)) {
      return category;
    }
  }
  return 'other';
}

/**
 * Get file extension from filename
 */
export function getFileExtension(filename: string): InputFileType | null {
  const ext = filename.split('.').pop()?.toLowerCase();
  if (!ext) return null;
  
  // Normalize jpeg to jpg
  if (ext === 'jpeg') return 'jpg';
  
  return ext as InputFileType || null;
}

/**
 * Validate conversion request
 */
export function validateConversion(
  inputFormat: InputFileType | null,
  outputFormat: OutputFileType
): { valid: boolean; error?: string } {
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
}

/**
 * Get suggested output formats for an input (prioritized)
 */
export function getSuggestedFormats(inputFormat: InputFileType): OutputFileType[] {
  const available = getAvailableOutputFormats(inputFormat);
  
  // Priority order for common conversions
  const priority = ['pdf', 'docx', 'png', 'jpg', 'html'];
  const prioritized = available.filter(f => priority.includes(f));
  const others = available.filter(f => !priority.includes(f));
  
  return [...prioritized, ...others];
}

// ============================================================================
// CONSTANTS
// ============================================================================

/**
 * File size and conversion limits
 */
export const CONVERSION_LIMITS = {
  maxFileSizeMB: 500,
  maxPages: 150000,
  jobTTLHours: 24,
} as const;

/**
 * API Endpoints
 */
export const CONVERSION_ENDPOINTS = {
  convert: '/api/file-processing/convert',
  status: '/api/file-processing/convert/status',
  result: '/api/file-processing/convert/result',
  capabilities: '/api/file-processing/conversion-capabilities',
} as const;

