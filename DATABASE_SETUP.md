# ZenithaLMS Database Setup Guide

## Production Setup (MySQL/PostgreSQL Recommended)

### 1. MySQL Setup
```bash
# Create database
mysql -u root -p
CREATE DATABASE zenithalms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'zenithalms'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON zenithalms.* TO 'zenithalms'@'localhost';
FLUSH PRIVILEGES;

# Update .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zenithalms
DB_USERNAME=zenithalms
DB_PASSWORD=your_password
```

### 2. PostgreSQL Setup
```bash
# Create database
sudo -u postgres psql
CREATE DATABASE zenithalms;
CREATE USER zenithalms WITH PASSWORD 'your_password';
GRANT ALL PRIVILEGES ON DATABASE zenithalms TO zenithalms;

# Update .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=zenithalms
DB_USERNAME=zenithalms
DB_PASSWORD=your_password
```

## Development Setup (SQLite)

For local development, SQLite is already configured:
```bash
# Current .env settings
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

## Migration Notes

The following migrations have been created/modified:
- ✅ users table (standard Laravel users)
- ✅ courses table (with instructor/category relationships)
- ✅ categories table (for course categorization)
- ✅ cache table (for database cache driver)
- ✅ sessions table (for database session driver)

## Required Database Tables for Production

1. **users** - User authentication and profiles
2. **courses** - Course content and metadata
3. **categories** - Course categorization
4. **cache** - Laravel cache functionality
5. **sessions** - Laravel session management
6. **tenants** - Multi-tenant architecture
7. **domains** - Tenant domain mapping

## Migration Command

```bash
php artisan migrate
```
