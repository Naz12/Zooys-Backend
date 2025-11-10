# 🔐 Document Intelligence - Environment Variables Setup

## ⚡ Quick Setup

Add these lines to your `.env` file:

```env
# ===========================================
# Document Intelligence Service Configuration
# ===========================================

# Microservice URL (Cloud-based)
DOC_INTELLIGENCE_URL=https://doc.akmicroservice.com

# Tenant ID (Multi-tenant isolation)
DOC_INTELLIGENCE_TENANT=dagu

# Client credentials for HMAC authentication
DOC_INTELLIGENCE_CLIENT_ID=dev
DOC_INTELLIGENCE_KEY_ID=local

# HMAC Secret Key (IMPORTANT: Get the real secret from the microservice provider)
DOC_INTELLIGENCE_SECRET=change_me

# Request timeout in seconds
DOC_INTELLIGENCE_TIMEOUT=120
```

---

## 📝 Instructions

### **Step 1: Open your `.env` file**
```bash
# In your project root
notepad .env
# or
code .env
```

### **Step 2: Scroll to the bottom**

### **Step 3: Add the variables**
Copy the entire block above (from `# Document Intelligence Service Configuration` to the end) and paste it at the bottom of your `.env` file.

### **Step 4: Update the secret**
Replace `change_me` with your actual HMAC secret key:
```env
DOC_INTELLIGENCE_SECRET=your_actual_secret_here
```

> **⚠️ IMPORTANT**: Contact the microservice provider to get your real credentials:
> - `DOC_INTELLIGENCE_CLIENT_ID`
> - `DOC_INTELLIGENCE_KEY_ID`
> - `DOC_INTELLIGENCE_SECRET`

### **Step 5: Save the file**

### **Step 6: Restart your services**
```bash
# Restart queue worker
php artisan queue:work --timeout=0

# Restart dev server if running
php artisan serve
```

---

## 🧪 Test Configuration

After adding the variables, test the connection:

```bash
curl -X GET http://localhost:8000/api/documents/health \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected Response:**
```json
{
  "success": true,
  "service": "document-intelligence",
  "health": {
    "ok": true,
    "uptime": 1234567,
    "vector_status": "healthy",
    "cache_status": "healthy"
  }
}
```

---

## 🔧 Configuration Options Explained

| Variable                      | Description                                    | Default                              |
|-------------------------------|------------------------------------------------|--------------------------------------|
| `DOC_INTELLIGENCE_URL`        | Microservice base URL                          | `https://doc.akmicroservice.com`     |
| `DOC_INTELLIGENCE_TENANT`     | Tenant ID for multi-tenant isolation           | `dagu`                               |
| `DOC_INTELLIGENCE_CLIENT_ID`  | Client identifier for authentication           | `dev`                                |
| `DOC_INTELLIGENCE_KEY_ID`     | Key identifier for HMAC signature              | `local`                              |
| `DOC_INTELLIGENCE_SECRET`     | Secret key for HMAC-SHA256 signing             | `change_me` ⚠️                       |
| `DOC_INTELLIGENCE_TIMEOUT`    | HTTP request timeout (seconds)                 | `120`                                |

---

## 🔐 Security Notes

1. **Never commit `.env` to Git** - It's already in `.gitignore`
2. **Keep the secret safe** - It's like a password
3. **Different secrets for prod/dev** - Use separate credentials
4. **Rotate secrets periodically** - Change them every few months

---

## ✅ Verification Checklist

- [ ] Added all 6 environment variables to `.env`
- [ ] Replaced `change_me` with real secret
- [ ] Saved `.env` file
- [ ] Restarted queue worker
- [ ] Tested health endpoint
- [ ] Health check returns `"ok": true`

---

## 🆘 Troubleshooting

### **"HMAC signature failed"**
- Wrong `DOC_INTELLIGENCE_SECRET`
- Check for extra spaces in the secret value

### **"Connection timeout"**
- Check `DOC_INTELLIGENCE_URL` is correct
- Increase `DOC_INTELLIGENCE_TIMEOUT`
- Check internet connectivity

### **"Tenant not found"**
- Verify `DOC_INTELLIGENCE_TENANT` with provider
- Make sure tenant is active

### **Health check fails**
- Microservice might be down
- Check credentials with provider
- Verify URL is reachable

---

## 📞 Getting Credentials

**Contact the microservice provider to get:**
1. Your tenant ID
2. Client ID and Key ID
3. Secret key for HMAC signing

**Or use the test credentials** (for development only):
```env
DOC_INTELLIGENCE_TENANT=dagu
DOC_INTELLIGENCE_CLIENT_ID=dev
DOC_INTELLIGENCE_KEY_ID=local
DOC_INTELLIGENCE_SECRET=change_me
```

---

## 🚀 You're Ready!

Once the variables are added, the Document Intelligence module will be fully functional! 🎉

**Next steps:**
1. Test with the health endpoint
2. Try document ingestion
3. Explore semantic search
4. Build amazing AI features! 🧠

---

**Questions?** Check `md/document-intelligence.md` for full documentation!







