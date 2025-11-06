# 📍 Zooys Backend - Complete API Endpoints

**Generated:** November 4, 2025  
**Base URL:** `http://localhost:8000/api`

---

## 📑 **Table of Contents**

1. [Public Endpoints](#1-public-endpoints)
2. [Authentication & User](#2-authentication--user)
3. [File Management](#3-file-management)
4. [Summarization](#4-summarization)
5. [AI Tools](#5-ai-tools)
6. [Document Processing](#6-document-processing)
7. [PDF Operations](#7-pdf-operations)
8. [Document Intelligence](#8-document-intelligence)
9. [Presentations](#9-presentations)
10. [Chat & Messaging](#10-chat--messaging)
11. [Subscription & Billing](#11-subscription--billing)
12. [Universal Job Status/Result](#12-universal-job-statusresult)
13. [Admin - Authentication](#13-admin---authentication)
14. [Admin - Dashboard](#14-admin---dashboard)
15. [Admin - User Management](#15-admin---user-management)
16. [Admin - Plans & Subscriptions](#16-admin---plans--subscriptions)
17. [Admin - Tools & Analytics](#17-admin---tools--analytics)
18. [Admin - System](#18-admin---system)

---

## 1. **Public Endpoints**

### **Plans**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/plans` | Get all available plans |

### **Authentication**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/register` | Register new user |
| `POST` | `/login` | User login |

### **Stripe**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/stripe/webhook` | Stripe webhook handler |

---

## 2. **Authentication & User**
🔐 **Requires Authentication**

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/logout` | Logout user |
| `GET` | `/user` | Get current user |

---

## 3. **File Management**
🔐 **Requires Authentication**

### **Upload & Management**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/files/upload` | Upload file(s) |
| `POST` | `/files/test-upload` | Test file upload (debug) |
| `GET` | `/files` | List user's files |
| `GET` | `/files/{id}` | Get file details |
| `GET` | `/files/{id}/content` | Get file content |
| `DELETE` | `/files/{id}` | Delete file |

### **Presentation Downloads**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/files/download/{filename}` | Download presentation file |

---

## 4. **Summarization**

### **Universal Summarization** (Public with Bearer Token)
| Method | Endpoint | Description | Input Type |
|--------|----------|-------------|------------|
| `POST` | `/summarize/async/youtube` | Summarize YouTube video | YouTube URL |
| `POST` | `/summarize/async/text` | Summarize text | Raw text |
| `POST` | `/summarize/async/file` | Summarize file (PDF/Doc/Image) | File ID |
| `POST` | `/summarize/async/audiovideo` | Summarize audio/video | File ID |
| `POST` | `/summarize/link` | Summarize any URL | URL |

### **Tool-Specific Status & Result**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/status/summarize/text?job_id=<id>` | Get text summarization status |
| `GET` | `/result/summarize/text?job_id=<id>` | Get text summarization result |
| `GET` | `/status/summarize/youtube?job_id=<id>` | Get YouTube summarization status |
| `GET` | `/result/summarize/youtube?job_id=<id>` | Get YouTube summarization result |
| `GET` | `/status/summarize/file?job_id=<id>` | Get file summarization status |
| `GET` | `/result/summarize/file?job_id=<id>` | Get file summarization result |
| `GET` | `/status/summarize/web?job_id=<id>` | Get web summarization status |
| `GET` | `/result/summarize/web?job_id=<id>` | Get web summarization result |

### **Admin Summarization** 🔐
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/admin/summarize` | Admin summarize (sync) |
| `POST` | `/admin/summarize/async` | Admin summarize (async) |
| `POST` | `/admin/summarize/validate` | Validate file for summarization |

---

## 5. **AI Tools**
🔐 **Requires Authentication**

### **Writer**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/writer/run` | Run AI writer |

### **Math Solver**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/math/solve` | Solve math problem |
| `POST` | `/client/math/generate` | Generate math solution (alias) |
| `POST` | `/client/math/help` | Get math help (alias) |
| `GET` | `/math/problems` | List math problems |
| `GET` | `/math/problems/{id}` | Get specific problem |
| `DELETE` | `/math/problems/{id}` | Delete problem |
| `GET` | `/math/history` | Get math history |
| `GET` | `/math/stats` | Get math stats |
| `GET` | `/client/math/history` | Get math history (client) |
| `GET` | `/client/math/stats` | Get math stats (client) |

#### **Math Tool Status & Result**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/status/math/text?job_id=<id>` | Get math text status |
| `GET` | `/result/math/text?job_id=<id>` | Get math text result |
| `GET` | `/status/math/image?job_id=<id>` | Get math image status |
| `GET` | `/result/math/image?job_id=<id>` | Get math image result |

### **Flashcards**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/flashcards/generate` | Generate flashcards |
| `GET` | `/flashcards` | List user's flashcards |
| `GET` | `/flashcards/public` | Get public flashcards |
| `GET` | `/flashcards/{id}` | Get specific flashcard |
| `PUT` | `/flashcards/{id}` | Update flashcard |
| `DELETE` | `/flashcards/{id}` | Delete flashcard |

#### **Flashcard Status & Result**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/status/flashcards/text?job_id=<id>` | Get flashcard text status |
| `GET` | `/result/flashcards/text?job_id=<id>` | Get flashcard text result |
| `GET` | `/status/flashcards/file?job_id=<id>` | Get flashcard file status |
| `GET` | `/result/flashcards/file?job_id=<id>` | Get flashcard file result |

### **Diagrams**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/diagram/generate` | Generate diagram |

### **AI Results**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/ai-results` | List AI results |
| `GET` | `/ai-results/{id}` | Get specific result |
| `PUT` | `/ai-results/{id}` | Update result |
| `DELETE` | `/ai-results/{id}` | Delete result |
| `GET` | `/ai-results/stats` | Get AI result stats |

---

## 6. **Document Processing**
🔐 **Requires Authentication**

### **Conversion & Extraction**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/file-processing/convert` | Convert document |
| `POST` | `/file-processing/extract` | Extract content |
| `GET` | `/file-processing/conversion-capabilities` | Get conversion capabilities |
| `GET` | `/file-processing/extraction-capabilities` | Get extraction capabilities |
| `GET` | `/file-processing/health` | Check microservice health |

#### **Document Processing Status & Result**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/status/content_extraction/file?job_id=<id>` | Get extraction status |
| `GET` | `/result/content_extraction/file?job_id=<id>` | Get extraction result |
| `GET` | `/status/document_conversion/file?job_id=<id>` | Get conversion status |
| `GET` | `/result/document_conversion/file?job_id=<id>` | Get conversion result |

---

## 7. **PDF Operations**

### **PDF Edit Operations** 🔐
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/pdf/edit/{operation}` | Start PDF operation |

**Operations:** `merge`, `split`, `compress`, `watermark`, `page-numbers`, `annotate`, `protect`, `unlock`, `preview`, `batch`, `edit_pdf`

### **PDF Status & Result** (Public with Bearer Token)
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/pdf/edit/{operation}/status?job_id=<id>` | Get PDF operation status |
| `GET` | `/pdf/edit/{operation}/result?job_id=<id>` | Get PDF operation result |

---

## 8. **Document Intelligence**
🔐 **Requires Authentication**

### **Document Operations**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/documents/ingest` | Ingest document for RAG |
| `POST` | `/documents/search` | Semantic search |
| `POST` | `/documents/answer` | RAG-powered Q&A |
| `POST` | `/documents/chat` | Conversational chat |
| `GET` | `/documents/jobs/{jobId}/status` | Get job status |
| `GET` | `/documents/jobs/{jobId}/result` | Get job result |
| `GET` | `/documents/health` | Health check |

---

## 9. **Presentations**

### **Public Presentation Endpoints**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/presentations` | List presentations |
| `GET` | `/presentations/templates` | Get templates |
| `POST` | `/presentations/generate-outline` | Generate outline |
| `POST` | `/presentations/{aiResultId}/generate-content` | Generate content |
| `POST` | `/presentations/{aiResultId}/export` | Export presentation |
| `POST` | `/presentations/{aiResultId}/save` | Save presentation |
| `GET` | `/presentations/{aiResultId}/data` | Get presentation data |
| `DELETE` | `/presentations/{aiResultId}` | Delete presentation |

#### **Presentation Status & Result**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/status/presentations/text?job_id=<id>` | Get presentation text status |
| `GET` | `/result/presentations/text?job_id=<id>` | Get presentation text result |
| `GET` | `/status/presentations/file?job_id=<id>` | Get presentation file status |
| `GET` | `/result/presentations/file?job_id=<id>` | Get presentation file result |

### **Admin Presentation Endpoints** 🔐
| Method | Endpoint | Description |
|--------|----------|-------------|
| `PUT` | `/admin/presentations/{aiResultId}/update-outline` | Update outline |
| `POST` | `/admin/presentations/{aiResultId}/generate-content` | Generate content |
| `POST` | `/admin/presentations/{aiResultId}/generate-powerpoint` | Generate PowerPoint |
| `GET` | `/admin/presentations/{aiResultId}` | Get presentation |
| `GET` | `/admin/presentations/{aiResultId}/status` | Get progress status |
| `GET` | `/admin/presentations/microservice-status` | Check microservice status |

---

## 10. **Chat & Messaging**
🔐 **Requires Authentication**

### **AI Chat**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/chat` | Send chat message |
| `POST` | `/chat/create-and-chat` | Create session and chat |
| `GET` | `/chat/history` | Get chat history |

### **Chat Sessions**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/chat/sessions` | List sessions |
| `POST` | `/chat/sessions` | Create session |
| `GET` | `/chat/sessions/{sessionId}` | Get session |
| `PUT` | `/chat/sessions/{sessionId}` | Update session |
| `DELETE` | `/chat/sessions/{sessionId}` | Delete session |
| `POST` | `/chat/sessions/{sessionId}/archive` | Archive session |
| `POST` | `/chat/sessions/{sessionId}/restore` | Restore session |

### **Chat Messages**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/chat/sessions/{sessionId}/messages` | Send message |
| `GET` | `/chat/sessions/{sessionId}/messages` | List messages |
| `GET` | `/chat/sessions/{sessionId}/history` | Get message history |

### **Document Chat**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/chat/document` | Chat with document |
| `GET` | `/chat/document/{documentId}/history` | Get document chat history |

#### **Document Chat Status & Result**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/status/document_chat/file?job_id=<id>` | Get document chat status |
| `GET` | `/result/document_chat/file?job_id=<id>` | Get document chat result |

---

## 11. **Subscription & Billing**
🔐 **Requires Authentication**

### **Checkout**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/checkout` | Create checkout session |
| `GET` | `/checkout/verify/{sessionId}` | Verify checkout session |

### **Subscription**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/subscription` | Get current subscription |
| `GET` | `/subscription/history` | Get subscription history |
| `GET` | `/usage` | Get usage stats |

---

## 12. **Universal Job Status/Result**
(Public with Bearer Token)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/status?job_id=<id>` | Universal job status |
| `GET` | `/result?job_id=<id>` | Universal job result |

---

## 13. **Admin - Authentication**

### **Public Admin Auth**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/admin/login` | Admin login |
| `POST` | `/admin/password/reset` | Send reset link |
| `POST` | `/admin/password/reset/verify` | Verify reset token |
| `POST` | `/admin/password/reset/confirm` | Confirm password reset |

### **Protected Admin Auth** 🔐
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/admin/auth/logout` | Admin logout |
| `GET` | `/admin/auth/me` | Get current admin |
| `POST` | `/admin/auth/password/change` | Change password |

---

## 14. **Admin - Dashboard**
🔐 **Requires Admin Authentication**

### **General Dashboard**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/admin/dashboard` | Main dashboard |
| `GET` | `/admin/dashboard/stats` | Dashboard stats |
| `GET` | `/admin/dashboard/analytics` | Analytics overview |
| `GET` | `/admin/dashboard/revenue` | Revenue stats |
| `GET` | `/admin/dashboard/users` | User stats |
| `GET` | `/admin/dashboard/subscriptions` | Subscription stats |

### **Financial Metrics**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/admin/dashboard/mrr` | Monthly recurring revenue |
| `GET` | `/admin/dashboard/arr` | Annual recurring revenue |
| `GET` | `/admin/dashboard/subscription-growth` | Subscription growth |
| `GET` | `/admin/dashboard/revenue-by-plan` | Revenue by plan |
| `GET` | `/admin/dashboard/subscription-analytics` | Subscription analytics |

### **Processing Dashboard**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/admin/processing/overview` | Processing overview |
| `GET` | `/admin/processing/statistics` | Processing statistics |
| `GET` | `/admin/processing/performance` | Performance metrics |
| `GET` | `/admin/processing/health` | System health |
| `GET` | `/admin/processing/cache` | Cache statistics |
| `GET` | `/admin/processing/batch` | Batch statistics |
| `GET` | `/admin/processing/jobs` | Job statistics |
| `GET` | `/admin/processing/activity` | Recent activity |
| `GET` | `/admin/processing/trends` | Processing trends |
| `GET` | `/admin/processing/file-types` | File type distribution |
| `GET` | `/admin/processing/tools` | Tool usage distribution |
| `POST` | `/admin/processing/cache/clear` | Clear cache |
| `POST` | `/admin/processing/cache/warm` | Warm up cache |

---

## 15. **Admin - User Management**
🔐 **Requires Admin Authentication**

### **User CRUD**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/admin/users` | List all users |
| `POST` | `/admin/users` | Create user |
| `GET` | `/admin/users/{user}` | Get user details |
| `PUT` | `/admin/users/{user}` | Update user |
| `DELETE` | `/admin/users/{user}` | Delete user |

### **User Actions**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/admin/users/{user}/activate` | Activate user |
| `POST` | `/admin/users/{user}/deactivate` | Deactivate user |
| `POST` | `/admin/users/{user}/suspend` | Suspend user |
| `GET` | `/admin/users/{user}/subscriptions` | Get user subscriptions |
| `GET` | `/admin/users/{user}/usage` | Get user usage |
| `GET` | `/admin/users/{user}/activity` | Get user activity |

### **Bulk Operations**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/admin/users/bulk/activate` | Bulk activate users |
| `POST` | `/admin/users/bulk/deactivate` | Bulk deactivate users |
| `POST` | `/admin/users/bulk/delete` | Bulk delete users |

---

## 16. **Admin - Plans & Subscriptions**
🔐 **Requires Admin Authentication**

### **Plan Management**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/admin/plans` | List all plans |
| `POST` | `/admin/plans` | Create plan |
| `GET` | `/admin/plans/{plan}` | Get plan details |
| `PUT` | `/admin/plans/{plan}` | Update plan |
| `DELETE` | `/admin/plans/{plan}` | Delete plan |
| `POST` | `/admin/plans/{plan}/activate` | Activate plan |
| `POST` | `/admin/plans/{plan}/deactivate` | Deactivate plan |
| `GET` | `/admin/plans/{plan}/subscriptions` | Get plan subscriptions |
| `GET` | `/admin/plans/{plan}/analytics` | Get plan analytics |
| `POST` | `/admin/plans/bulk-update` | Bulk update plans |
| `POST` | `/admin/plans/{plan}/duplicate` | Duplicate plan |

### **Subscription Management**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/admin/subscriptions` | List all subscriptions |
| `POST` | `/admin/subscriptions` | Create subscription |
| `GET` | `/admin/subscriptions/{subscription}` | Get subscription details |
| `PUT` | `/admin/subscriptions/{subscription}` | Update subscription |
| `DELETE` | `/admin/subscriptions/{subscription}` | Delete subscription |

### **Subscription Actions**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/admin/subscriptions/{subscription}/activate` | Activate subscription |
| `POST` | `/admin/subscriptions/{subscription}/cancel` | Cancel subscription |
| `POST` | `/admin/subscriptions/{subscription}/pause` | Pause subscription |
| `POST` | `/admin/subscriptions/{subscription}/resume` | Resume subscription |
| `POST` | `/admin/subscriptions/{subscription}/upgrade` | Upgrade subscription |
| `POST` | `/admin/subscriptions/{subscription}/downgrade` | Downgrade subscription |
| `POST` | `/admin/subscriptions/{subscription}/apply-grace-period` | Apply grace period |
| `GET` | `/admin/subscriptions/{subscription}/payment-history` | Get payment history |

### **Subscription Bulk Operations**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/admin/subscriptions/bulk-activate` | Bulk activate |
| `POST` | `/admin/subscriptions/bulk-cancel` | Bulk cancel |

### **Subscription Analytics**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/admin/subscriptions/analytics/overview` | Analytics overview |
| `GET` | `/admin/subscriptions/analytics/revenue` | Revenue analytics |
| `GET` | `/admin/subscriptions/analytics/churn` | Churn analytics |
| `GET` | `/admin/subscriptions/analytics/conversion` | Conversion analytics |

---

## 17. **Admin - Tools & Analytics**
🔐 **Requires Admin Authentication**

### **Tool Management**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/admin/tools` | List all tools |
| `POST` | `/admin/tools` | Create tool |
| `GET` | `/admin/tools/{tool}` | Get tool details |
| `PUT` | `/admin/tools/{tool}` | Update tool |
| `DELETE` | `/admin/tools/{tool}` | Delete tool |
| `POST` | `/admin/tools/{tool}/activate` | Activate tool |
| `POST` | `/admin/tools/{tool}/deactivate` | Deactivate tool |
| `GET` | `/admin/tools/{tool}/usage` | Get tool usage |
| `GET` | `/admin/tools/{tool}/analytics` | Get tool analytics |
| `GET` | `/admin/tools/{tool}/users` | Get tool users |

### **Visitor Analytics**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/admin/visitors` | List visitors |
| `GET` | `/admin/visitors/analytics` | Visitor analytics |
| `GET` | `/admin/visitors/geographic` | Geographic data |
| `GET` | `/admin/visitors/demographic` | Demographic data |
| `GET` | `/admin/visitors/behavior` | Behavior analytics |
| `GET` | `/admin/visitors/sources` | Traffic sources |
| `GET` | `/admin/visitors/devices` | Device analytics |
| `GET` | `/admin/visitors/export` | Export visitor data |

### **Reports & Exports**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/admin/reports/users` | Export users report |
| `GET` | `/admin/reports/subscriptions` | Export subscriptions report |
| `GET` | `/admin/reports/revenue` | Export revenue report |
| `GET` | `/admin/reports/analytics` | Export analytics report |
| `GET` | `/admin/reports/usage` | Export usage report |

---

## 18. **Admin - System**
🔐 **Requires Admin Authentication**

### **System Management**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/admin/system/health` | System health check |
| `GET` | `/admin/system/logs` | System logs |
| `GET` | `/admin/system/performance` | Performance metrics |
| `GET` | `/admin/system/cache` | Cache status |
| `POST` | `/admin/system/cache/clear` | Clear cache |
| `GET` | `/admin/system/database` | Database status |
| `POST` | `/admin/system/maintenance` | Toggle maintenance mode |

---

## 📊 **Summary Statistics**

| Category | Count |
|----------|-------|
| **Public Endpoints** | 20 |
| **Authenticated Client Endpoints** | 85+ |
| **Admin Endpoints** | 130+ |
| **Tool-Specific Status/Result Endpoints** | 28 |
| **Universal Endpoints** | 2 |
| **Total Unique Endpoints** | **265+** |

---

## 🎯 **Key Endpoint Patterns**

### **Job-Based Async Operations**
Most AI tools follow this pattern:
1. **Start Job:** `POST /tool/operation` → Returns `job_id`
2. **Check Status:** `GET /status/tool/type?job_id=<id>` → Returns status/progress
3. **Get Result:** `GET /result/tool/type?job_id=<id>` → Returns final result

### **Universal Status/Result**
Can be used for any job type:
- `GET /status?job_id=<id>`
- `GET /result?job_id=<id>`

### **CRUD Pattern**
Most resources follow REST conventions:
- `GET /resource` - List all
- `POST /resource` - Create
- `GET /resource/{id}` - Get one
- `PUT /resource/{id}` - Update
- `DELETE /resource/{id}` - Delete

---

## 🔒 **Authentication**

### **Client Authentication**
- **Method:** Bearer Token (Sanctum)
- **Header:** `Authorization: Bearer <token>`

### **Admin Authentication**
- **Method:** Bearer Token (Sanctum) + Admin Role
- **Header:** `Authorization: Bearer <admin_token>`

---

## 📝 **Notes**

1. **Microservice Integration:** Many endpoints delegate to microservices:
   - PDF operations → PDF Microservice (localhost:8004)
   - Summarization → AI Manager (cloud)
   - Document Intelligence → Doc Service (cloud)
   - YouTube → YouTube Transcriber (cloud)

2. **Job Polling:** Async operations require polling status endpoint until `status === 'completed'`

3. **File IDs:** Most file operations use universal file IDs from the file upload system

4. **Test Endpoints:** Several test/debug endpoints exist for development (prefixed with `/test-`)

5. **CORS:** Several CORS OPTIONS routes exist for frontend compatibility

---

**Last Updated:** November 4, 2025  
**Generated by:** Cursor AI Assistant


