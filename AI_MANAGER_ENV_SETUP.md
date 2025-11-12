# 🤖 AI Manager - Environment Variables Setup

## ⚡ Quick Setup

Add these lines to your `.env` file:

```env
# ===========================================
# AI Manager Configuration
# ===========================================

# Microservice URL
AI_MANAGER_URL=https://aimanager.akmicroservice.com

# API Key for authentication
AI_MANAGER_API_KEY=8eebab3587a5719950dfb3ee348737c6e244c13a5d6b3d35161071ee6a9d8c43

# Request timeout (seconds)
AI_MANAGER_TIMEOUT=180

# Default model for AI processing
AI_MANAGER_DEFAULT_MODEL=ollama:llama3
```

---

## 📝 Instructions

### **Step 1: Open your `.env` file**
```bash
notepad .env
# or
code .env
```

### **Step 2: Add the variables**
Scroll to the bottom and paste the entire block above.

### **Step 3: Save the file**

### **Step 4: Clear config cache**
```bash
php artisan config:clear
```

### **Step 5: Test the connection**
```bash
php artisan tinker
```

Then run:
```php
$ai = app(\App\Services\AIManagerService::class);
$result = $ai->getAvailableModels();
print_r($result);
```

---

## 🔧 Configuration Options Explained

| Variable                    | Description                              | Default                              |
|-----------------------------|------------------------------------------|--------------------------------------|
| `AI_MANAGER_URL`            | Microservice base URL                    | `https://aimanager.akmicroservice.com` |
| `AI_MANAGER_API_KEY`        | Authentication API key                   | (New long key)                       |
| `AI_MANAGER_TIMEOUT`        | HTTP request timeout (seconds)           | `180`                                |
| `AI_MANAGER_DEFAULT_MODEL`  | Default AI model to use                  | `ollama:llama3`                      |

---

## ✅ Verification Checklist

- [ ] Added all 4 environment variables to `.env`
- [ ] Saved `.env` file
- [ ] Ran `php artisan config:clear`
- [ ] Tested connection with available models check
- [ ] Models list returned successfully

---

## 🚀 You're Ready!

Once the variables are added, the AI Manager module will be fully functional with all new features! 🎉










