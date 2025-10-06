# Updated User Management API Endpoints

## Authentication
All endpoints require admin authentication token in the Authorization header:
```
Authorization: Bearer {admin_token}
```

## User Management Endpoints

### 1. Get All Users
```http
GET /api/admin/users
```

**Response:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 4,
      "name": "Sarah Wilson",
      "email": "sarah.wilson@example.com",
      "email_verified_at": "2025-09-15T18:32:42.000000Z",
      "is_active": true,
      "status": "active",
      "suspended_at": null,
      "suspension_reason": null,
      "created_at": "2025-09-15T18:32:42.000000Z",
      "updated_at": "2025-09-30T19:55:42.000000Z"
    }
  ],
  "per_page": 15,
  "total": 12
}
```

### 2. Get Single User
```http
GET /api/admin/users/{user_id}
```

**Response:**
```json
{
  "id": 4,
  "name": "Sarah Wilson",
  "email": "sarah.wilson@example.com",
  "email_verified_at": "2025-09-15T18:32:42.000000Z",
  "is_active": true,
  "status": "active",
  "suspended_at": null,
  "suspension_reason": null,
  "created_at": "2025-09-15T18:32:42.000000Z",
  "updated_at": "2025-09-30T19:55:42.000000Z"
}
```

### 3. Create User
```http
POST /api/admin/users
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "message": "User created!",
  "user": {
    "id": 13,
    "name": "John Doe",
    "email": "john@example.com",
    "is_active": true,
    "status": "active",
    "created_at": "2025-09-30T20:00:00.000000Z",
    "updated_at": "2025-09-30T20:00:00.000000Z"
  }
}
```

### 4. Update User
```http
PUT /api/admin/users/{user_id}
Content-Type: application/json

{
  "name": "John Smith",
  "email": "johnsmith@example.com",
  "password": "newpassword123"
}
```

**Response:**
```json
{
  "message": "User updated!",
  "user": {
    "id": 4,
    "name": "John Smith",
    "email": "johnsmith@example.com",
    "is_active": true,
    "status": "active",
    "updated_at": "2025-09-30T20:05:00.000000Z"
  }
}
```

### 5. Delete User
```http
DELETE /api/admin/users/{user_id}
```

**Response:**
```json
{
  "message": "User deleted!"
}
```

## User Status Management

### 6. Activate User
```http
POST /api/admin/users/{user_id}/activate
```

**Response:**
```json
{
  "message": "User activated successfully",
  "user": {
    "id": 4,
    "name": "Sarah Wilson",
    "email": "sarah.wilson@example.com",
    "is_active": true,
    "status": "active",
    "updated_at": "2025-09-30T19:55:42.000000Z"
  }
}
```

### 7. Deactivate User
```http
POST /api/admin/users/{user_id}/deactivate
```

**Response:**
```json
{
  "message": "User deactivated successfully",
  "user": {
    "id": 4,
    "name": "Sarah Wilson",
    "email": "sarah.wilson@example.com",
    "is_active": false,
    "status": "inactive",
    "updated_at": "2025-09-30T19:55:49.000000Z"
  }
}
```

### 8. Suspend User
```http
POST /api/admin/users/{user_id}/suspend
```

**Response:**
```json
{
  "message": "User suspended successfully",
  "user": {
    "id": 4,
    "name": "Sarah Wilson",
    "email": "sarah.wilson@example.com",
    "is_active": false,
    "status": "suspended",
    "suspended_at": "2025-09-30T19:55:34.030723Z",
    "suspension_reason": "Suspended by admin",
    "updated_at": "2025-09-30T19:55:34.000000Z"
  }
}
```

## User Analytics & Information

### 9. Get User Subscriptions
```http
GET /api/admin/users/{user_id}/subscriptions
```

**Response:**
```json
{
  "subscription": {
    "id": 4,
    "user_id": 4,
    "plan_id": 2,
    "stripe_id": null,
    "stripe_customer_id": null,
    "active": true,
    "starts_at": "2025-09-25T18:32:44.000000Z",
    "ends_at": "2025-10-25T18:32:44.000000Z",
    "created_at": "2025-09-25T18:32:44.000000Z",
    "updated_at": "2025-09-25T18:32:44.000000Z",
    "plan": {
      "id": 2,
      "name": "Starter",
      "price": "9.99",
      "currency": "USD",
      "limit": 100,
      "is_active": true
    }
  }
}
```

### 10. Get User Usage Statistics
```http
GET /api/admin/users/{user_id}/usage
```

**Response:**
```json
{
  "usage": {
    "total_requests": 19,
    "this_month": 18,
    "by_tool": {
      "YouTube Summarizer": 5,
      "AI Writer": 3,
      "Diagram Generator": 1,
      "Code Generator": 4,
      "Image Generator": 1,
      "Text Translator": 4,
      "Voice Transcriber": 1
    }
  }
}
```

### 11. Get User Activity History
```http
GET /api/admin/users/{user_id}/activity
```

**Response:**
```json
{
  "activities": [
    {
      "id": 60,
      "action": "tool_usage",
      "tool": "Code Generator",
      "created_at": "2025-09-25T15:13:45.000000Z"
    },
    {
      "id": 53,
      "action": "tool_usage",
      "tool": "AI Writer",
      "created_at": "2025-09-25T12:57:44.000000Z"
    }
  ]
}
```

## Bulk Operations

### 12. Bulk Activate Users
```http
POST /api/admin/users/bulk/activate
Content-Type: application/json

{
  "user_ids": [4, 5, 6]
}
```

**Response:**
```json
{
  "message": "3 users activated successfully"
}
```

### 13. Bulk Deactivate Users
```http
POST /api/admin/users/bulk/deactivate
Content-Type: application/json

{
  "user_ids": [4, 5, 6]
}
```

**Response:**
```json
{
  "message": "3 users deactivated successfully"
}
```

### 14. Bulk Delete Users
```http
POST /api/admin/users/bulk/delete
Content-Type: application/json

{
  "user_ids": [4, 5, 6]
}
```

**Response:**
```json
{
  "message": "3 users deleted successfully"
}
```

## User Status Fields

### Status Values:
- **`is_active`**: `true`/`false` - Quick boolean flag for active/inactive
- **`status`**: `"active"`/`"inactive"`/`"suspended"` - Detailed status
- **`suspended_at`**: `timestamp` or `null` - When user was suspended
- **`suspension_reason`**: `string` or `null` - Reason for suspension

### Status Logic:
- **Active**: `is_active: true`, `status: "active"`, `suspended_at: null`
- **Inactive**: `is_active: false`, `status: "inactive"`, `suspended_at: null`
- **Suspended**: `is_active: false`, `status: "suspended"`, `suspended_at: timestamp`

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthorized. Admin authentication required."
}
```

### 404 Not Found
```json
{
  "message": "User not found"
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "name": ["The name field is required."]
  }
}
```

## Frontend Integration Notes

1. **Status Display**: Use the `status` field for display labels and `is_active` for quick boolean checks
2. **Real-time Updates**: The response includes updated user data, so you can update your frontend state immediately
3. **Bulk Operations**: Use the bulk endpoints for better UX when managing multiple users
4. **Pagination**: The users list endpoint supports pagination with `per_page` parameter
5. **Status Persistence**: All status changes are now stored in the database and will persist across page refreshes
