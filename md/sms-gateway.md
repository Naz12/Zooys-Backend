# 📱 SMS Gateway Module - API Documentation

## Overview

The **SMS Gateway Module** provides a unified interface to the **DYM SMS Gateways Engine** for sending and tracking SMS messages across multiple providers (Twilio, etc.) with HMAC-SHA256 authentication.

### Features
- ✅ Send OTP, transactional, marketing, alert, and service messages
- ✅ Provider abstraction (Twilio and more)
- ✅ HMAC-SHA256 authentication
- ✅ Idempotency protection (prevents duplicate sends)
- ✅ Message status tracking
- ✅ Async job polling
- ✅ Phone number validation and formatting
- ✅ Multi-app support (Zooys, Akili, Dagu)

---

## 🔐 Authentication

Uses **HMAC-SHA256 signature** with:
- **`X-Client-Id`** - Service identifier (zooys, akili, dagu)
- **`X-Key-Id`** - API key identifier
- **`X-Timestamp`** - Unix timestamp (±5 min tolerance)
- **`Idempotency-Key`** - UUID to prevent duplicate sends
- **`X-Signature`** - `sha256=` + HMAC hash of request body

---

## 📋 Message Types

| Type | Description | Use Cases |
|------|-------------|-----------|
| `otp` | One-time passwords | Login codes, verification |
| `transactional` | Transaction confirmations | Order confirmations, receipts |
| `marketing` | Promotional messages | Campaigns, offers |
| `alert` | Urgent notifications | Security alerts, system notifications |
| `service` | Service updates | Delivery status, appointment reminders |

---

## 🚀 Usage Examples

### **1. Send OTP Code**

```php
use App\Services\Modules\ModuleRegistry;

// Get SMS Gateway module
$smsGateway = ModuleRegistry::getModule('sms_gateway');

// Send OTP
$result = $smsGateway->sendOtp(
    '+251912345678',
    '123456',
    [
        'metadata' => [
            'user_id' => 123,
            'campaign_id' => 'user_registration'
        ]
    ]
);

// Response
[
    'status' => 'queued',
    'message_id' => 'sms_01JC8Z2YB6E6VQ8M1F3C1RC6R2',
    'provider_selected' => 'twilio',
    'segments_estimate' => 1
]
```

---

### **2. Send Transactional Message**

```php
$result = $smsGateway->sendTransactional(
    '+251912345678',
    'Your order #12345 has been confirmed and will be delivered by 5 PM.',
    [
        'metadata' => [
            'order_id' => 12345,
            'campaign_id' => 'order_confirmation'
        ]
    ]
);
```

---

### **3. Send Marketing Campaign**

```php
$result = $smsGateway->sendMarketing(
    '+251912345678',
    'Flash Sale! 50% off on all items. Use code FLASH50. Valid until midnight.',
    [
        'metadata' => [
            'campaign_id' => 'flash_sale_nov_2025'
        ],
        'delivery' => [
            'report_level' => 'full' // Get detailed delivery reports
        ]
    ]
);
```

---

### **4. Send Security Alert**

```php
$result = $smsGateway->sendAlert(
    '+251912345678',
    'Security alert: Your password was changed. If this wasn\'t you, contact support immediately.',
    [
        'metadata' => [
            'alert_type' => 'password_change'
        ]
    ]
);
```

---

### **5. Check Message Status**

```php
// Get status
$status = $smsGateway->getMessageStatus('sms_01JC8Z2YB6E6VQ8M1F3C1RC6R2');

// Response
[
    'id' => 'sms_01JC8Z2YB6E6VQ8M1F3C1RC6R2',
    'status' => 'delivered', // or queued, sent, failed, undelivered
    'provider' => 'twilio',
    'to' => '+251912345678',
    'segments' => 1
]
```

---

### **6. Check if Message was Delivered**

```php
$delivered = $smsGateway->isDelivered('sms_01JC8Z2YB6E6VQ8M1F3C1RC6R2');
// Returns: true or false
```

---

### **7. Poll Until Delivered (Async)**

```php
try {
    $finalStatus = $smsGateway->pollMessageStatus(
        'sms_01JC8Z2YB6E6VQ8M1F3C1RC6R2',
        30,  // Max attempts
        2    // Delay between attempts (seconds)
    );
    
    if ($finalStatus['status'] === 'delivered') {
        // SMS successfully delivered
    } else {
        // SMS failed or undelivered
    }
} catch (\Exception $e) {
    // Polling timed out
}
```

---

### **8. Advanced: Custom Universal Payload**

```php
$result = $smsGateway->sendSms(
    '+251912345678',
    'Your appointment is confirmed for tomorrow at 3 PM.',
    'service',
    [
        'routing' => [
            'profile' => 'local' // or 'priority', 'international'
        ],
        'delivery' => [
            'report_level' => 'full' // 'basic' or 'full'
        ],
        'metadata' => [
            'appointment_id' => 456,
            'campaign_id' => 'appointment_reminder'
        ]
    ]
);
```

---

### **9. Using Template (if supported by engine)**

```php
$result = $smsGateway->sendSms(
    '+251912345678',
    '', // Body not required when using template
    'otp',
    [
        'template' => [
            'id' => 'otp_template_v1',
            'variables' => [
                'code' => '123456',
                'expiry' => '5 minutes'
            ]
        ]
    ]
);
```

---

## 📞 Phone Number Utilities

### **Validate Phone Number**

```php
$valid = $smsGateway->validatePhone('+251912345678');
// Returns: true or false
```

### **Format Phone Number**

```php
// Auto-add country code
$formatted = $smsGateway->formatPhone('0912345678', '251');
// Returns: +251912345678

// Already formatted
$formatted = $smsGateway->formatPhone('+251912345678');
// Returns: +251912345678
```

---

## 🏥 Health Check

```php
$healthy = $smsGateway->checkHealth();
// Returns: true or false

// Get detailed service info
$info = $smsGateway->getServiceInfo();
[
    'service' => 'SMS Gateway',
    'base_url' => 'http://127.0.0.1:9000/api/internal/v1',
    'client_id' => 'zooys',
    'timeout' => 30,
    'supported_types' => ['otp', 'transactional', 'marketing', 'alert', 'service'],
    'healthy' => true
]
```

---

## 🔌 Integration in Controllers

### **Example: Send OTP in AuthController**

```php
<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\Modules\ModuleRegistry;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string'
        ]);

        // Get SMS Gateway module
        $smsGateway = ModuleRegistry::getModule('sms_gateway');

        // Format phone number
        $phone = $smsGateway->formatPhone($request->phone, '251');

        // Validate phone
        if (!$smsGateway->validatePhone($phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number format'
            ], 400);
        }

        // Generate OTP code
        $code = rand(100000, 999999);

        // Store code in cache/database
        \Cache::put("otp:{$phone}", $code, now()->addMinutes(5));

        // Send OTP via SMS
        try {
            $result = $smsGateway->sendOtp($phone, $code, [
                'metadata' => [
                    'user_id' => $request->user()->id ?? null,
                    'campaign_id' => 'auth_login'
                ]
            ]);

            // Store message_id for tracking
            \Cache::put("otp:message_id:{$phone}", $result['message_id'], now()->addMinutes(10));

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully',
                'message_id' => $result['message_id'],
                'expires_at' => now()->addMinutes(5)->toIso8601String()
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send OTP', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.'
            ], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string'
        ]);

        $phone = ModuleRegistry::getModule('sms_gateway')->formatPhone($request->phone, '251');
        $storedCode = \Cache::get("otp:{$phone}");

        if (!$storedCode || $storedCode != $request->code) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        // Check delivery status (optional)
        $messageId = \Cache::get("otp:message_id:{$phone}");
        if ($messageId) {
            $smsGateway = ModuleRegistry::getModule('sms_gateway');
            $delivered = $smsGateway->isDelivered($messageId);
            
            \Log::info('OTP delivery status', [
                'phone' => $phone,
                'delivered' => $delivered
            ]);
        }

        // Clear OTP
        \Cache::forget("otp:{$phone}");

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully'
        ]);
    }
}
```

---

## 📊 Status Flow

```
Send SMS
    ↓
queued → sent → delivered ✅
              ↘ failed ❌
              ↘ undelivered ❌
```

### **Status Meanings:**
- **`queued`** - SMS accepted, waiting to be sent
- **`sent`** - SMS sent to provider
- **`delivered`** - SMS successfully delivered to recipient
- **`failed`** - SMS failed to send
- **`undelivered`** - SMS sent but not delivered (invalid number, network issue)

---

## 🔧 Configuration

Located in `config/services.php`:

```php
'sms_gateway' => [
    'url' => env('SMS_GATEWAY_URL', 'http://127.0.0.1:9000/api/internal/v1'),
    'client_id' => env('SMS_GATEWAY_CLIENT_ID', 'zooys'),
    'key_id' => env('SMS_GATEWAY_KEY_ID', 'k_demo_zooys'),
    'secret' => env('SMS_GATEWAY_SECRET', 's_XXXXXXXXXXXXXXXXXXXXXXXXXXXX'),
    'timeout' => env('SMS_GATEWAY_TIMEOUT', 30),
],
```

---

## 🌍 Multi-App Usage

The SMS Gateway is designed for **internal multi-app usage**:

### **Zooys:**
- User authentication (OTP)
- Order confirmations
- Delivery notifications
- Password resets

### **Akili:**
- Student verification
- Exam reminders
- Result notifications

### **Dagu:**
- Booking confirmations
- Service alerts
- Customer notifications

Each app uses its own `client_id`, `key_id`, and `secret` for isolation and tracking.

---

## 📈 Cost Tracking

SMS are billed by **segments** (160 characters per segment):

```php
$result = $smsGateway->sendSms($phone, $message, 'transactional');

// Check estimated segments before sending
$segments = $result['segments_estimate'];
// 1 segment = 1 credit
// 2 segments = 2 credits
```

**Long Message Example:**
- 160 chars = 1 segment
- 161 chars = 2 segments
- 321 chars = 3 segments

---

## ⚠️ Error Handling

```php
try {
    $result = $smsGateway->sendOtp($phone, $code);
} catch (\Exception $e) {
    // Handle errors
    if (str_contains($e->getMessage(), 'Invalid phone')) {
        // Invalid phone number
    } elseif (str_contains($e->getMessage(), 'signature')) {
        // Authentication failed
    } else {
        // General failure
    }
    
    \Log::error('SMS send failed', [
        'phone' => $phone,
        'error' => $e->getMessage()
    ]);
}
```

---

## 🔒 Security Best Practices

1. **Never expose credentials**
   - Keep `SMS_GATEWAY_SECRET` in `.env`
   - Never log sensitive data

2. **Rate limiting**
   - Implement rate limits for OTP endpoints
   - Prevent SMS spam/abuse

3. **Phone validation**
   - Always validate phone format
   - Use `formatPhone()` for consistency

4. **Idempotency**
   - Engine handles duplicate prevention automatically
   - Same request within 5 minutes = single SMS

5. **Logging**
   - Log all SMS sends for audit trail
   - Track success/failure rates

---

## 📚 API Methods Reference

### **Sending Methods:**
- `sendSms($to, $body, $type, $options)` - Universal send method
- `sendOtp($to, $code, $options)` - Send OTP code
- `sendTransactional($to, $message, $options)` - Transactional message
- `sendMarketing($to, $message, $options)` - Marketing message
- `sendAlert($to, $message, $options)` - Alert message
- `sendService($to, $message, $options)` - Service notification

### **Status Methods:**
- `getMessageStatus($messageId)` - Get current status
- `isDelivered($messageId)` - Check if delivered (boolean)
- `pollMessageStatus($messageId, $maxAttempts, $delay)` - Poll until complete

### **Utility Methods:**
- `validatePhone($phone)` - Validate phone format
- `formatPhone($phone, $countryCode)` - Format to international
- `getSupportedTypes()` - Get message types
- `checkHealth()` - Health check
- `getServiceInfo()` - Service information

---

## ✅ Testing

See `POSTMAN_SETUP_GUIDE.md` for Postman collection (coming soon).

### **Quick Test:**

```php
php artisan tinker
```

```php
use App\Services\Modules\ModuleRegistry;

$sms = ModuleRegistry::getModule('sms_gateway');

// Test phone validation
$sms->validatePhone('+251912345678');

// Test phone formatting
$sms->formatPhone('0912345678', '251');

// Test health check
$sms->checkHealth();

// Test send (if microservice is running)
$sms->sendOtp('+251912345678', '123456');
```

---

## 🎯 Summary

The SMS Gateway Module provides:
- ✅ **Unified interface** to DYM SMS Engine
- ✅ **Type-safe methods** for all message types
- ✅ **Automatic authentication** (HMAC-SHA256)
- ✅ **Idempotency protection** (no duplicates)
- ✅ **Status tracking** with polling support
- ✅ **Phone utilities** (validation, formatting)
- ✅ **Multi-app support** (Zooys, Akili, Dagu)
- ✅ **Provider abstraction** (easy to add new providers)

**Integration is simple**: Get the module from ModuleRegistry and start sending SMS! 🚀



















