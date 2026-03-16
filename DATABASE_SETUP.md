# ZenithaLMS Database Setup Guide

## Official Runtime Database: MySQL First Strategy

**ZenithaLMS uses MySQL as the official runtime database** for all production environments including:
- Production deployments
- Staging environments  
- Demo installations
- Commercial distribution

### 1. MySQL Setup (Recommended for Production)
```bash
# Create main application database
mysql -u root -p
CREATE DATABASE zenithalms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'zenithalms'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON zenithalms.* TO 'zenithalms'@'localhost';
FLUSH PRIVILEGES;

# Create central tenancy database
CREATE DATABASE zenithalms_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON zenithalms_central.* TO 'zenithalms'@'localhost';
FLUSH PRIVILEGES;

# Update .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zenithalms
DB_USERNAME=zenithalms
DB_PASSWORD=your_password

CENTRAL_DB_CONNECTION=central
CENTRAL_DB_DATABASE=zenithalms_central
```

### 2. PostgreSQL Setup (Alternative for Production)
```bash
# Create databases
sudo -u postgres psql
CREATE DATABASE zenithalms;
CREATE DATABASE zenithalms_central;
CREATE USER zenithalms WITH PASSWORD 'your_password';
GRANT ALL PRIVILEGES ON DATABASE zenithalms TO zenithalms;
GRANT ALL PRIVILEGES ON DATABASE zenithalms_central TO zenithalms;

# Update .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=zenithalms
DB_USERNAME=zenithalms
DB_PASSWORD=your_password

CENTRAL_DB_CONNECTION=central
CENTRAL_DB_DATABASE=zenithalms_central
```

## Development Setup (SQLite Only for Testing)

**SQLite is intended for development and testing only**, not for production deployments.

For local development, you can use SQLite:
```bash
# Update .env for SQLite development
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Note: Central database will still use MySQL in multi-tenancy setups
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
6. **tenants** - Multi-tenant architecture (central DB)
7. **domains** - Tenant domain mapping (central DB)

## Migration Command

```bash
php artisan migrate
```

## Database Strategy Summary

- **Production/Staging/Demo**: MySQL (recommended) or PostgreSQL
- **Development/Testing**: SQLite (temporary, not for production)
- **Multi-Tenancy**: Central database uses MySQL by default
- **Tenant Databases**: Can use MySQL, PostgreSQL, or SQLite per tenant

The MySQL-first strategy ensures optimal performance, compatibility, and production readiness for commercial ZenithaLMS deployments.
