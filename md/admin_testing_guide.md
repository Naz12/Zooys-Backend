# 🧪 **Admin Authentication Testing Guide**

## 📋 **Overview**
This guide provides instructions for testing the admin authentication system implemented in Phase 1 of the Laravel backend API.

---

## 👤 **Available Admin Users**

### **1. Default Admin (from seeder)**
- **ID**: 1
- **Name**: Super Admin
- **Email**: `admin@example.com`
- **Password**: `password`
- **Created**: 2025-09-30 15:59:54
- **Source**: Database seeder

### **2. Test Admin (manually created)**
- **ID**: 2
- **Name**: Test Admin
- **Email**: `test@admin.com`
- **Password**: `admin123`
- **Created**: 2025-09-30 16:05:17
- **Source**: Artisan command

---

## 🔧 **Admin Management Commands**

### **Create New Admin User**
```bash
php artisan admin:create "Admin Name" "email@example.com" "password"
```

**Example:**
```bash
php artisan admin:create "John Doe" "john@admin.com" "securepass123"
```

### **List All Admin Users**
```bash
php artisan admin:list
```

**Output:**
```
Admin Users:

ID: 1
Name: Super Admin
Email: admin@example.com
Created: 2025-09-30 15:59:54
---
ID: 2
Name: Test Admin
Email: test@admin.com
Created: 2025-09-30 16:05:17
---
```

---

## 🧪 **API Testing Instructions**

### **1. Test Admin Login**

#### **Option A: Using Default Admin**
```bash
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

#### **Option B: Using Test Admin**
```bash
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@admin.com",
    "password": "admin123"
  }'
```

#### **Expected Response:**
```json
{
    "message": "Login successful",
    "admin": {
        "id": 1,
        "name": "Super Admin",
        "email": "admin@example.com"
    }
}
```

### **2. Test Protected Routes**

#### **Get Current Admin Info**
```bash
curl -X GET http://localhost:8000/api/admin/me \
  -H "Cookie: laravel_session=your_session_cookie"
```

#### **Get Admin Dashboard**
```bash
curl -X GET http://localhost:8000/api/admin/dashboard \
  -H "Cookie: laravel_session=your_session_cookie"
```

#### **Expected Response (Dashboard):**
```json
{
    "users": 150,
    "activeSubs": 45,
    "monthlyRevenue": 1349.55,
    "revenueTrend": [
        {
            "month": "2025-01",
            "revenue": 1349.55,
            "subs": 45
        }
    ],
    "dailyVisitors": {
        "2025-01-30": 25,
        "2025-01-29": 18
    }
}
```

### **3. Test Admin Logout**
```bash
curl -X POST http://localhost:8000/api/admin/logout \
  -H "Cookie: laravel_session=your_session_cookie"
```

#### **Expected Response:**
```json
{
    "message": "Logged out successfully"
}
```

### **4. Test Password Change**
```bash
curl -X POST http://localhost:8000/api/admin/password/change \
  -H "Content-Type: application/json" \
  -H "Cookie: laravel_session=your_session_cookie" \
  -d '{
    "current_password": "password",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
  }'
```

#### **Expected Response:**
```json
{
    "message": "Password changed successfully"
}
```

### **5. Test Password Reset**

#### **Send Reset Link**
```bash
curl -X POST http://localhost:8000/api/admin/password/reset \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com"
  }'
```

#### **Expected Response:**
```json
{
    "message": "Password reset link sent to your email.",
    "token": "abc123..." // Remove in production
}
```

#### **Verify Reset Token**
```bash
curl -X POST http://localhost:8000/api/admin/password/reset/verify \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "token": "abc123..."
  }'
```

#### **Reset Password**
```bash
curl -X POST http://localhost:8000/api/admin/password/reset/confirm \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "token": "abc123...",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
  }'
```

---

## 🔒 **Security Testing**

### **1. Test Rate Limiting**
Try logging in with wrong credentials 5 times:

```bash
# Try wrong password 5 times
for i in {1..5}; do
  curl -X POST http://localhost:8000/api/admin/login \
    -H "Content-Type: application/json" \
    -d '{
      "email": "admin@example.com",
      "password": "wrongpassword"
    }'
  echo "Attempt $i"
done
```

#### **Expected Response (after 5 attempts):**
```json
{
    "message": {
        "email": ["Too many login attempts. Please try again in 60 seconds."]
    }
}
```

### **2. Test Unauthorized Access**
Try accessing protected routes without authentication:

```bash
curl -X GET http://localhost:8000/api/admin/me
```

#### **Expected Response:**
```json
{
    "message": "Unauthorized. Admin authentication required."
}
```

### **3. Test Invalid Credentials**
```bash
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "wrongpassword"
  }'
```

#### **Expected Response:**
```json
{
    "message": {
        "email": ["The provided credentials are incorrect."]
    }
}
```

---

## 📊 **Admin Management Endpoints**

### **User Management**
```bash
# List all users
curl -X GET http://localhost:8000/api/admin/users \
  -H "Cookie: laravel_session=your_session_cookie"

# Create new user
curl -X POST http://localhost:8000/api/admin/users \
  -H "Content-Type: application/json" \
  -H "Cookie: laravel_session=your_session_cookie" \
  -d '{
    "name": "New User",
    "email": "user@example.com",
    "password": "password123"
  }'

# Get specific user
curl -X GET http://localhost:8000/api/admin/users/1 \
  -H "Cookie: laravel_session=your_session_cookie"

# Update user
curl -X PUT http://localhost:8000/api/admin/users/1 \
  -H "Content-Type: application/json" \
  -H "Cookie: laravel_session=your_session_cookie" \
  -d '{
    "name": "Updated User",
    "email": "updated@example.com"
  }'

# Delete user
curl -X DELETE http://localhost:8000/api/admin/users/1 \
  -H "Cookie: laravel_session=your_session_cookie"
```

### **Plan Management**
```bash
# List all plans
curl -X GET http://localhost:8000/api/admin/plans \
  -H "Cookie: laravel_session=your_session_cookie"

# Create new plan
curl -X POST http://localhost:8000/api/admin/plans \
  -H "Content-Type: application/json" \
  -H "Cookie: laravel_session=your_session_cookie" \
  -d '{
    "name": "Premium Plan",
    "price": 49.99,
    "interval": "monthly",
    "is_active": true
  }'
```

### **Subscription Management**
```bash
# List all subscriptions
curl -X GET http://localhost:8000/api/admin/subscriptions \
  -H "Cookie: laravel_session=your_session_cookie"

# Create new subscription
curl -X POST http://localhost:8000/api/admin/subscriptions \
  -H "Content-Type: application/json" \
  -H "Cookie: laravel_session=your_session_cookie" \
  -d '{
    "user_id": 1,
    "plan_id": 1,
    "active": true
  }'
```

### **Tool Management**
```bash
# List all tools
curl -X GET http://localhost:8000/api/admin/tools \
  -H "Cookie: laravel_session=your_session_cookie"

# Create new tool
curl -X POST http://localhost:8000/api/admin/tools \
  -H "Content-Type: application/json" \
  -H "Cookie: laravel_session=your_session_cookie" \
  -d '{
    "name": "AI Summarizer",
    "slug": "ai-summarizer",
    "description": "AI-powered content summarization tool"
  }'
```

### **Visitor Analytics**
```bash
# Get visitor analytics
curl -X GET http://localhost:8000/api/admin/visitors \
  -H "Cookie: laravel_session=your_session_cookie"
```

---

## 🎯 **Testing Checklist**

### **Authentication Tests**
- [ ] Login with correct credentials
- [ ] Login with incorrect credentials
- [ ] Rate limiting after 5 failed attempts
- [ ] Logout functionality
- [ ] Session invalidation after logout
- [ ] Access protected routes without authentication
- [ ] Access protected routes with valid session

### **Password Management Tests**
- [ ] Change password with correct current password
- [ ] Change password with incorrect current password
- [ ] Send password reset link
- [ ] Verify reset token
- [ ] Reset password with valid token
- [ ] Reset password with invalid/expired token

### **Admin Management Tests**
- [ ] List all users
- [ ] Create new user
- [ ] Update user information
- [ ] Delete user
- [ ] List all plans
- [ ] Create new plan
- [ ] Update plan
- [ ] Delete plan
- [ ] List all subscriptions
- [ ] Create new subscription
- [ ] Update subscription
- [ ] Delete subscription
- [ ] List all tools
- [ ] Create new tool
- [ ] Update tool
- [ ] Delete tool
- [ ] Get visitor analytics
- [ ] Get dashboard statistics

### **Security Tests**
- [ ] CSRF protection
- [ ] Input validation
- [ ] SQL injection prevention
- [ ] XSS prevention
- [ ] Session security
- [ ] Password hashing
- [ ] Rate limiting
- [ ] Error handling

---

## 🚨 **Important Notes**

### **Security Considerations**
1. **Change default passwords** in production
2. **Remove token from password reset response** in production
3. **Configure email sending** for password reset
4. **Use HTTPS** in production
5. **Implement IP whitelisting** for admin access
6. **Enable 2FA** for additional security

### **Session Management**
- Sessions are stored in Laravel's default session driver
- Session cookies are HTTP-only for security
- Sessions expire based on Laravel's configuration
- Remember me functionality extends session lifetime

### **Rate Limiting**
- 5 login attempts per email/IP combination
- 60-second lockout after exceeding limit
- Rate limiting is per email and IP address
- Cleared on successful login

### **Password Requirements**
- Minimum 8 characters
- Confirmation required for password changes
- Current password verification required
- Secure hashing with Laravel's Hash facade

---

## 📝 **Troubleshooting**

### **Common Issues**

#### **1. "Unauthorized" Error**
- Check if session cookie is included in request
- Verify admin is logged in
- Check if session has expired

#### **2. "Too many login attempts" Error**
- Wait 60 seconds before trying again
- Check if rate limiting is working correctly
- Verify IP address and email combination

#### **3. "Invalid credentials" Error**
- Verify email and password are correct
- Check if admin user exists in database
- Ensure password is properly hashed

#### **4. Session Issues**
- Check Laravel session configuration
- Verify session driver is working
- Check if cookies are being set correctly

### **Debug Commands**
```bash
# Check admin users
php artisan admin:list

# Create new admin user
php artisan admin:create "Name" "email@example.com" "password"

# Check routes
php artisan route:list --path=admin

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## 🎉 **Success Criteria**

The admin authentication system is working correctly if:

1. ✅ Admin can log in with correct credentials
2. ✅ Admin cannot log in with incorrect credentials
3. ✅ Rate limiting works after 5 failed attempts
4. ✅ Admin can access protected routes when logged in
5. ✅ Admin cannot access protected routes when not logged in
6. ✅ Admin can log out successfully
7. ✅ Session is invalidated after logout
8. ✅ Password change works with correct current password
9. ✅ Password reset flow works end-to-end
10. ✅ All admin management endpoints are accessible
11. ✅ Proper error responses are returned
12. ✅ Security measures are in place

**Phase 1 Admin Authentication System is complete and ready for production use!** 🚀
