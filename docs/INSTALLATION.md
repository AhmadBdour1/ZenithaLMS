# ZenithaLMS Installation Guide

This guide will help you install and configure ZenithaLMS on your server.

## System Requirements

- **PHP**: >= 8.1
- **Web Server**: Apache, Nginx, or Laravel Valet
- **Database**: MySQL 8.0+ or PostgreSQL 12+ or SQLite 3.8+
- **PHP Extensions**:
  - PDO
  - PDO_MySQL or PDO_PGSQL or PDO_SQLite
  - MBString
  - Tokenizer
  - XML
  - CTYPE
  - JSON
  - BCMath
  - FileInfo
  - OpenSSL

## Installation Methods

### Method 1: Web Installer (Recommended for Shared Hosting)

1. **Upload Files**
   ```bash
   Upload all ZenithaLMS files to your web directory
   ```

2. **Set Permissions**
   ```bash
   chmod -R 755 storage/
   chmod -R 755 bootstrap/cache/
   ```

3. **Create Database**
   - Create a new database for ZenithaLMS
   - Note the database name, username, and password

4. **Configure Environment**
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` file with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=zenithalms_db
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

6. **Run Web Installer**
   - Visit your website in the browser
   - You will be automatically redirected to the installer
   - Follow the on-screen instructions

7. **Complete Installation**
   - The installer will:
     - Check system requirements
     - Test database connection
     - Run database migrations
     - Create admin user
     - Set up storage links
     - Clear caches

### Method 2: CLI Installer (Recommended for VPS/Dedicated Servers)

1. **Clone or Upload Files**
   ```bash
   git clone https://github.com/your-repo/zenithalms.git
   cd zenithalms
   ```

2. **Install Dependencies**
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Edit .env File**
   Configure your database connection and other settings

5. **Run Installation Command**
   ```bash
   # Basic installation
   php artisan zenitha:install
   
   # Installation with demo data
   php artisan zenitha:install --demo
   ```

6. **Follow the Prompts**
   - The command will guide you through the installation process
   - It will ask for admin user details

## Post-Installation Configuration

### 1. Queue Worker (Required for background jobs)

Create a supervisor configuration file:

```ini
[program:zenithalms-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/zenithalms/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/zenithalms/storage/logs/worker.log
stopwaitsecs=3600
```

### 2. Task Scheduler (Required for automated tasks)

Add this cron job to your system:

```bash
* * * * * cd /path/to/your/zenithalms && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Web Server Configuration

#### Apache (.htaccess)

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Disable index view
Options -Indexes

# Hide a .htaccess file
<Files .htaccess>
    Order allow,deny
    Deny from all
</Files>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/your/zenithalms/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
}
```

## Environment Variables

Key environment variables to configure:

```env
# Application
APP_NAME="ZenithaLMS"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zenithalms_db
DB_USERNAME=username
DB_PASSWORD=password

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# File Storage
FILESYSTEM_DISK=public

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis
```

## Security Recommendations

1. **Environment File**: Never commit `.env` to version control
2. **File Permissions**: Ensure only web server can write to storage directories
3. **HTTPS**: Always use HTTPS in production
4. **Regular Updates**: Keep PHP and dependencies updated
5. **Backup**: Regular database and file backups

## Troubleshooting

### Common Issues

1. **500 Internal Server Error**
   - Check file permissions
   - Verify `.env` configuration
   - Check Laravel logs: `storage/logs/laravel.log`

2. **Database Connection Failed**
   - Verify database credentials
   - Check if database server is running
   - Ensure database exists

3. **Storage Link Issues**
   ```bash
   php artisan storage:link
   ```

4. **Cache Issues**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

5. **Queue Not Working**
   ```bash
   php artisan queue:failed
   php artisan queue:retry all
   ```

## Getting Help

- **Documentation**: Check the official documentation
- **Community**: Join our Discord community
- **Issues**: Report bugs on GitHub
- **Support**: Contact support team for enterprise licenses

## Updating ZenithaLMS

1. **Backup your application and database**
2. **Download the latest version**
3. **Update dependencies**: `composer update`
4. **Run migrations**: `php artisan migrate`
5. **Clear caches**: `php artisan cache:clear`

## License

ZenithaLMS is released under the MIT License. See [LICENSE.md](LICENSE.md) for details.
