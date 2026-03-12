# 📡 ZenithaLMS API Documentation

## Base URL
```
Production: https://yourdomain.com/api/v1
Development: http://localhost:8000/api/v1
```

## Authentication

All API requests require authentication using Laravel Sanctum tokens.

### Register
```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "student"
    },
    "token": "1|abc123def456..."
  }
}
```

### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "2|xyz789..."
  }
}
```

### Logout
```http
POST /api/logout
Authorization: Bearer {token}
```

### Using Token
Include in all subsequent requests:
```http
Authorization: Bearer {your_token_here}
```

---

## 📚 Courses

### List All Courses
```http
GET /api/v1/courses
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `per_page` (int): Items per page (default: 15)
- `search` (string): Search in title/description
- `category_id` (int): Filter by category
- `level` (string): beginner|intermediate|advanced
- `is_free` (boolean): true|false
- `sort` (string): latest|popular|price_low|price_high

**Example:**
```http
GET /api/v1/courses?category_id=1&level=beginner&page=1
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "courses": [
      {
        "id": 1,
        "title": "Complete Web Development Bootcamp",
        "slug": "complete-web-development",
        "description": "Learn HTML, CSS, JavaScript...",
        "thumbnail_url": "https://...",
        "price": 99.99,
        "is_free": false,
        "level": "beginner",
        "duration_minutes": 7200,
        "instructor": {
          "id": 2,
          "name": "Dr. Sarah Johnson"
        },
        "category": {
          "id": 1,
          "name": "Web Development"
        },
        "enrolled_students_count": 1250,
        "average_rating": 4.8,
        "created_at": "2026-01-15T10:00:00Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "per_page": 15,
      "total": 45,
      "last_page": 3
    }
  }
}
```

### Get Single Course
```http
GET /api/v1/courses/{id}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "course": {
      "id": 1,
      "title": "Complete Web Development Bootcamp",
      "description": "Full description...",
      "content": "Detailed content...",
      "price": 99.99,
      "instructor": {...},
      "category": {...},
      "lessons": [
        {
          "id": 1,
          "title": "Introduction to HTML",
          "duration_minutes": 45,
          "is_free": true
        }
      ],
      "requirements": ["Basic computer skills"],
      "what_you_will_learn": ["HTML5", "CSS3", "JavaScript"],
      "is_enrolled": false
    }
  }
}
```

### Enroll in Course
```http
POST /api/v1/courses/{id}/enroll
Authorization: Bearer {token}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Enrolled successfully",
  "data": {
    "enrollment": {
      "id": 1,
      "course_id": 1,
      "user_id": 1,
      "status": "active",
      "progress_percentage": 0,
      "enrolled_at": "2026-03-11T14:30:00Z"
    }
  }
}
```

---

## 📖 Lessons

### Get Course Lessons
```http
GET /api/v1/courses/{course_id}/lessons
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "lessons": [
      {
        "id": 1,
        "title": "Introduction to HTML",
        "description": "Learn HTML basics",
        "duration_minutes": 45,
        "sort_order": 1,
        "is_free": true,
        "is_completed": false,
        "video_url": "https://..."
      }
    ]
  }
}
```

### Get Single Lesson
```http
GET /api/v1/lessons/{id}
Authorization: Bearer {token}
```

### Mark Lesson as Complete
```http
POST /api/v1/lessons/{id}/complete
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Lesson completed",
  "data": {
    "progress_percentage": 15.5
  }
}
```

---

## 📝 Enrollments

### Get My Enrollments
```http
GET /api/v1/my-enrollments
Authorization: Bearer {token}
```

**Query Parameters:**
- `status` (string): active|completed|dropped

**Response (200):**
```json
{
  "success": true,
  "data": {
    "enrollments": [
      {
        "id": 1,
        "course": {
          "id": 1,
          "title": "Complete Web Development",
          "thumbnail_url": "https://..."
        },
        "status": "active",
        "progress_percentage": 35.5,
        "enrolled_at": "2026-02-01T10:00:00Z",
        "last_accessed_at": "2026-03-10T15:30:00Z"
      }
    ]
  }
}
```

### Get Enrollment Details
```http
GET /api/v1/enrollments/{id}
Authorization: Bearer {token}
```

### Drop Course
```http
POST /api/v1/enrollments/{id}/drop
Authorization: Bearer {token}
```

---

## 🎯 Quizzes

### Get Course Quizzes
```http
GET /api/v1/courses/{course_id}/quizzes
Authorization: Bearer {token}
```

### Start Quiz Attempt
```http
POST /api/v1/quizzes/{id}/start
Authorization: Bearer {token}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "attempt_id": 123,
    "quiz": {
      "id": 1,
      "title": "HTML Basics Quiz",
      "time_limit_minutes": 30,
      "questions_count": 10
    },
    "started_at": "2026-03-11T14:45:00Z"
  }
}
```

### Submit Quiz Answer
```http
POST /api/v1/quiz-attempts/{attempt_id}/answer
Authorization: Bearer {token}
Content-Type: application/json

{
  "question_id": 1,
  "answer": "A"
}
```

### Submit Quiz
```http
POST /api/v1/quiz-attempts/{attempt_id}/submit
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "score": 85,
    "total_questions": 10,
    "correct_answers": 9,
    "passed": true,
    "certificate_eligible": true
  }
}
```

---

## 🏆 Certificates

### Get My Certificates
```http
GET /api/v1/my-certificates
Authorization: Bearer {token}
```

### Download Certificate
```http
GET /api/v1/certificates/{id}/download
Authorization: Bearer {token}
```

---

## 👤 User Profile

### Get My Profile
```http
GET /api/v1/profile
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "avatar_url": "https://...",
      "bio": "Passionate learner",
      "role": "student",
      "enrolled_courses_count": 5,
      "completed_courses_count": 2,
      "certificates_count": 2,
      "total_learning_hours": 120
    }
  }
}
```

### Update Profile
```http
PUT /api/v1/profile
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "John Smith",
  "bio": "Updated bio",
  "phone": "+1234567890"
}
```

### Upload Avatar
```http
POST /api/v1/profile/avatar
Authorization: Bearer {token}
Content-Type: multipart/form-data

avatar: [file]
```

---

## 🔔 Notifications

### Get Notifications
```http
GET /api/v1/notifications
Authorization: Bearer {token}
```

### Mark as Read
```http
POST /api/v1/notifications/{id}/read
Authorization: Bearer {token}
```

### Mark All as Read
```http
POST /api/v1/notifications/read-all
Authorization: Bearer {token}
```

---

## 💰 Payments

### Get Payment Methods
```http
GET /api/v1/payment-methods
Authorization: Bearer {token}
```

### Create Payment Intent
```http
POST /api/v1/payments/intent
Authorization: Bearer {token}
Content-Type: application/json

{
  "course_id": 1,
  "payment_method": "stripe"
}
```

### Confirm Payment
```http
POST /api/v1/payments/confirm
Authorization: Bearer {token}
Content-Type: application/json

{
  "payment_intent_id": "pi_123abc",
  "course_id": 1
}
```

---

## 📊 Admin Endpoints

### Dashboard Stats
```http
GET /api/v1/admin/dashboard
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "total_users": 1250,
    "total_courses": 45,
    "total_enrollments": 3890,
    "total_revenue": 89500.00,
    "recent_enrollments": [...],
    "popular_courses": [...]
  }
}
```

### Manage Users
```http
GET /api/v1/admin/users
POST /api/v1/admin/users
PUT /api/v1/admin/users/{id}
DELETE /api/v1/admin/users/{id}
```

### Manage Courses
```http
GET /api/v1/admin/courses
POST /api/v1/admin/courses
PUT /api/v1/admin/courses/{id}
DELETE /api/v1/admin/courses/{id}
```

---

## 📋 Categories

### List Categories
```http
GET /api/v1/categories
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "categories": [
      {
        "id": 1,
        "name": "Web Development",
        "slug": "web-development",
        "courses_count": 15
      }
    ]
  }
}
```

---

## 🔍 Search

### Global Search
```http
GET /api/v1/search?q=javascript&type=courses
Authorization: Bearer {token}
```

**Query Parameters:**
- `q` (string): Search query
- `type` (string): courses|users|lessons (default: all)

---

## ⚠️ Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "This action is unauthorized"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Resource not found"
}
```

### 422 Unprocessable Entity
```json
{
  "success": false,
  "message": "The given data was invalid",
  "errors": {...}
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Server error occurred"
}
```

---

## 📊 Rate Limiting

- **Default:** 60 requests per minute
- **Authenticated:** 120 requests per minute

**Headers:**
```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 119
```

---

## 🧪 Testing

### Postman Collection
Import the collection: `/docs/postman/ZenithaLMS.postman_collection.json`

### Example cURL
```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@zenithalms.test","password":"password123"}'

# Get courses (with token)
curl -X GET http://localhost:8000/api/v1/courses \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## 📝 Notes

- All timestamps are in ISO 8601 format (UTC)
- All monetary values are in USD
- File uploads: max 10MB
- Supported image formats: jpg, png, webp
- Supported video formats: mp4, webm
- API versioning: `/api/v1`, `/api/v2` (future)

---

**Version:** 1.0  
**Last Updated:** 2026-03-11  
**Base URL:** `https://api.zenithalms.test/v1`
