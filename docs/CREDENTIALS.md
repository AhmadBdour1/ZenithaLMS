# ZenithaLMS Demo Credentials Guide

## Default Administrator Access

### Central Admin
```
URL: https://yourdomain.com/admin
Email: admin@zenithalms.test
Password: password123
```

### First Tenant Admin
```
URL: https://demo.yourdomain.com/login
Email: admin@demo.com
Password: password123
```

## Demo Users

### Student Account
```
Email: student@demo.com
Password: password123
Access: Enroll in courses, view progress, take quizzes
```

### Instructor Account
```
Email: instructor@demo.com
Password: password123
Access: Create courses, manage enrollments, view analytics
```

## Sample Data Overview

### Courses
- **Introduction to Laravel Development** (Free)
- **Advanced PHP Techniques** ($49.99)
- **Web Security Fundamentals** ($79.99)
- **Database Design Mastery** ($99.99)

### Users Structure
- 1 Central Administrator
- 1 Tenant Administrator  
- 2 Instructors
- 5 Students
- 1 Organization Admin

### Content Examples
- 12 Course lessons with video content
- 25 Quiz questions across different types
- 8 Forum discussion threads
- 15 Blog posts
- 5 E-book resources

## Feature Demonstration Checklist

### Core LMS Features
- [ ] User registration and email verification
- [ ] Course enrollment and payment flow
- [ ] Progress tracking and completion certificates
- [ ] Quiz taking with immediate feedback
- [ ] Forum discussions and replies
- [ ] Assignment submission and grading

### Multi-Tenancy Features
- [ ] Tenant creation and domain mapping
- [ ] Isolated user databases
- [ ] Tenant-specific branding
- [ ] Central tenant management
- [ ] Cross-tenant data isolation

### Administrative Features
- [ ] User management and roles
- [ ] Course creation and publishing
- [ ] Revenue and analytics dashboard
- [ ] System settings and configuration
- [ ] Bulk operations and data export

### Advanced Features
- [ ] AI-powered course recommendations
- [ ] Virtual classroom integration
- [ ] Certificate generation and templates
- [ ] Payment gateway integration
- [ ] Email notifications and alerts

## Screenshot Guide

### Homepage Screenshots
1. **Landing Page** - Hero section with course highlights
2. **Feature Showcase** - Key LMS capabilities
3. **Course Catalog** - Browse available courses
4. **Pricing Plans** - Subscription options

### Authentication Screenshots
1. **Login Page** - Clean login form
2. **Registration Form** - User sign-up process
3. **Password Reset** - Email verification flow
4. **Email Verification** - Account confirmation

### Dashboard Screenshots
1. **Student Dashboard** - Enrolled courses and progress
2. **Instructor Dashboard** - Course management tools
3. **Admin Dashboard** - System overview and analytics
4. **Organization Dashboard** - Multi-tenant management

### Course Screenshots
1. **Course List** - Filtered course browsing
2. **Course Details** - Course information and enrollment
3. **Lesson Player** - Video/content viewer
4. **Quiz Interface** - Interactive assessment
5. **Certificate View** - Completion achievement

### Administrative Screenshots
1. **User Management** - User directory and roles
2. **Course Creation** - Course builder interface
3. **Analytics Reports** - Usage and revenue data
4. **System Settings** - Configuration options
5. **Tenant Management** - Multi-tenant controls

## Data Privacy Notice

⚠️ **Important**: The demo data contains:
- Sample user accounts with predictable passwords
- Fictional course content and examples
- Test payment transactions (not real)
- Demo organizational data

**Before Production:**
1. Delete all demo user accounts
2. Change all default passwords
3. Remove sample course content
4. Clear all test data
5. Update contact information

## Setup Instructions

### 1. Access Demo Mode
```bash
# Seed demo data
php artisan db:seed --class=DemoSeeder

# Create demo tenant
php artisan tenant:create \
    --id="demo" \
    --domain="demo.yourdomain.com" \
    --email="admin@demo.com"
```

### 2. Verify Installation
1. Navigate to central domain
2. Login with admin credentials
3. Verify tenant creation
4. Access tenant subdomain
5. Test user roles and permissions

### 3. Disable Demo Mode
```bash
# Remove demo data
php artisan db:seed --class=RemoveDemoSeeder

# Or reset completely
php artisan migrate:fresh
```

## Support Resources

### Documentation
- **Installation Guide**: `INSTALLATION.md`
- **Tenancy Setup**: `docs/TENANCY.md`
- **API Documentation**: `/docs/api`
- **User Manual**: `/docs/user-guide`

### Troubleshooting
- Check error logs: `storage/logs/laravel.log`
- Verify database connections
- Test email configuration
- Validate file permissions

### Contact Support
For demo setup issues:
1. Review installation prerequisites
2. Check system requirements
3. Validate domain configuration
4. Test database connectivity
