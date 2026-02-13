# Phase 2: Test Accounts Report

## Multi-Role Test Accounts

The following test accounts have been created and are ready for automated crawling and testing:

### 🔴 Administrator Account
| Field | Value |
|-------|-------|
| **Email** | admin@zenithalms.com |
| **Password** | password |
| **Name** | Admin User |
| **Role** | admin |
| **Organization** | ZenithaLMS |
| **Permissions** | Full system access, user management, content moderation, system settings |

**Accessible Routes**:
- `/dashboard/admin` - Admin dashboard
- `/system/analytics` - System analytics
- `/system/reports` - System reports  
- `/system/settings` - System settings
- `/system/users` - User management
- `/ebooks/admin/*` - Ebook administration
- All API endpoints with `auth:sanctum` middleware

### 🎓 Instructor Account
| Field | Value |
|-------|-------|
| **Email** | instructor@zenithalms.com |
| **Password** | password |
| **Name** | John Instructor |
| **Role** | instructor |
| **Organization** | ZenithaLMS |
| **Title** | Senior Instructor |
| **Bio** | Experienced instructor with 10+ years of teaching experience |

**Accessible Routes**:
- `/dashboard/instructor` - Instructor dashboard
- `/ebooks/create` - Create new ebooks
- `/ebooks/{id}/edit` - Edit owned ebooks
- `/ebooks/my-ebooks` - View owned content
- Course management routes
- Quiz creation and management
- Virtual class hosting

### 👨‍🎓 Student Account
| Field | Value |
|-------|-------|
| **Email** | student@zenithalms.com |
| **Password** | password |
| **Name** | Jane Student |
| **Role** | student |
| **Organization** | ZenithaLMS |

**Accessible Routes**:
- `/dashboard/student` - Student dashboard
- `/courses` - Browse courses
- `/ebooks` - Browse ebooks
- `/ebooks/{slug}` - View ebook details
- `/ebooks/read/{ebookId}` - Read purchased ebooks
- `/ebooks/download/{ebookId}` - Download purchased ebooks
- `/ebooks/my-ebooks` - View library
- `/ebooks/favorites/{ebookId}` - Manage favorites
- Course enrollment and progress tracking
- Quiz taking

### 🏢 Organization Admin Account
| Field | Value |
|-------|-------|
| **Email** | demo@zenithalms.com |
| **Password** | password |
| **Name** | Demo Admin |
| **Role** | organization_admin |
| **Organization** | Demo University |

**Accessible Routes**:
- `/dashboard/organization` - Organization dashboard
- Organization-specific user management
- Organization analytics
- Content management within organization

## Account Credentials Summary

| Role | Email | Password | Organization |
|------|-------|----------|---------------|
| Admin | admin@zenithalms.com | password | ZenithaLMS |
| Instructor | instructor@zenithalms.com | password | ZenithaLMS |
| Student | student@zenithalms.com | password | ZenithaLMS |
| Organization Admin | demo@zenithalms.com | password | Demo University |

## Authentication Test Matrix

### Public Routes (No Authentication)
✅ **Any User** can access:
- Homepage (`/`)
- Login/Register forms
- Course browsing (`/courses`)
- Ebook browsing (`/ebooks`)
- Search functionality
- Password reset flows

### Authenticated Routes (Any Logged-in User)
✅ **All Accounts** can access:
- Dashboard (role-based redirect)
- Profile management (`/profile`)
- Logout functionality
- Password change

### Role-Specific Routes
| Route | Admin | Instructor | Student | Org Admin |
|-------|-------|------------|---------|-----------|
| `/dashboard/admin` | ✅ | ❌ | ❌ | ❌ |
| `/dashboard/instructor` | ❌ | ✅ | ❌ | ❌ |
| `/dashboard/student` | ❌ | ❌ | ✅ | ❌ |
| `/dashboard/organization` | ❌ | ❌ | ❌ | ✅ |
| `/system/*` | ✅ | ❌ | ❌ | ❌ |
| `/ebooks/admin/*` | ✅ | ❌ | ❌ | ❌ |
| `/ebooks/create` | ✅ | ✅ | ❌ | ✅ |
| `/ebooks/{id}/edit` | ✅ | ✅* | ❌ | ✅ |

*Instructors can only edit their own content

## API Authentication

All accounts can be used for API testing with Laravel Sanctum:

### API Token Acquisition
```bash
# Login to get token
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@zenithalms.com", "password": "password"}'
```

### API Access Levels
- **Public APIs**: No token required
- **User APIs**: Any authenticated user
- **Admin APIs**: Admin role required
- **Content APIs**: Role-based permissions

## Test Data Available

### Courses (3 total)
1. **Introduction to Web Development** - Beginner level, $49.99
2. **Advanced JavaScript Programming** - Advanced level, $79.99  
3. **Data Science Fundamentals** - Beginner level, $89.99

### Ebooks (2 total)
1. **JavaScript: The Complete Guide** - $29.99
2. **CSS Mastery: From Basics to Advanced** - $24.99

### Categories (10 total)
- Programming, Web Development, Mobile Development, Data Science, Machine Learning
- Business, Design, Marketing, Languages, Other

### Forums and Blog Posts
- Sample forum posts for discussion testing
- Blog posts for content testing

## Security Notes

⚠️ **Important**: These are development/test credentials only:
- All passwords are simple (`password`)
- Accounts should only be used in development environment
- Do not use these credentials in production
- Change passwords before any production deployment

## Automated Testing Ready

These accounts are configured for:
✅ **Backend HTTP Tests** - Laravel Feature tests can use `actingAs()`  
✅ **UI E2E Tests** - Playwright can login with these credentials  
✅ **API Testing** - Sanctum token generation works for all roles  
✅ **Role-based Testing** - Each role provides different access levels for testing  

## Next Phase Ready

Test accounts are fully configured and ready for **Phase 3: Automated Route Crawling**:
- Backend HTTP tests with role-based authentication
- UI crawling with Playwright using different user contexts  
- API endpoint testing with proper authentication
- Cross-role access validation
