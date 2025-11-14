# 📱 SMS Gateway Environment Setup

## 🔧 Required Environment Variables

Add these to your `.env` file:

```env
# SMS Gateway Configuration
SMS_GATEWAY_URL=http://127.0.0.1:9000/api/internal/v1
SMS_GATEWAY_CLIENT_ID=zooys
SMS_GATEWAY_KEY_ID=k_demo_zooys
SMS_GATEWAY_SECRET=s_XXXXXXXXXXXXXXXXXXXXXXXXXXXX
SMS_GATEWAY_TIMEOUT=30
```

---

## 📝 Configuration Details

### **SMS_GATEWAY_URL**
- **Production**: `https://<your-host>/api/internal/v1`
- **Local Development**: `http://127.0.0.1:9000/api/internal/v1`
- Base URL for the DYM SMS Gateways Engine

### **SMS_GATEWAY_CLIENT_ID**
- Identifies which app is calling: `zooys`, `akili`, or `dagu`
- Used in HMAC signature and routing

### **SMS_GATEWAY_KEY_ID**
- API key identifier issued by the engine
- Format: `k_demo_<client_id>` or production key

### **SMS_GATEWAY_SECRET**
- Secret key for HMAC-SHA256 signature generation
- **KEEP THIS SECURE** - Never commit to version control
- Used to sign all requests

### **SMS_GATEWAY_TIMEOUT**
- HTTP timeout in seconds (default: 30)
- Increase if sending bulk SMS or experiencing network delays

---

## ⚠️ Security Notes

1. **Never commit** `.env` file to version control
2. **Rotate secrets** regularly in production
3. **Use different keys** for each app (zooys, akili, dagu)
4. **Restrict network access** to the microservice (internal only)

---

## 🚀 Quick Setup

1. **Copy the variables above** into your `.env` file
2. **Replace placeholder secret** (`s_XXXXXXXXXXXXXXXXXXXXXXXXXXXX`) with actual secret from SMS engine admin
3. **Verify URL** matches your SMS engine deployment
4. **Restart Laravel** if already running:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

---

## ✅ Verification

After adding variables, test the configuration:

```bash
php artisan tinker
```

```php
config('services.sms_gateway.url')
// Should output: http://127.0.0.1:9000/api/internal/v1

config('services.sms_gateway.client_id')
// Should output: zooys
```

---

## 🔄 Multi-App Configuration

If you need different configs for different apps on same server:

```env
# Zooys
SMS_GATEWAY_CLIENT_ID=zooys
SMS_GATEWAY_KEY_ID=k_demo_zooys
SMS_GATEWAY_SECRET=s_zooys_secret_here

# Or use app-specific prefix if running multiple apps:
# AKILI_SMS_GATEWAY_CLIENT_ID=akili
# AKILI_SMS_GATEWAY_KEY_ID=k_demo_akili
# AKILI_SMS_GATEWAY_SECRET=s_akili_secret_here
```

---

## 📞 Need Credentials?

Contact your **SMS Engine administrator** to get:
- Production URL
- Client ID for your app
- Key ID
- Secret key

For local development, use the demo values provided above.














