# 📄 Document Conversion Test Guide

## ✅ Test Script Created

A comprehensive test script has been created to test **all input/output format combinations** for document conversion.

---

## 🎯 Supported Conversion Formats

### **Supported Output Formats**
- `pdf` - Portable Document Format
- `png` - PNG Image
- `jpg` / `jpeg` - JPEG Image
- `docx` - Microsoft Word Document
- `txt` - Plain Text
- `html` - HTML Document

### **Supported Input Formats**
Based on the microservice capabilities:
- PDF files (`.pdf`)
- Word documents (`.doc`, `.docx`)
- Images (`.jpg`, `.jpeg`, `.png`, `.gif`, `.bmp`)
- Text files (`.txt`)
- HTML files (`.html`)
- And more (see microservice documentation)

---

## 📋 What the Test Does

The test script will:

1. ✅ **Test all combinations** of input → output format conversions
2. ✅ **Track success/failure** for each combination
3. ✅ **Measure processing time** for each conversion
4. ✅ **Generate detailed reports**:
   - Console output with color-coded results
   - JSON export with full test results
   - Conversion matrix showing all combinations
5. ✅ **Validate job processing**:
   - Job submission
   - Status polling
   - Result retrieval

---

## 🚀 How to Run the Test

### **Step 1: Prepare Test Files**

First, upload test files to get their IDs. You need at least:

```powershell
# Required files
- PDF file (ID: 207) ✅ Already configured
- JPEG image (ID: 204) ✅ Already configured

# Optional files (for comprehensive testing)
- DOCX file
- TXT file
- PNG image
- HTML file
```

Upload files using:
```http
POST http://localhost:8000/api/files/upload
Authorization: Bearer YOUR_TOKEN
Content-Type: multipart/form-data

file: [your file]
```

### **Step 2: Update Script Configuration**

Edit `test_all_conversions.ps1` and update:

```powershell
# Line 5: Update your bearer token
$TOKEN = "YOUR_BEARER_TOKEN_HERE"

# Lines 20-25: Update file IDs
$testFiles = @{
    "pdf" = @{ "file_id" = "207"; ... }          # ✅ Already set
    "docx" = @{ "file_id" = "YOUR_DOCX_ID"; ... }  # Update this
    "txt" = @{ "file_id" = "YOUR_TXT_ID"; ... }    # Update this
    "image_jpg" = @{ "file_id" = "204"; ... }    # ✅ Already set
    "image_png" = @{ "file_id" = "YOUR_PNG_ID"; ... }  # Update this
    "html" = @{ "file_id" = "YOUR_HTML_ID"; ... }      # Update this
}
```

### **Step 3: Start Laravel Server**

⚠️ **IMPORTANT**: The Laravel backend must be running!

```bash
# Start the Laravel server
cd C:\xampp\htdocs\zooys_backend_laravel-main
php artisan serve

# Or if using Laravel Valet/Herd
# Just ensure your server is running on http://localhost:8000
```

Verify server is running:
```powershell
Test-NetConnection -ComputerName localhost -Port 8000
```

Should show: `TcpTestSucceeded : True`

### **Step 4: Start Queue Worker**

The conversion jobs run in the background, so you need a queue worker:

```bash
# In a separate terminal
php artisan queue:work --timeout=0
```

Or use the provided batch file:
```bash
start_queue_worker.bat
```

### **Step 5: Run the Test**

```powershell
.\test_all_conversions.ps1
```

The script will:
1. Show test file configuration
2. Ask for confirmation
3. Run all conversions
4. Display live progress with color coding
5. Generate final report
6. Export results to `conversion_test_results.json`

---

## 📊 Understanding the Results

### **Console Output**

Live test progress:
```
Testing: PDF file → pdf ... [OK] SUCCESS
    Time: 3.45s

Testing: PDF file → png ... [X] FAILED (Job)
    Error: Unsupported conversion

Testing: JPEG image → pdf ... [OK] SUCCESS
    Time: 5.21s
```

### **Summary Report**

```
=====================================
        TEST SUMMARY REPORT
=====================================

Total Tests: 42
Successful: 25
Failed: 12
Skipped: 5
```

### **Conversion Matrix**

Visual overview of all combinations:

```
Input Format    │    PDF   PNG   JPG  JPEG  DOCX   TXT  HTML
────────────────┼───────────────────────────────────────────────
PDF file        │  [OK]   [OK]   [OK]  [OK]   [ ]   [OK]  [OK]
JPEG image      │  [OK]   [OK]   [OK]  [OK]   [ ]   [ ]   [ ]
Word document   │  [OK]   [ ]    [ ]   [ ]   [OK]  [OK]  [OK]
Text file       │  [OK]   [ ]    [ ]   [ ]   [ ]   [OK]  [OK]
PNG image       │  [OK]   [OK]   [OK]  [OK]   [ ]   [ ]   [ ]
HTML file       │  [OK]   [ ]    [ ]   [ ]   [OK]  [OK]  [OK]
```

**Legend:**
- `[OK]` = Conversion successful
- `[X]` = Conversion failed
- `[!!]` = Error occurred
- `[ ]` = Skipped (no input file)

### **JSON Export**

Complete test results in `conversion_test_results.json`:

```json
{
  "timestamp": "2025-11-04 11:31:34",
  "summary": {
    "total_tests": 42,
    "successful": 25,
    "failed": 12,
    "skipped": 5
  },
  "results": [
    {
      "input": "pdf",
      "input_desc": "PDF file",
      "output": "png",
      "status": "SUCCESS",
      "message": "Conversion completed",
      "job_id": "uuid-123",
      "processing_time": 3.45,
      "download_url": "http://..."
    },
    ...
  ]
}
```

---

## 🐛 Troubleshooting

### **Issue: "Unable to connect to the remote server"**

**Cause**: Laravel server is not running

**Solution**:
```bash
# Start Laravel server
php artisan serve
```

### **Issue: "All tests timing out"**

**Cause**: Queue worker is not running

**Solution**:
```bash
# Start queue worker
php artisan queue:work --timeout=0
```

### **Issue: "File not found" errors**

**Cause**: Invalid file IDs in script configuration

**Solution**:
1. Upload test files via `/api/files/upload`
2. Update file IDs in script

### **Issue: "Job failed" errors**

**Cause**: Microservice is down or misconfigured

**Solution**:
```bash
# Check microservice health
curl http://localhost:8004/health
```

---

## 📈 Expected Results

### **Common Working Combinations**

| Input | Output | Expected Result |
|-------|--------|-----------------|
| PDF → PDF | ✅ | Identity conversion (copy) |
| PDF → PNG | ✅ | PDF pages to PNG images |
| PDF → JPG | ✅ | PDF pages to JPEG images |
| PDF → TXT | ✅ | Text extraction |
| PDF → HTML | ✅ | Structured HTML output |
| Image (JPG/PNG) → PDF | ✅ | Image to PDF document |
| DOCX → PDF | ✅ | Word to PDF conversion |
| HTML → PDF | ✅ | HTML to PDF conversion |
| TXT → PDF | ✅ | Text to PDF document |

### **Unsupported Combinations**

Some combinations may not be supported by the microservice:
- Image → DOCX
- Image → TXT (unless OCR is enabled)
- TXT → Image (except as PDF)

---

## 🔧 Customization

### **Add More Input Types**

Edit the `$testFiles` hash in the script:

```powershell
$testFiles = @{
    "your_format" = @{ 
        "file_id" = "YOUR_FILE_ID"
        "name" = "test.ext"
        "description" = "Your File Type"
    }
}
```

### **Add More Output Formats**

Edit the `$targetFormats` array:

```powershell
$targetFormats = @("pdf", "png", "jpg", "your_format")
```

### **Adjust Timeouts**

Edit the `Wait-JobCompletion` function:

```powershell
# Line 38
function Wait-JobCompletion {
    param (
        [string]$jobId,
        [int]$maxAttempts = 60  # Change this (default: 30)
    )
    ...
}
```

---

## 📝 Files Created

| File | Description |
|------|-------------|
| `test_all_conversions.ps1` | Main test script |
| `conversion_test_results.json` | Test results (generated) |
| `md/CONVERSION_TEST_GUIDE.md` | This documentation |

---

## ✅ Summary

**Test Capabilities:**
- ✅ Tests all input/output format combinations
- ✅ Measures processing time
- ✅ Tracks job lifecycle (submit → poll → result)
- ✅ Generates detailed reports
- ✅ Exports JSON results
- ✅ Color-coded console output
- ✅ Conversion matrix visualization

**Prerequisites:**
- Laravel server running (`php artisan serve`)
- Queue worker running (`php artisan queue:work`)
- Test files uploaded with valid IDs
- Bearer token for authentication
- Microservice running and accessible

**Output:**
- Live console feedback
- Summary report with statistics
- Conversion matrix
- JSON export for further analysis

---

## 🎯 Next Steps

1. ✅ **Start Laravel server** - `php artisan serve`
2. ✅ **Start queue worker** - `php artisan queue:work --timeout=0`
3. ✅ **Update token** in script (if expired)
4. ✅ **Upload test files** (optional: DOCX, TXT, PNG, HTML)
5. ✅ **Run the test** - `.\test_all_conversions.ps1`
6. ✅ **Review results** - Check console and JSON output

---

**Ready to test!** 🚀




















