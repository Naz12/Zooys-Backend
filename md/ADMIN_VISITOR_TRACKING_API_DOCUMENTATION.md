# Zooys Admin Visitor Tracking API Documentation

## Overview

The Admin Visitor Tracking API allows administrators to view, manage, and analyze visitor tracking data collected from the client frontend. This API provides comprehensive analytics, filtering capabilities, and CRUD operations for visitor tracking records.

**Base URL:** `http://localhost:8000/api/admin`

**Authentication:** Bearer token (Admin authentication required)

**All endpoints require:**
- `Authorization: Bearer {admin_token}` header
- Admin authentication middleware (`auth:sanctum` + `admin.auth`)

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Endpoints](#endpoints)
   - [List Visitor Tracking Records](#1-list-visitor-tracking-records)
   - [Get Statistics](#2-get-statistics)
   - [Get Single Visit](#3-get-single-visit)
   - [Update Visit](#4-update-visit)
   - [Delete Visit](#5-delete-visit)
4. [Data Structures](#data-structures)
5. [Query Parameters & Filters](#query-parameters--filters)
6. [Error Handling](#error-handling)
7. [Examples](#examples)

---

## Authentication

All admin visitor tracking endpoints require admin authentication.

### Headers Required

```http
Authorization: Bearer {admin_token}
Content-Type: application/json
Accept: application/json
```

### Getting Admin Token

```http
POST /api/admin/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "your_password"
}
```

**Response:**
```json
{
  "message": "Login successful",
  "admin": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com"
  },
  "token": "1|abc123def456..."
}
```

Use the `token` value in the `Authorization` header for all subsequent requests.

---

## Endpoints

### 1. List Visitor Tracking Records

Get paginated list of visitor tracking records with optional filtering.

**Endpoint:** `GET /admin/visitor-tracking`

**Authentication:** Required (Admin)

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page (default: 50, max: 100) |
| `start_date` | string | No | Filter by start date (format: Y-m-d, e.g., 2024-01-01) |
| `end_date` | string | No | Filter by end date (format: Y-m-d, e.g., 2024-01-31) |
| `tool_id` | string | No | Filter by tool ID (e.g., "ai_chat", "pdf_editor") |
| `user_id` | integer | No | Filter by user ID |
| `public_id` | string | No | Filter by public visitor ID |
| `session_id` | string | No | Filter by session ID |

**Example Request:**

```http
GET /api/admin/visitor-tracking?page=1&per_page=20&start_date=2024-01-01&end_date=2024-01-31&tool_id=ai_chat
Authorization: Bearer {admin_token}
```

**Success Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "tool_id": "ai_chat",
        "route_path": "/chat",
        "user_id": 123,
        "public_id": "550e8400-e29b-41d4-a716-446655440000",
        "session_id": "660e8400-e29b-41d4-a716-446655440001",
        "visited_at": "2024-01-15T10:30:00.000000Z",
        "referrer": "/",
        "location": {
          "country": "US",
          "country_name": "United States",
          "city": "New York",
          "region": "NY",
          "timezone": "America/New_York"
        },
        "ip_address": "192.168.1.1",
        "user_agent": "Mozilla/5.0...",
        "device_type": "desktop",
        "browser": "Chrome",
        "os": "Windows",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z",
        "user": {
          "id": 123,
          "name": "John Doe",
          "email": "john@example.com"
        }
      }
    ],
    "first_page_url": "http://localhost:8000/api/admin/visitor-tracking?page=1",
    "from": 1,
    "last_page": 10,
    "last_page_url": "http://localhost:8000/api/admin/visitor-tracking?page=10",
    "links": [...],
    "next_page_url": "http://localhost:8000/api/admin/visitor-tracking?page=2",
    "path": "http://localhost:8000/api/admin/visitor-tracking",
    "per_page": 20,
    "prev_page_url": null,
    "to": 20,
    "total": 200
  }
}
```

**Error Response (401 Unauthorized):**

```json
{
  "message": "Unauthorized. Admin authentication required."
}
```

**Error Response (500 Internal Server Error):**

```json
{
  "success": false,
  "error": "Failed to fetch visitor tracking data"
}
```

---

### 2. Get Statistics

Get comprehensive analytics and statistics for visitor tracking data.

**Endpoint:** `GET /admin/visitor-tracking/statistics`

**Authentication:** Required (Admin)

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `start_date` | string | No | Filter by start date (format: Y-m-d) |
| `end_date` | string | No | Filter by end date (format: Y-m-d) |
| `tool_id` | string | No | Filter by tool ID |
| `user_id` | integer | No | Filter by user ID |

**Example Request:**

```http
GET /api/admin/visitor-tracking/statistics?start_date=2024-01-01&end_date=2024-01-31
Authorization: Bearer {admin_token}
```

**Success Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "total_visits": 15234,
    "unique_visitors": 3421,
    "unique_sessions": 5678,
    "authenticated_visits": 8901,
    "anonymous_visits": 6333,
    "unique_authenticated_users": 1234,
    "tool_usage": {
      "ai_chat": 5234,
      "pdf_editor": 3124,
      "dashboard": 2890,
      "math": 1567,
      "flashcards": 1234,
      "presentations": 987,
      "diagrams": 198
    },
    "top_countries": {
      "US": 5234,
      "GB": 2341,
      "CA": 1890,
      "AU": 1234,
      "DE": 987,
      "FR": 765,
      "IT": 543,
      "ES": 432,
      "NL": 321,
      "BR": 234
    }
  }
}
```

**Statistics Fields Explained:**

- **total_visits**: Total number of visits recorded
- **unique_visitors**: Number of unique public IDs (unique visitors)
- **unique_sessions**: Number of unique session IDs
- **authenticated_visits**: Number of visits from logged-in users
- **anonymous_visits**: Number of visits from anonymous users
- **unique_authenticated_users**: Number of unique authenticated users who visited
- **tool_usage**: Object with tool IDs as keys and visit counts as values
- **top_countries**: Object with country codes as keys and visit counts as values (top 10)

**Error Response (500 Internal Server Error):**

```json
{
  "success": false,
  "error": "Failed to fetch visitor statistics"
}
```

---

### 3. Get Single Visit

Get detailed information about a specific visitor tracking record.

**Endpoint:** `GET /admin/visitor-tracking/{id}`

**Authentication:** Required (Admin)

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Visit ID |

**Example Request:**

```http
GET /api/admin/visitor-tracking/123
Authorization: Bearer {admin_token}
```

**Success Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "id": 123,
    "tool_id": "ai_chat",
    "route_path": "/chat",
    "user_id": 456,
    "public_id": "550e8400-e29b-41d4-a716-446655440000",
    "session_id": "660e8400-e29b-41d4-a716-446655440001",
    "visited_at": "2024-01-15T10:30:00.000000Z",
    "referrer": "/dashboard",
    "location": {
      "country": "US",
      "country_name": "United States",
      "city": "New York",
      "region": "NY",
      "timezone": "America/New_York"
    },
    "ip_address": "192.168.1.1",
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    "device_type": "desktop",
    "browser": "Chrome",
    "os": "Windows",
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z",
    "user": {
      "id": 456,
      "name": "Jane Smith",
      "email": "jane@example.com",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  }
}
```

**Error Response (404 Not Found):**

```json
{
  "success": false,
  "error": "Visit not found"
}
```

---

### 4. Update Visit

Update a visitor tracking record. Useful for correcting data or adding metadata.

**Endpoint:** `PUT /admin/visitor-tracking/{id}` or `PATCH /admin/visitor-tracking/{id}`

**Authentication:** Required (Admin)

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Visit ID |

**Request Body (All fields optional):**

```json
{
  "tool_id": "ai_chat",
  "route_path": "/chat",
  "user_id": 456,
  "referrer": "/dashboard",
  "location": {
    "country": "US",
    "country_name": "United States",
    "city": "New York",
    "region": "NY",
    "timezone": "America/New_York"
  },
  "ip_address": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "device_type": "desktop",
  "browser": "Chrome",
  "os": "Windows"
}
```

**Example Request:**

```http
PUT /api/admin/visitor-tracking/123
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "tool_id": "pdf_editor",
  "user_id": 789
}
```

**Success Response (200 OK):**

```json
{
  "success": true,
  "message": "Visit updated successfully",
  "data": {
    "id": 123,
    "tool_id": "pdf_editor",
    "route_path": "/chat",
    "user_id": 789,
    "public_id": "550e8400-e29b-41d4-a716-446655440000",
    "session_id": "660e8400-e29b-41d4-a716-446655440001",
    "visited_at": "2024-01-15T10:30:00.000000Z",
    "referrer": "/dashboard",
    "location": {
      "country": "US",
      "country_name": "United States",
      "city": "New York",
      "region": "NY",
      "timezone": "America/New_York"
    },
    "ip_address": "192.168.1.1",
    "user_agent": "Mozilla/5.0...",
    "device_type": "desktop",
    "browser": "Chrome",
    "os": "Windows",
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T11:45:00.000000Z"
  }
}
```

**Validation Error Response (422 Unprocessable Entity):**

```json
{
  "success": false,
  "error": "Validation failed",
  "details": {
    "user_id": ["The selected user id is invalid."],
    "ip_address": ["The ip address must be a valid IP address."]
  }
}
```

**Error Response (404 Not Found):**

```json
{
  "success": false,
  "error": "Visit not found"
}
```

---

### 5. Delete Visit

Delete a visitor tracking record.

**Endpoint:** `DELETE /admin/visitor-tracking/{id}`

**Authentication:** Required (Admin)

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Visit ID |

**Example Request:**

```http
DELETE /api/admin/visitor-tracking/123
Authorization: Bearer {admin_token}
```

**Success Response (200 OK):**

```json
{
  "success": true,
  "message": "Visit deleted successfully"
}
```

**Error Response (404 Not Found):**

```json
{
  "success": false,
  "error": "Visit not found"
}
```

---

## Data Structures

### Visit Object

```typescript
interface Visit {
  id: number;
  tool_id: string | null;
  route_path: string | null;
  user_id: number | null;
  public_id: string;
  session_id: string;
  visited_at: string; // ISO 8601 datetime
  referrer: string | null;
  location: LocationData | null;
  ip_address: string | null;
  user_agent: string | null;
  device_type: string | null; // "desktop" | "mobile" | "tablet"
  browser: string | null; // "Chrome" | "Firefox" | "Safari" | "Edge" | "Opera"
  os: string | null; // "Windows" | "macOS" | "Linux" | "Android" | "iOS"
  created_at: string; // ISO 8601 datetime
  updated_at: string; // ISO 8601 datetime
  user?: User | null; // Included when user_id is not null
}
```

### Location Data Object

```typescript
interface LocationData {
  country: string; // ISO country code (e.g., "US")
  country_name: string; // Full country name (e.g., "United States")
  city: string | null;
  region: string | null; // State/Province code
  timezone: string | null; // IANA timezone (e.g., "America/New_York")
}
```

### User Object (when included)

```typescript
interface User {
  id: number;
  name: string;
  email: string;
  created_at: string; // ISO 8601 datetime
}
```

### Statistics Object

```typescript
interface Statistics {
  total_visits: number;
  unique_visitors: number;
  unique_sessions: number;
  authenticated_visits: number;
  anonymous_visits: number;
  unique_authenticated_users: number;
  tool_usage: Record<string, number>; // tool_id => count
  top_countries: Record<string, number>; // country_code => count
}
```

---

## Query Parameters & Filters

### Date Range Filtering

Both `start_date` and `end_date` must be provided together to filter by date range:

```
?start_date=2024-01-01&end_date=2024-01-31
```

**Format:** `Y-m-d` (e.g., `2024-01-15`)

### Tool ID Filtering

Filter visits by specific tool:

```
?tool_id=ai_chat
```

**Supported Tool IDs:**
- `dashboard`
- `ai_chat`
- `pdf_editor`
- `math`
- `flashcards`
- `presentations`
- `diagrams`

### User ID Filtering

Filter visits by authenticated user:

```
?user_id=123
```

### Public ID Filtering

Filter visits by public visitor ID (for tracking a specific anonymous visitor):

```
?public_id=550e8400-e29b-41d4-a716-446655440000
```

### Session ID Filtering

Filter visits by session ID (for tracking a specific browser session):

```
?session_id=660e8400-e29b-41d4-a716-446655440001
```

### Combined Filters

You can combine multiple filters:

```
?start_date=2024-01-01&end_date=2024-01-31&tool_id=ai_chat&user_id=123
```

---

## Error Handling

### Standard Error Response Format

```json
{
  "success": false,
  "error": "Error message here"
}
```

### HTTP Status Codes

| Status Code | Description |
|-------------|-------------|
| `200` | Success |
| `201` | Created (not used in admin endpoints) |
| `401` | Unauthorized - Missing or invalid admin token |
| `404` | Not Found - Visit record not found |
| `422` | Validation Error - Invalid request data |
| `500` | Internal Server Error - Server-side error |

### Common Error Scenarios

#### 1. Missing Authentication

```http
GET /api/admin/visitor-tracking
```

**Response (401):**
```json
{
  "message": "Unauthorized. Admin authentication required."
}
```

#### 2. Invalid Visit ID

```http
GET /api/admin/visitor-tracking/99999
Authorization: Bearer {admin_token}
```

**Response (404):**
```json
{
  "success": false,
  "error": "Visit not found"
}
```

#### 3. Validation Error

```http
PUT /api/admin/visitor-tracking/123
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "user_id": 99999,
  "ip_address": "invalid-ip"
}
```

**Response (422):**
```json
{
  "success": false,
  "error": "Validation failed",
  "details": {
    "user_id": ["The selected user id is invalid."],
    "ip_address": ["The ip address must be a valid IP address."]
  }
}
```

---

## Examples

### Example 1: Get All Visits for a Specific Tool

```bash
curl -X GET "http://localhost:8000/api/admin/visitor-tracking?tool_id=ai_chat&per_page=50" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Accept: application/json"
```

### Example 2: Get Statistics for Last Month

```bash
curl -X GET "http://localhost:8000/api/admin/visitor-tracking/statistics?start_date=2024-01-01&end_date=2024-01-31" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Accept: application/json"
```

### Example 3: Get Visits for a Specific User

```bash
curl -X GET "http://localhost:8000/api/admin/visitor-tracking?user_id=123&per_page=100" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Accept: application/json"
```

### Example 4: Update a Visit Record

```bash
curl -X PUT "http://localhost:8000/api/admin/visitor-tracking/456" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "tool_id": "pdf_editor",
    "user_id": 789
  }'
```

### Example 5: Get Single Visit Details

```bash
curl -X GET "http://localhost:8000/api/admin/visitor-tracking/789" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Accept: application/json"
```

### Example 6: Delete a Visit Record

```bash
curl -X DELETE "http://localhost:8000/api/admin/visitor-tracking/789" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Accept: application/json"
```

### Example 7: Get Visits with Date Range and Multiple Filters

```bash
curl -X GET "http://localhost:8000/api/admin/visitor-tracking?start_date=2024-01-01&end_date=2024-01-31&tool_id=ai_chat&user_id=123&page=1&per_page=25" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Accept: application/json"
```

### Example 8: JavaScript/Fetch Example

```javascript
// Get visitor statistics
async function getVisitorStatistics(startDate, endDate) {
  const response = await fetch(
    `http://localhost:8000/api/admin/visitor-tracking/statistics?start_date=${startDate}&end_date=${endDate}`,
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Accept': 'application/json'
      }
    }
  );
  
  const data = await response.json();
  return data;
}

// Get paginated visits
async function getVisits(page = 1, filters = {}) {
  const params = new URLSearchParams({
    page: page.toString(),
    per_page: '50',
    ...filters
  });
  
  const response = await fetch(
    `http://localhost:8000/api/admin/visitor-tracking?${params}`,
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Accept': 'application/json'
      }
    }
  );
  
  const data = await response.json();
  return data;
}

// Update a visit
async function updateVisit(visitId, updateData) {
  const response = await fetch(
    `http://localhost:8000/api/admin/visitor-tracking/${visitId}`,
    {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(updateData)
    }
  );
  
  const data = await response.json();
  return data;
}
```

---

## Best Practices

### 1. Pagination

Always use pagination when fetching large datasets:

```
?page=1&per_page=50
```

**Recommended `per_page` values:**
- Small datasets: 10-25
- Medium datasets: 50
- Large datasets: 100 (max)

### 2. Date Range Queries

Always provide both `start_date` and `end_date` together for date filtering:

```
✅ ?start_date=2024-01-01&end_date=2024-01-31
❌ ?start_date=2024-01-01 (missing end_date)
```

### 3. Caching Statistics

Statistics endpoint can be resource-intensive. Consider caching results:

```javascript
// Cache statistics for 5 minutes
const cacheKey = `visitor_stats_${startDate}_${endDate}`;
const cached = localStorage.getItem(cacheKey);
if (cached && Date.now() - JSON.parse(cached).timestamp < 300000) {
  return JSON.parse(cached).data;
}
```

### 4. Error Handling

Always handle errors gracefully:

```javascript
try {
  const response = await fetch(url, options);
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.error || 'Request failed');
  }
  return await response.json();
} catch (error) {
  console.error('API Error:', error);
  // Show user-friendly error message
}
```

### 5. Rate Limiting

Be mindful of rate limits. Don't make excessive requests:

- Statistics: Cache for 5-10 minutes
- List visits: Use pagination, don't fetch all at once
- Updates/Deletes: Batch operations when possible

---

## Integration Notes

### Frontend Integration

The admin dashboard should:

1. **Store admin token** securely (localStorage, sessionStorage, or secure cookie)
2. **Include token** in all requests via `Authorization` header
3. **Handle 401 errors** by redirecting to login
4. **Implement pagination** for visit lists
5. **Cache statistics** to reduce API calls
6. **Show loading states** during API calls
7. **Display errors** in user-friendly format

### Dashboard Widgets

Common dashboard widgets using this API:

1. **Total Visits Widget**: Use statistics endpoint
2. **Recent Visits Table**: Use list endpoint with pagination
3. **Tool Usage Chart**: Use statistics endpoint `tool_usage` field
4. **Geographic Map**: Use statistics endpoint `top_countries` field
5. **Visitor Timeline**: Use list endpoint with date filters

---

## Support

For issues or questions:
- Check error responses for detailed messages
- Verify admin token is valid and not expired
- Ensure date formats are correct (Y-m-d)
- Check that visit IDs exist before updating/deleting

---

**Last Updated:** 2024-01-15  
**API Version:** 1.0.0

