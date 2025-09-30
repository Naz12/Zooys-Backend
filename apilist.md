# 📋 **Complete Admin API Documentation**

## 🔐 **Authentication APIs**

### **1. Admin Login**
- **Endpoint**: `POST /api/admin/login`
- **Headers**: `Content-Type: application/json`
- **Body**:
```json
{
  "email": "admin@example.com",
  "password": "password"
}
```
- **Response**:
```json
{
  "message": "Login successful",
  "admin": {
    "id": 1,
    "name": "Super Admin",
    "email": "admin@example.com"
  },
  "token": "1|hhc7Ilj8rd9oKONTGmy5Pu74cAPPvXVRzT5ucwTqd908c9e1"
}
```

### **2. Admin Logout**
- **Endpoint**: `POST /api/admin/auth/logout`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Logged out successfully"
}
```

### **3. Get Current Admin**
- **Endpoint**: `GET /api/admin/auth/me`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "admin": {
    "id": 1,
    "name": "Super Admin",
    "email": "admin@example.com",
    "created_at": "2025-09-30T15:59:54.000000Z"
  }
}
```

### **4. Change Password**
- **Endpoint**: `POST /api/admin/auth/password/change`
- **Headers**: `Authorization: Bearer {token}`, `Content-Type: application/json`
- **Body**:
```json
{
  "current_password": "oldpassword",
  "new_password": "newpassword",
  "new_password_confirmation": "newpassword"
}
```
- **Response**:
```json
{
  "message": "Password changed successfully"
}
```

---

## 📊 **Dashboard APIs**

### **5. Main Dashboard**
- **Endpoint**: `GET /api/admin/dashboard`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "users": 15,
  "activeSubs": 7,
  "monthlyRevenue": 179.93,
  "revenueTrend": [
    {
      "month": "2025-09",
      "revenue": "179.93",
      "subs": 7
    }
  ],
  "dailyVisitors": {
    "2025-10-01": 32,
    "2025-09-30": 34,
    "2025-09-29": 38
  }
}
```

### **6. Dashboard Statistics**
- **Endpoint**: `GET /api/admin/dashboard/stats`
- **Headers**: `Authorization: Bearer {token}`
- **Response**: Detailed statistics object

### **7. Dashboard Analytics**
- **Endpoint**: `GET /api/admin/dashboard/analytics`
- **Headers**: `Authorization: Bearer {token}`
- **Response**: Analytics data object

### **8. Revenue Analytics**
- **Endpoint**: `GET /api/admin/dashboard/revenue`
- **Headers**: `Authorization: Bearer {token}`
- **Response**: Revenue analytics object

### **9. User Analytics**
- **Endpoint**: `GET /api/admin/dashboard/users`
- **Headers**: `Authorization: Bearer {token}`
- **Response**: User analytics object

### **10. Subscription Analytics**
- **Endpoint**: `GET /api/admin/dashboard/subscriptions`
- **Headers**: `Authorization: Bearer {token}`
- **Response**: Subscription analytics object

---

## 👥 **User Management APIs**

### **11. List Users**
- **Endpoint**: `GET /api/admin/users`
- **Headers**: `Authorization: Bearer {token}`
- **Query Parameters**: `page`, `per_page`, `search`, `sort`
- **Response**:
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "email_verified_at": "2025-08-31T18:32:41.000000Z",
      "created_at": "2025-08-31T18:32:41.000000Z",
      "updated_at": "2025-08-31T18:32:41.000000Z"
    }
  ],
  "first_page_url": "http://localhost:8000/api/admin/users?page=1",
  "from": 1,
  "last_page": 1,
  "last_page_url": "http://localhost:8000/api/admin/users?page=1",
  "links": [...],
  "next_page_url": null,
  "path": "http://localhost:8000/api/admin/users",
  "per_page": 15,
  "prev_page_url": null,
  "to": 15,
  "total": 15
}
```

### **12. Create User**
- **Endpoint**: `POST /api/admin/users`
- **Headers**: `Authorization: Bearer {token}`, `Content-Type: application/json`
- **Body**:
```json
{
  "name": "New User",
  "email": "newuser@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```
- **Response**:
```json
{
  "id": 16,
  "name": "New User",
  "email": "newuser@example.com",
  "email_verified_at": null,
  "created_at": "2025-09-30T18:33:00.000000Z",
  "updated_at": "2025-09-30T18:33:00.000000Z"
}
```

### **13. Get User Details**
- **Endpoint**: `GET /api/admin/users/{id}`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john.doe@example.com",
  "email_verified_at": "2025-08-31T18:32:41.000000Z",
  "created_at": "2025-08-31T18:32:41.000000Z",
  "updated_at": "2025-08-31T18:32:41.000000Z",
  "subscriptions": [...],
  "usage_stats": {...}
}
```

### **14. Update User**
- **Endpoint**: `PUT /api/admin/users/{id}`
- **Headers**: `Authorization: Bearer {token}`, `Content-Type: application/json`
- **Body**:
```json
{
  "name": "Updated Name",
  "email": "updated@example.com"
}
```
- **Response**:
```json
{
  "id": 1,
  "name": "Updated Name",
  "email": "updated@example.com",
  "email_verified_at": "2025-08-31T18:32:41.000000Z",
  "created_at": "2025-08-31T18:32:41.000000Z",
  "updated_at": "2025-09-30T18:33:00.000000Z"
}
```

### **15. Delete User**
- **Endpoint**: `DELETE /api/admin/users/{id}`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "User deleted successfully"
}
```

### **16. Activate User**
- **Endpoint**: `POST /api/admin/users/{id}/activate`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "User activated successfully"
}
```

### **17. Deactivate User**
- **Endpoint**: `POST /api/admin/users/{id}/deactivate`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "User deactivated successfully"
}
```

### **18. Suspend User**
- **Endpoint**: `POST /api/admin/users/{id}/suspend`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "User suspended successfully"
}
```

### **19. Get User Subscriptions**
- **Endpoint**: `GET /api/admin/users/{id}/subscriptions`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "subscriptions": [
    {
      "id": 1,
      "plan": {
        "name": "Pro",
        "price": "9.99"
      },
      "starts_at": "2025-09-10T00:00:00.000000Z",
      "ends_at": "2025-10-10T00:00:00.000000Z",
      "active": true
    }
  ]
}
```

### **20. Get User Usage**
- **Endpoint**: `GET /api/admin/users/{id}/usage`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "usage": {
    "total_requests": 45,
    "this_month": 12,
    "by_tool": {
      "youtube": 15,
      "pdf": 8,
      "writer": 22
    }
  }
}
```

### **21. Get User Activity**
- **Endpoint**: `GET /api/admin/users/{id}/activity`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "activities": [
    {
      "id": 1,
      "action": "tool_usage",
      "tool": "YouTube Summarizer",
      "created_at": "2025-09-30T18:33:00.000000Z"
    }
  ]
}
```

### **22. Bulk Activate Users**
- **Endpoint**: `POST /api/admin/users/bulk/activate`
- **Headers**: `Authorization: Bearer {token}`, `Content-Type: application/json`
- **Body**:
```json
{
  "user_ids": [1, 2, 3]
}
```
- **Response**:
```json
{
  "message": "3 users activated successfully"
}
```

### **23. Bulk Deactivate Users**
- **Endpoint**: `POST /api/admin/users/bulk/deactivate`
- **Headers**: `Authorization: Bearer {token}`, `Content-Type: application/json`
- **Body**:
```json
{
  "user_ids": [1, 2, 3]
}
```
- **Response**:
```json
{
  "message": "3 users deactivated successfully"
}
```

### **24. Bulk Delete Users**
- **Endpoint**: `POST /api/admin/users/bulk/delete`
- **Headers**: `Authorization: Bearer {token}`, `Content-Type: application/json`
- **Body**:
```json
{
  "user_ids": [1, 2, 3]
}
```
- **Response**:
```json
{
  "message": "3 users deleted successfully"
}
```

---

## 💳 **Plan Management APIs**

### **25. List Plans**
- **Endpoint**: `GET /api/admin/plans`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "Free",
      "price": "0.00",
      "currency": "USD",
      "limit": 20,
      "is_active": 1,
      "created_at": "2025-08-31T18:32:41.000000Z",
      "updated_at": "2025-08-31T18:32:41.000000Z"
    }
  ]
}
```

### **26. Create Plan**
- **Endpoint**: `POST /api/admin/plans`
- **Headers**: `Authorization: Bearer {token}`, `Content-Type: application/json`
- **Body**:
```json
{
  "name": "New Plan",
  "price": 15.99,
  "currency": "USD",
  "limit": 1000,
  "is_active": true
}
```
- **Response**:
```json
{
  "id": 8,
  "name": "New Plan",
  "price": "15.99",
  "currency": "USD",
  "limit": 1000,
  "is_active": 1,
  "created_at": "2025-09-30T18:33:00.000000Z",
  "updated_at": "2025-09-30T18:33:00.000000Z"
}
```

### **27. Get Plan Details**
- **Endpoint**: `GET /api/admin/plans/{id}`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "id": 1,
  "name": "Free",
  "price": "0.00",
  "currency": "USD",
  "limit": 20,
  "is_active": 1,
  "created_at": "2025-08-31T18:32:41.000000Z",
  "updated_at": "2025-08-31T18:32:41.000000Z",
  "subscriptions": [...],
  "analytics": {...}
}
```

### **28. Update Plan**
- **Endpoint**: `PUT /api/admin/plans/{id}`
- **Headers**: `Authorization: Bearer {token}`, `Content-Type: application/json`
- **Body**:
```json
{
  "name": "Updated Plan",
  "price": 19.99,
  "limit": 1500
}
```
- **Response**:
```json
{
  "id": 1,
  "name": "Updated Plan",
  "price": "19.99",
  "currency": "USD",
  "limit": 1500,
  "is_active": 1,
  "created_at": "2025-08-31T18:32:41.000000Z",
  "updated_at": "2025-09-30T18:33:00.000000Z"
}
```

### **29. Delete Plan**
- **Endpoint**: `DELETE /api/admin/plans/{id}`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Plan deleted successfully"
}
```

### **30. Activate Plan**
- **Endpoint**: `POST /api/admin/plans/{id}/activate`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Plan activated successfully"
}
```

### **31. Deactivate Plan**
- **Endpoint**: `POST /api/admin/plans/{id}/deactivate`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Plan deactivated successfully"
}
```

### **32. Get Plan Subscriptions**
- **Endpoint**: `GET /api/admin/plans/{id}/subscriptions`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "subscriptions": [
    {
      "id": 1,
      "user": {
        "name": "John Doe",
        "email": "john.doe@example.com"
      },
      "starts_at": "2025-09-10T00:00:00.000000Z",
      "ends_at": "2025-10-10T00:00:00.000000Z",
      "active": true
    }
  ]
}
```

### **33. Get Plan Analytics**
- **Endpoint**: `GET /api/admin/plans/{id}/analytics`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "analytics": {
    "total_subscribers": 5,
    "revenue": 49.95,
    "conversion_rate": 0.15,
    "churn_rate": 0.05
  }
}
```

---

## 📋 **Subscription Management APIs**

### **34. List Subscriptions**
- **Endpoint**: `GET /api/admin/subscriptions`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "user": {
        "name": "John Doe",
        "email": "john.doe@example.com"
      },
      "plan": {
        "name": "Pro",
        "price": "9.99"
      },
      "starts_at": "2025-09-10T00:00:00.000000Z",
      "ends_at": "2025-10-10T00:00:00.000000Z",
      "active": true,
      "created_at": "2025-09-10T00:00:00.000000Z"
    }
  ]
}
```

### **35. Create Subscription**
- **Endpoint**: `POST /api/admin/subscriptions`
- **Headers**: `Authorization: Bearer {token}`, `Content-Type: application/json`
- **Body**:
```json
{
  "user_id": 1,
  "plan_id": 2,
  "starts_at": "2025-09-30T00:00:00.000000Z",
  "ends_at": "2025-10-30T00:00:00.000000Z"
}
```
- **Response**:
```json
{
  "id": 11,
  "user_id": 1,
  "plan_id": 2,
  "starts_at": "2025-09-30T00:00:00.000000Z",
  "ends_at": "2025-10-30T00:00:00.000000Z",
  "active": true,
  "created_at": "2025-09-30T18:33:00.000000Z"
}
```

### **36. Get Subscription Details**
- **Endpoint**: `GET /api/admin/subscriptions/{id}`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "id": 1,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john.doe@example.com"
  },
  "plan": {
    "id": 3,
    "name": "Pro",
    "price": "9.99"
  },
  "starts_at": "2025-09-10T00:00:00.000000Z",
  "ends_at": "2025-10-10T00:00:00.000000Z",
  "active": true,
  "created_at": "2025-09-10T00:00:00.000000Z"
}
```

### **37. Update Subscription**
- **Endpoint**: `PUT /api/admin/subscriptions/{id}`
- **Headers**: `Authorization: Bearer {token}`, `Content-Type: application/json`
- **Body**:
```json
{
  "plan_id": 4,
  "ends_at": "2025-11-10T00:00:00.000000Z"
}
```
- **Response**:
```json
{
  "id": 1,
  "user_id": 1,
  "plan_id": 4,
  "starts_at": "2025-09-10T00:00:00.000000Z",
  "ends_at": "2025-11-10T00:00:00.000000Z",
  "active": true,
  "updated_at": "2025-09-30T18:33:00.000000Z"
}
```

### **38. Delete Subscription**
- **Endpoint**: `DELETE /api/admin/subscriptions/{id}`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Subscription deleted successfully"
}
```

### **39. Activate Subscription**
- **Endpoint**: `POST /api/admin/subscriptions/{id}/activate`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Subscription activated successfully"
}
```

### **40. Cancel Subscription**
- **Endpoint**: `POST /api/admin/subscriptions/{id}/cancel`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Subscription cancelled successfully"
}
```

### **41. Pause Subscription**
- **Endpoint**: `POST /api/admin/subscriptions/{id}/pause`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Subscription paused successfully"
}
```

### **42. Resume Subscription**
- **Endpoint**: `POST /api/admin/subscriptions/{id}/resume`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Subscription resumed successfully"
}
```

### **43. Upgrade Subscription**
- **Endpoint**: `POST /api/admin/subscriptions/{id}/upgrade`
- **Headers**: `Authorization: Bearer {token}`, `Content-Type: application/json`
- **Body**:
```json
{
  "new_plan_id": 4
}
```
- **Response**:
```json
{
  "message": "Subscription upgraded successfully"
}
```

### **44. Downgrade Subscription**
- **Endpoint**: `POST /api/admin/subscriptions/{id}/downgrade`
- **Headers**: `Authorization: Bearer {token}`, `Content-Type: application/json`
- **Body**:
```json
{
  "new_plan_id": 2
}
```
- **Response**:
```json
{
  "message": "Subscription downgraded successfully"
}
```

### **45. Subscription Analytics Overview**
- **Endpoint**: `GET /api/admin/subscriptions/analytics/overview`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "total_subscriptions": 10,
  "active_subscriptions": 7,
  "cancelled_subscriptions": 3,
  "monthly_recurring_revenue": 179.93,
  "average_revenue_per_user": 25.70
}
```

### **46. Subscription Revenue Analytics**
- **Endpoint**: `GET /api/admin/subscriptions/analytics/revenue`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "revenue": {
    "total": 179.93,
    "this_month": 179.93,
    "last_month": 0,
    "growth": 100
  }
}
```

### **47. Subscription Churn Analytics**
- **Endpoint**: `GET /api/admin/subscriptions/analytics/churn`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "churn_rate": 0.05,
  "churned_this_month": 1,
  "retention_rate": 0.95
}
```

### **48. Subscription Conversion Analytics**
- **Endpoint**: `GET /api/admin/subscriptions/analytics/conversion`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "conversion_rate": 0.15,
  "free_to_paid": 0.12,
  "trial_to_paid": 0.18
}
```

---

## 🛠️ **Tool Management APIs**

### **49. List Tools**
- **Endpoint**: `GET /api/admin/tools`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "YouTube Summarizer",
      "slug": "youtube",
      "enabled": 1,
      "created_at": "2025-08-31T18:32:33.000000Z",
      "updated_at": "2025-08-31T18:32:33.000000Z"
    }
  ]
}
```

### **50. Create Tool**
- **Endpoint**: `POST /api/admin/tools`
- **Headers**: `Authorization: Bearer {token}`, `Content-Type: application/json`
- **Body**:
```json
{
  "name": "New Tool",
  "slug": "new-tool",
  "enabled": true
}
```
- **Response**:
```json
{
  "id": 11,
  "name": "New Tool",
  "slug": "new-tool",
  "enabled": 1,
  "created_at": "2025-09-30T18:33:00.000000Z",
  "updated_at": "2025-09-30T18:33:00.000000Z"
}
```

### **51. Get Tool Details**
- **Endpoint**: `GET /api/admin/tools/{id}`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "id": 1,
  "name": "YouTube Summarizer",
  "slug": "youtube",
  "enabled": 1,
  "created_at": "2025-08-31T18:32:33.000000Z",
  "updated_at": "2025-08-31T18:32:33.000000Z",
  "usage_stats": {...},
  "analytics": {...}
}
```

### **52. Update Tool**
- **Endpoint**: `PUT /api/admin/tools/{id}`
- **Headers**: `Authorization: Bearer {token}`, `Content-Type: application/json`
- **Body**:
```json
{
  "name": "Updated Tool Name",
  "enabled": false
}
```
- **Response**:
```json
{
  "id": 1,
  "name": "Updated Tool Name",
  "slug": "youtube",
  "enabled": 0,
  "created_at": "2025-08-31T18:32:33.000000Z",
  "updated_at": "2025-09-30T18:33:00.000000Z"
}
```

### **53. Delete Tool**
- **Endpoint**: `DELETE /api/admin/tools/{id}`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Tool deleted successfully"
}
```

### **54. Activate Tool**
- **Endpoint**: `POST /api/admin/tools/{id}/activate`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Tool activated successfully"
}
```

### **55. Deactivate Tool**
- **Endpoint**: `POST /api/admin/tools/{id}/deactivate`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Tool deactivated successfully"
}
```

### **56. Get Tool Usage**
- **Endpoint**: `GET /api/admin/tools/{id}/usage`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "usage": {
    "total_requests": 45,
    "this_month": 12,
    "daily_usage": {
      "2025-09-30": 5,
      "2025-09-29": 3,
      "2025-09-28": 4
    }
  }
}
```

### **57. Get Tool Analytics**
- **Endpoint**: `GET /api/admin/tools/{id}/analytics`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "analytics": {
    "total_users": 8,
    "avg_usage_per_user": 5.6,
    "popularity_score": 0.75,
    "success_rate": 0.95
  }
}
```

### **58. Get Tool Users**
- **Endpoint**: `GET /api/admin/tools/{id}/users`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "users": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "usage_count": 15,
      "last_used": "2025-09-30T18:33:00.000000Z"
    }
  ]
}
```

---

## 📈 **Visitor Analytics APIs**

### **59. Visitor Data**
- **Endpoint**: `GET /api/admin/visitors`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "dailyVisitors": {
    "2025-10-01": 32,
    "2025-09-30": 34,
    "2025-09-29": 38,
    "2025-09-28": 21
  }
}
```

### **60. Visitor Analytics**
- **Endpoint**: `GET /api/admin/visitors/analytics`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "analytics": {
    "total_visitors": 916,
    "unique_visitors": 450,
    "bounce_rate": 0.35,
    "avg_session_duration": 180
  }
}
```

### **61. Geographic Analytics**
- **Endpoint**: `GET /api/admin/visitors/geographic`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "geographic": {
    "countries": {
      "United States": 45,
      "Canada": 23,
      "United Kingdom": 18
    }
  }
}
```

### **62. Demographic Analytics**
- **Endpoint**: `GET /api/admin/visitors/demographic`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "demographic": {
    "age_groups": {
      "18-24": 25,
      "25-34": 40,
      "35-44": 20
    }
  }
}
```

### **63. Behavior Analytics**
- **Endpoint**: `GET /api/admin/visitors/behavior`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "behavior": {
    "page_views": {
      "/": 150,
      "/pricing": 45,
      "/features": 32
    }
  }
}
```

### **64. Traffic Sources**
- **Endpoint**: `GET /api/admin/visitors/sources`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "sources": {
    "direct": 40,
    "google": 35,
    "facebook": 15,
    "twitter": 10
  }
}
```

### **65. Device Analytics**
- **Endpoint**: `GET /api/admin/visitors/devices`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "devices": {
    "desktop": 60,
    "mobile": 35,
    "tablet": 5
  }
}
```

### **66. Export Visitor Data**
- **Endpoint**: `GET /api/admin/visitors/export`
- **Headers**: `Authorization: Bearer {token}`
- **Response**: CSV file download

---

## 📊 **Reports & Export APIs**

### **67. Export User Data**
- **Endpoint**: `GET /api/admin/reports/users`
- **Headers**: `Authorization: Bearer {token}`
- **Response**: CSV file download

### **68. Export Subscription Data**
- **Endpoint**: `GET /api/admin/reports/subscriptions`
- **Headers**: `Authorization: Bearer {token}`
- **Response**: CSV file download

### **69. Export Revenue Data**
- **Endpoint**: `GET /api/admin/reports/revenue`
- **Headers**: `Authorization: Bearer {token}`
- **Response**: CSV file download

### **70. Export Analytics Data**
- **Endpoint**: `GET /api/admin/reports/analytics`
- **Headers**: `Authorization: Bearer {token}`
- **Response**: CSV file download

### **71. Export Usage Data**
- **Endpoint**: `GET /api/admin/reports/usage`
- **Headers**: `Authorization: Bearer {token}`
- **Response**: CSV file download

---

## ⚙️ **System Administration APIs**

### **72. System Health**
- **Endpoint**: `GET /api/admin/system/health`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "status": "healthy",
  "uptime": "2 days, 5 hours",
  "memory_usage": "45%",
  "disk_usage": "60%"
}
```

### **73. System Logs**
- **Endpoint**: `GET /api/admin/system/logs`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "logs": [
    {
      "level": "info",
      "message": "User logged in",
      "timestamp": "2025-09-30T18:33:00.000000Z"
    }
  ]
}
```

### **74. System Performance**
- **Endpoint**: `GET /api/admin/system/performance`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "performance": {
    "response_time": "120ms",
    "throughput": "150 req/min",
    "error_rate": "0.1%"
  }
}
```

### **75. Cache Status**
- **Endpoint**: `GET /api/admin/system/cache`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "cache": {
    "status": "active",
    "hit_rate": "85%",
    "size": "50MB"
  }
}
```

### **76. Clear Cache**
- **Endpoint**: `POST /api/admin/system/cache/clear`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Cache cleared successfully"
}
```

### **77. Database Status**
- **Endpoint**: `GET /api/admin/system/database`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "database": {
    "status": "connected",
    "size": "25MB",
    "connections": 5
  }
}
```

### **78. Maintenance Mode**
- **Endpoint**: `POST /api/admin/system/maintenance`
- **Headers**: `Authorization: Bearer {token}`, `Content-Type: application/json`
- **Body**:
```json
{
  "enabled": true,
  "message": "System maintenance in progress"
}
```
- **Response**:
```json
{
  "message": "Maintenance mode updated successfully"
}
```

---

## 🔑 **Authentication Notes:**

- **All protected endpoints** require `Authorization: Bearer {token}` header
- **Token** is obtained from the login endpoint
- **Token expires** after a certain period (configurable)
- **Logout** invalidates the token immediately

## 📝 **Common Response Formats:**

### **Success Response:**
```json
{
  "message": "Operation successful",
  "data": {...}
}
```

### **Error Response:**
```json
{
  "message": "Error description",
  "errors": {
    "field": ["Error message"]
  }
}
```

### **Validation Error:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

This comprehensive API documentation covers all 78 admin endpoints with their request/response formats, making it easy to integrate with your Next.js frontend! 🚀
