# ZenithaLMS Installation Contract

This document defines the official installation methods and requirements for ZenithaLMS.

## Supported Installation Methods

### Method 1: Web Installer (Primary Method)

**Target Users:** Shared hosting, non-technical users, quick deployments

**Prerequisites:**
- PHP 8.2+ with required extensions
- Web server (Apache/Nginx)
- Database server access
- File write permissions

**Installation Steps:**
1. Extract ZenithaLMS files to web directory
2. Set file permissions: `chmod -R 755 storage/ bootstrap/cache/`
3. Create database and configure `.env`
4. Generate application key: `php artisan key:generate`
5. Access web installer: `http://your-domain.com/install`
6. Follow on-screen instructions

**Expected Outcome:**
- Central database initialized
- Default tenant created with isolated database
- Admin user created
- Storage links configured
- Application ready for login

### Method 2: CLI Installer (Advanced Method)

**Target Users:** VPS/Dedicated servers, CI/CD pipelines, technical users

**Prerequisites:**
- Command line access
- Composer installed
- Node.js/NPM installed
- Database server access

**Installation Steps:**
1. Clone repository: `git clone https://github.com/AhmadBdour1/ZenithaLMS.git`
2. Install dependencies: `composer install --optimize-autoloader --no-dev`
3. Build frontend: `npm install && npm run build`
4. Configure environment: `cp .env.example .env && php artisan key:generate`
5. Run central migrations: `php artisan migrate:fresh --force`
6. Create tenant: `php artisan zenitha:create-default-tenant`
7. Seed tenant data: `php artisan tenants:seed --class=RoleSeeder --force`
8. Create admin user via tinker or custom script

**Expected Outcome:**
- Fully configured multi-tenant system
- Default tenant operational
- Admin user ready for login

## Installation Contract Requirements

### System Requirements
- **PHP:** 8.2+ with extensions (pdo, mbstring, tokenizer, xml, ctype, json, fileinfo, openssl)
- **Database:** MySQL 8.0+, PostgreSQL 12+, or SQLite 3.8+
- **Web Server:** Apache 2.4+ (with mod_rewrite) or Nginx 1.18+
- **Memory:** 256MB+ PHP memory limit
- **Disk Space:** 100MB+ for application files

### Database Requirements
- **Central Database:** For tenant management, domains, plans
- **Tenant Databases:** Each tenant gets isolated database
- **Permissions:** CREATE, ALTER, INDEX, DROP, SELECT, INSERT, UPDATE, DELETE
- **Character Set:** UTF8MB4 for MySQL, UTF8 for PostgreSQL

### File Permissions
```bash
# Required writable directories
storage/ (755)
storage/app/ (755)
storage/framework/ (755)
storage/logs/ (755)
bootstrap/cache/ (755)

# Optional for uploads
storage/app/public/ (755)
public/storage/ (755) # symlink created by installer
```

### Environment Configuration
Required `.env` variables:
```env
APP_NAME="ZenithaLMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zenithalms_central
DB_USERNAME=username
DB_PASSWORD=password

CACHE_DRIVER=redis
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

## Multi-Tenant Architecture

### Central Database Schema
- `tenants` - Tenant records
- `domains` - Domain mappings
- `plans` - Subscription plans
- `tenant_subscriptions` - Tenant subscriptions

### Tenant Database Schema
- `users` - User accounts
- `roles` - User roles
- `courses` - Course data
- `enrollments` - User enrollments
- `payments` - Payment records
- Plus 40+ additional tables for complete LMS functionality

### Database File Structure (SQLite)
```
database/
├── database.sqlite          # Central database
├── tenant_default.sqlite     # Default tenant database
├── tenant_org1.sqlite       # Organization 1 database
└── tenant_org2.sqlite       # Organization 2 database
```

## Installation Verification

### Post-Installation Checklist
- [ ] Central database accessible
- [ ] Default tenant created
- [ ] Tenant database accessible
- [ ] Admin user can login
- [ ] Dashboard loads without errors
- [ ] Storage links functional
- [ ] Queue worker configured (if using queues)

### Verification Commands
```bash
# Check tenant status
php artisan tenants:list

# Verify tenant database
php artisan zenitha:create-default-tenant

# Test admin login
# Visit: http://your-domain.com/login
```

## Re-run Safety

### Installation Prevention
- Installer checks for existing installation via `storage/app/installed.json`
- Second installation attempts are blocked with clear error message
- Manual removal required for re-installation

### Safe Re-installation Process
1. Backup existing data if needed
2. Remove installation marker: `rm storage/app/installed.json`
3. Clear caches: `php artisan cache:clear`
4. Re-run installer

### Data Integrity
- Duplicate tenant creation prevented by unique constraints
- Role seeding uses firstOrCreate to avoid duplicates
- Admin user creation checks for existing users

## Troubleshooting

### Common Issues
1. **Database Connection Failed**
   - Verify database credentials
   - Check database server status
   - Ensure database exists

2. **Permission Denied**
   - Check file permissions
   - Verify web server user ownership
   - Ensure SELinux configured (if applicable)

3. **Tenant Creation Failed**
   - Check database permissions
   - Verify disk space
   - Review error logs: `storage/logs/laravel.log`

### Support Channels
- Documentation: `/docs/` directory
- Troubleshooting: `/docs/TROUBLESHOOTING.md`
- Issues: GitHub repository issues

## Version Compatibility

### Laravel Version
- **Required:** Laravel 12.x
- **PHP Compatibility:** 8.2, 8.3
- **Database Support:** MySQL 8.0+, PostgreSQL 12+, SQLite 3.8+

### Dependency Updates
- Run `composer update` for Laravel updates
- Run `npm update` for frontend updates
- Test compatibility after major version updates

## Security Considerations

### Production Security
- Set `APP_DEBUG=false`
- Use HTTPS in production
- Configure firewall rules
- Regular security updates
- Database encryption at rest

### Installation Security
- Generate unique `APP_KEY`
- Use strong database passwords
- Restrict file permissions
- Validate environment configuration

## Warranty and Support

### Installation Warranty
- Installation contract covers standard environments
- Custom configurations may require additional support
- Community support available via GitHub

### Commercial Support
- Enterprise support available for commercial licenses
- Installation assistance included with commercial licenses
- Priority bug fixes and security updates
