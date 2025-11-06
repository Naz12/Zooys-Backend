# 🚀 Postman Collection - Import Guide

## 📦 What You Get

Two files that contain **everything** pre-configured:

1. **`Zooys_PDF_Operations.postman_collection.json`** - All API endpoints
2. **`Zooys_PDF_Operations.postman_environment.json`** - Environment variables

---

## 📥 How to Import (Super Easy!)

### Step 1: Import the Collection

1. Open **Postman**
2. Click **"Import"** button (top left)
3. Drag & drop `Zooys_PDF_Operations.postman_collection.json` OR click "Upload Files"
4. Click **"Import"**

✅ Done! You'll see **"Zooys PDF Operations"** collection in your sidebar.

---

### Step 2: Import the Environment

1. Click **"Environments"** icon (left sidebar, looks like an eye 👁️)
2. Click **"Import"** at the top
3. Drag & drop `Zooys_PDF_Operations.postman_environment.json`
4. Click **"Import"**

✅ Done! You'll see **"Zooys PDF Operations - Local"** environment.

---

### Step 3: Activate the Environment

1. Click the **dropdown** in the top-right corner (says "No Environment")
2. Select **"Zooys PDF Operations - Local"**

✅ Environment activated! All `{{variables}}` will work automatically.

---

### Step 4: Update Your Token (One-Time Setup)

1. Click **Environments** icon 👁️
2. Click **"Zooys PDF Operations - Local"**
3. Update these values:

| Variable | Current Value | Your Value |
|----------|---------------|------------|
| `base_url` | `localhost:8000/api` | ✅ Keep as is |
| `bearer_token` | `207\|vhs65...` | 🔄 **Replace with YOUR token** |
| `file_id` | `204` | 🔄 **Replace with YOUR file ID** (or it auto-updates when you upload) |
| `job_id` | (empty) | ✅ Keep empty (auto-saves) |

4. Click **"Save"** (Ctrl+S)

---

## 🎯 How to Use

### Quick Test Workflow

1. **Upload a File**:
   - Open: `1. File Upload` → `Upload Single File`
   - Click **"Select Files"** in the body
   - Upload a PDF
   - Click **"Send"**
   - ✅ `file_id` is **auto-saved**!

2. **Run Any Operation**:
   - Example: `2. PDF Merge` → `Submit Merge Job`
   - Click **"Send"**
   - ✅ `job_id` is **auto-saved**!

3. **Check Status** (Auto-Retry):
   - Click: `Check Merge Status`
   - Click **"Send"**
   - If status is `processing`, it will **auto-retry** every 2 seconds!
   - Wait until status is `completed`

4. **Get Result**:
   - Click: `Get Merge Result`
   - Click **"Send"**
   - ✅ Download URLs appear in response!

---

## 🧪 What's Pre-Configured?

### ✅ Auto-Save Variables
- Upload file → `file_id` auto-saves
- Submit job → `job_id` auto-saves
- No manual copying needed!

### ✅ Auto-Retry Status Checks
- Status endpoints automatically re-run if job is still processing
- Checks every 2 seconds until completed
- You can just sit back and watch!

### ✅ Smart Test Scripts
- Validates responses
- Logs download URLs to console
- Shows progress in console
- Error detection

### ✅ Pre-Filled Request Bodies
- All JSON bodies are ready to use
- Just update `file_id` values if needed
- All options are included with sensible defaults

---

## 📋 Collection Structure

```
Zooys PDF Operations/
├── 1. File Upload/
│   ├── Upload Single File
│   └── Upload Multiple Files
├── 2. PDF Merge/
│   ├── Submit Merge Job
│   ├── Check Merge Status (auto-retry)
│   └── Get Merge Result
├── 3. PDF Split/
│   ├── Submit Split Job
│   ├── Check Split Status (auto-retry)
│   └── Get Split Result
├── 4. PDF Compress/
│   ├── Submit Compress Job
│   ├── Check Compress Status (auto-retry)
│   └── Get Compress Result
├── 5. PDF Watermark/
│   ├── Submit Watermark Job
│   ├── Check Watermark Status (auto-retry)
│   └── Get Watermark Result
├── 6. PDF Page Numbers/
│   ├── Submit Page Numbers Job
│   ├── Check Page Numbers Status (auto-retry)
│   └── Get Page Numbers Result
├── 7. PDF Protect (Password)/
│   ├── Submit Protect Job
│   ├── Check Protect Status (auto-retry)
│   └── Get Protect Result
└── 8. Document Conversion/
    ├── Submit Convert Job
    ├── Check Convert Status (auto-retry)
    └── Get Convert Result
```

---

## 🔧 Viewing Auto-Saved Variables

### Option 1: Environment View
1. Click **Environments** 👁️
2. Click **"Zooys PDF Operations - Local"**
3. See all current values

### Option 2: Console View
1. Click **"Console"** at bottom (or Ctrl+Alt+C)
2. Run any request
3. See logged values like:
   ```
   File ID saved: 204
   Job ID saved: abc-123-xyz
   Status: processing (45%)
   ```

---

## 🎨 Custom Requests

### Example: Merge Specific Files

1. Open: `2. PDF Merge` → `Submit Merge Job`
2. Edit the body:
```json
{
  "file_ids": [204, 205, 206],  // Your file IDs
  "options": {
    "page_order": "as_uploaded",
    "remove_blank_pages": false,
    "add_page_numbers": true
  }
}
```
3. Click **"Send"**

---

## ⚡ Pro Tips

### Tip 1: Run Multiple Operations in Sequence
Use **Collection Runner**:
1. Right-click collection → **"Run collection"**
2. Select requests to run
3. Click **"Run Zooys PDF Operations"**
4. Watch all requests execute automatically!

### Tip 2: Save Responses
- Click **"Save Response"** → **"Save as example"**
- Helps you compare results later

### Tip 3: Use Console for Debugging
- **Console** shows all logs, errors, and auto-saved values
- Open: View → Show Postman Console (Ctrl+Alt+C)

### Tip 4: Export & Share
- Right-click collection → **"Export"**
- Share with team members
- All test scripts and configurations included!

---

## 🛠️ Troubleshooting

### ❌ "Authorization failed"
**Fix:** Update your `bearer_token` in the environment
1. Environments 👁️ → "Zooys PDF Operations - Local"
2. Update `bearer_token`
3. Save (Ctrl+S)

### ❌ "File not found"
**Fix:** Update `file_id` in the environment
1. Run "Upload Single File" first
2. `file_id` will auto-save
3. Or manually set it in Environments

### ❌ Status keeps checking forever
**Fix:** Check server logs or manually stop
1. Click **"Cancel Request"** in Postman
2. Check Laravel logs: `storage/logs/laravel.log`
3. Ensure queue worker is running: `php artisan queue:work`

### ❌ Variables not working
**Fix:** Ensure environment is active
1. Check top-right dropdown
2. Should say **"Zooys PDF Operations - Local"**
3. Not "No Environment"

---

## 📊 Request Flow Diagram

```
Upload File
    ↓
  [file_id saved]
    ↓
Submit Job (merge/split/compress/etc)
    ↓
  [job_id saved]
    ↓
Check Status (auto-retry every 2 seconds)
    ↓
  [wait for "completed"]
    ↓
Get Result
    ↓
  [download_urls in response]
    ↓
Download/Use Files
```

---

## 🌐 Endpoints Included

### File Management
- ✅ Upload Single File
- ✅ Upload Multiple Files

### PDF Operations
- ✅ Merge PDFs
- ✅ Split PDF
- ✅ Compress PDF
- ✅ Add Watermark
- ✅ Add Page Numbers
- ✅ Password Protect

### Document Conversion
- ✅ Convert (PDF ↔ DOCX ↔ HTML ↔ TXT)

**Total:** 22 pre-configured requests!

---

## 🎉 You're All Set!

No more copy-pasting! Everything is ready to use.

**Quick Start:**
1. Import collection
2. Import environment
3. Activate environment
4. Update bearer_token
5. Start testing! 🚀

---

**Questions?** Check the console logs - they show what's happening! 
**Errors?** Check Laravel logs: `storage/logs/laravel.log`

