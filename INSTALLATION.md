# ZenithaLMS Installation Guide

## System Requirements

### Minimum Requirements
- **PHP**: 8.2 or higher
- **MySQL**: 5.7+ or 8.0+ / MariaDB 10.3+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Node.js**: 18.0+ (for asset compilation)
- **Composer**: 2.0+
- **Memory**: 512MB PHP memory limit
- **Disk**: 500MB free space

### Recommended Requirements
- **PHP**: 8.3+ with OPcache
- **MySQL**: 8.0+
- **Memory**: 1GB+ PHP memory limit
- **Disk**: 2GB+ free space

### PHP Extensions Required
```ini
extension=ctype
extension=curl
extension=dom
extension=fileinfo
extension=filter
extension=gd
extension=hash
extension=iconv
extension=intl
extension=json
extension=mbstring
extension=openssl
extension=pcre
extension=pdo
extension=pdo_mysql
extension=session
extension=tokenizer
extension=xml
```

## Installation Steps

### 1. Download & Extract
```bash
# Extract the ZIP file to your web directory
unzip zenithalms-v1.0.0.zip
cd zenithalms-v1.0.0
```

### 2. Configure Database
```sql
-- Create MySQL database
CREATE DATABASE zenithalms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create database user (recommended)
CREATE USER 'zenithalms'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON zenithalms.* TO 'zenithalms'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Environment Setup
```bash
# Copy environment template
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file with your settings
nano .env
```

**Required .env Configuration:**
```env
APP_NAME="ZenithaLMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zenithalms
DB_USERNAME=zenithalms
DB_PASSWORD=your_secure_password

# Session & Cache
SESSION_DRIVER=database
CACHE_DRIVER=database

# Mail Configuration (required for emails)
MAIL_MAILER=smtp
MAIL_HOST=your-mail-server.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-mail-password
MAIL_ENCRYPTION=tls
```

### 4. Install Dependencies
```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node.js dependencies
npm install

# Build frontend assets
npm run build
```

### 5. Database Migration
```bash
# Run database migrations
php artisan migrate

# Optional: Seed with demo data
php artisan db:seed --class=DemoSeeder
```

### 6. Final Setup
```bash
# Create storage link
php artisan storage:link

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. Web Server Configuration

#### Apache (.htaccess included)
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>
    
    RewriteEngine On
    
    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    
    # Redirect Trailing Slashes...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    
    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/zenithalms/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## Multi-Tenancy Setup

### 1. Central Domain Configuration
Update `.env` with your central domain:
```env
TENANCY_BASE_DOMAIN=yourdomain.com
```

### 2. Create First Tenant
```bash
# Create tenant via artisan
php artisan tenant:create \
    --id="tenant1" \
    --domain="tenant1.yourdomain.com" \
    --email="admin@yourdomain.com"

# Or create programmatically
php artisan tinker
>>> App\Models\Central\Tenant::create([
...     'id' => 'tenant1',
...     'organization_name' => 'Organization Name',
...     'admin_name' => 'Admin Name',
...     'admin_email' => 'admin@yourdomain.com'
... ]);
```

### 3. Tenant Domain Setup
- Point tenant subdomain to your server
- Configure SSL certificates for each tenant
- Tenant will auto-migrate on first access

## Post-Installation

### 1. Access Application
- **Central Admin**: `https://yourdomain.com/admin`
- **Tenant Login**: `https://tenant1.yourdomain.com/login`

### 2. Default Credentials
If demo data was seeded:
```
Email: admin@zenithalms.test
Password: password123
```

### 3. Security Checklist
- [ ] Change default admin password
- [ ] Configure HTTPS/SSL
- [ ] Set up regular backups
- [ ] Configure email settings
- [ ] Review file permissions
- [ ] Set up monitoring

## Troubleshooting

### Common Issues

**500 Internal Server Error**
```bash
# Check Laravel log
tail storage/logs/laravel.log

# Check file permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

**Database Connection Failed**
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check database credentials
php artisan config:cache
php artisan config:clear
```

**Assets Not Loading**
```bash
# Rebuild assets
npm run build

# Clear view cache
php artisan view:clear

# Check storage link
php artisan storage:link
```

### Performance Optimization
```bash
# Enable OPcache in php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000

# Optimize Composer autoloader
composer dump-autoload --optimize

# Cache configuration for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Support

For installation issues:
1. Check system requirements
2. Review troubleshooting section
3. Check error logs: `storage/logs/laravel.log`
4. Verify file permissions
5. Test database connection
