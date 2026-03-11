# 🚀 ZenithaLMS - Quick Start Guide

## Welcome to ZenithaLMS!

This guide will get you up and running in **15 minutes**.

---

## 📋 Prerequisites

- ✅ PHP 8.2 or higher
- ✅ Composer 2.x
- ✅ Node.js 18+ & NPM/Yarn
- ✅ MySQL 8.0+ / PostgreSQL 12+ / SQLite
- ✅ Redis (optional, recommended)

---

## ⚡ Quick Installation

### Step 1: Clone & Install Dependencies (5 min)

```bash
# Clone the repository
git clone https://github.com/yourusername/ZenithaLMS.git
cd ZenithaLMS

# Install PHP dependencies
composer install

# Install Node dependencies  
npm install
# or
yarn install
```

### Step 2: Environment Setup (3 min)

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zenithalms
DB_USERNAME=root
DB_PASSWORD=your_password

# Or use SQLite for quick testing:
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

### Step 3: Database Setup (5 min)

```bash
# Create database (if using SQLite)
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed initial data
php artisan db:seed

# Optional: Seed demo data
php artisan db:seed --class=DemoDataSeeder
```

### Step 4: Build & Run (2 min)

```bash
# Build frontend assets
npm run build
# or for development with hot reload:
npm run dev

# Start Laravel server
php artisan serve

# Visit: http://localhost:8000
```

---

## 🔐 Default Login Credentials

After seeding, you can login with:

**Admin Account:**
- Email: `admin@zenithalms.test`
- Password: `password123`

**Instructor Account:**
- Email: `instructor@zenithalms.test`
- Password: `password123`

**Student Account:**
- Email: `student@zenithalms.test`
- Password: `password123`

⚠️ **Change these passwords immediately in production!**

---

## 🎯 Key Features

### Core LMS
- ✅ Course Management
- ✅ User Management (Students, Instructors, Admins)
- ✅ Enrollment System
- ✅ Progress Tracking
- ✅ Quiz & Assessments
- ✅ Certificates

### Advanced Features
- ✅ Multi-Tenant Architecture
- ✅ AI-Powered Recommendations
- ✅ Payment Integration (Stripe, PayPal)
- ✅ Ebook Marketplace
- ✅ Virtual Classrooms
- ✅ Forum & Blog
- ✅ Gamification (Badges, Achievements)

### Enterprise Features
- ✅ Organization Management
- ✅ Branch & Department Support
- ✅ Advanced RBAC (Role-Based Access Control)
- ✅ Audit Logs
- ✅ Analytics & Reporting

---

## 🔧 Configuration

### Email Setup

Edit `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

### Payment Integration

Edit `.env`:
```env
# Stripe
STRIPE_KEY=pk_test_your_key
STRIPE_SECRET=sk_test_your_secret

# PayPal
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_CLIENT_SECRET=your_secret
```

See `/docs/API_KEYS_GUIDE.md` for detailed instructions.

### Cache (Recommended)

For better performance, use Redis:
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## 📱 API Access

### Base URL
```
http://localhost:8000/api/v1
```

### Authentication
```bash
# Register
POST /api/register
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}

# Login
POST /api/login
{
  "email": "john@example.com",
  "password": "password123"
}

# Use token in requests:
Authorization: Bearer {your_token}
```

### Example Requests
```bash
# Get all courses
GET /api/v1/courses

# Enroll in course
POST /api/v1/courses/{id}/enroll

# Get my enrollments
GET /api/v1/my-enrollments
```

Full API documentation: `/docs/API_DOCUMENTATION.md`

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=CourseTest

# With coverage
php artisan test --coverage
```

---

## 📊 Admin Panel

Access at: `http://localhost:8000/admin`

### Key Admin Features:
- Dashboard with analytics
- User management
- Course approval
- Payment monitoring
- System settings
- Reports & exports

---

## 🚀 Deployment

### Production Checklist

```bash
# 1. Set environment
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# 2. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev

# 3. Build assets
npm run build

# 4. Set permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 5. Setup queue worker
php artisan queue:work --daemon

# 6. Setup cron job
* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
```

### Recommended Hosting:
- **Shared Hosting:** Use Laravel Forge or Ploi
- **VPS:** DigitalOcean, AWS, Linode
- **Managed:** Laravel Vapor, Laravel Cloud

---

## 🔒 Security

### Production Security Checklist:
- ✅ Change all default passwords
- ✅ Use HTTPS (SSL certificate)
- ✅ Set `APP_DEBUG=false`
- ✅ Configure firewall
- ✅ Enable rate limiting
- ✅ Regular backups
- ✅ Keep dependencies updated

---

## 📚 Documentation

- **Installation Guide:** `/docs/INSTALLATION.md`
- **API Documentation:** `/docs/API_DOCUMENTATION.md`
- **Database Schema:** `/docs/DATABASE_SETUP.md`
- **API Keys Guide:** `/docs/API_KEYS_GUIDE.md`
- **Troubleshooting:** `/docs/TROUBLESHOOTING.md`

---

## 🆘 Getting Help

### Common Issues:

**"Class not found"**
```bash
composer dump-autoload
php artisan config:clear
```

**"Storage link not working"**
```bash
php artisan storage:link
```

**"Migration error"**
```bash
php artisan migrate:fresh
php artisan db:seed
```

**"Assets not loading"**
```bash
npm run build
php artisan view:clear
```

### Support Channels:
- 📧 Email: support@zenithalms.test
- 📖 Documentation: `/docs`
- 🐛 Issues: GitHub Issues

---

## 🎓 Next Steps

1. ✅ Explore the admin panel
2. ✅ Create your first course
3. ✅ Configure payment gateway
4. ✅ Customize branding
5. ✅ Invite users
6. ✅ Set up email notifications
7. ✅ Configure SSL for production

---

## 📄 License

This project is proprietary software. See `LICENSE` file for details.

---

**Version:** 1.0  
**Last Updated:** 2026-03-11  
**Requires:** PHP 8.2+, Laravel 12

---

🚀 **Happy Teaching & Learning!**
