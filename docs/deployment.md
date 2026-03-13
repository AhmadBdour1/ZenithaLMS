# ZenithaLMS Deployment Guide

This guide covers deploying ZenithaLMS to production environments.

## Pre-Deployment Checklist

### 1. Environment Setup
- [ ] Server meets all system requirements
- [ ] PHP extensions installed and enabled
- [ ] Database server configured
- [ ] SSL certificate installed
- [ ] Domain name pointed to server

### 2. Application Preparation
- [ ] Source code uploaded to server
- [ ] Dependencies installed
- [ ] Environment file configured
- [ ] Application key generated
- [ ] File permissions set

### 3. Database Setup
- [ ] Database created
- [ ] User permissions configured
- [ ] Connection tested

## Deployment Methods

### Method 1: Manual Deployment

#### 1. Upload Application Files
```bash
# Upload files to web directory
scp -r zenithalms/ user@server:/var/www/
```

#### 2. Install Dependencies
```bash
cd /var/www/zenithalms
composer install --optimize-autoloader --no-dev
npm install --production
npm run build
```

#### 3. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file:
```env
APP_NAME="ZenithaLMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zenithalms_prod
DB_USERNAME=zenithalms_user
DB_PASSWORD=secure_password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

#### 4. Run Installation
```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 5. Set Permissions
```bash
chown -R www-data:www-data /var/www/zenithalms
chmod -R 755 /var/www/zenithalms/storage
chmod -R 755 /var/www/zenithalms/bootstrap/cache
```

### Method 2: Docker Deployment

#### 1. Create Dockerfile
```dockerfile
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www

# Install dependencies
RUN composer install --optimize-autoloader --no-dev

# Set permissions
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www/storage
RUN chmod -R 755 /var/www/bootstrap/cache

# Expose port
EXPOSE 9000

CMD ["php-fpm"]
```

#### 2. Create docker-compose.yml
```yaml
version: '3.8'

services:
  app:
    build: .
    container_name: zenithalms_app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./storage/app:/var/www/storage/app
    networks:
      - zenithalms

  webserver:
    image: nginx:alpine
    container_name: zenithalms_webserver
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx/conf.d:/etc/nginx/conf.d
    networks:
      - zenithalms

  db:
    image: mysql:8.0
    container_name: zenithalms_db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: zenithalms
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_PASSWORD: db_password
      MYSQL_USER: zenithalms_user
    volumes:
      - dbdata:/var/lib/mysql
    networks:
      - zenithalms

  redis:
    image: redis:7-alpine
    container_name: zenithalms_redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    networks:
      - zenithalms

networks:
  zenithalms:
    driver: bridge

volumes:
  dbdata:
    driver: local
```

#### 3. Deploy with Docker
```bash
docker-compose up -d
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan storage:link
docker-compose exec app php artisan config:cache
```

### Method 3: CI/CD Pipeline

#### GitHub Actions Example
```yaml
name: Deploy ZenithaLMS

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: mbstring, xml, ctype, iconv, intl, pdo, pdo_mysql, dom, filter, gd, hash, json, pcre, session, simplexml, spl, tokenizer, curl
        
    - name: Install Dependencies
      run: composer install --optimize-autoloader --no-dev
      
    - name: Build Assets
      run: |
        npm install
        npm run build
        
    - name: Deploy to Server
      uses: appleboy/ssh-action@v0.1.5
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.SSH_KEY }}
        script: |
          cd /var/www/zenithalms
          git pull origin main
          composer install --optimize-autoloader --no-dev
          npm install --production
          npm run build
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          php artisan queue:restart
```

## Web Server Configuration

### Apache Configuration

Create `/etc/apache2/sites-available/zenithalms.conf`:
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/zenithalms/public
    
    <Directory /var/www/zenithalms/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/zenithalms_error.log
    CustomLog ${APACHE_LOG_DIR}/zenithalms_access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/zenithalms/public
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    <Directory /var/www/zenithalms/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/zenithalms_error.log
    CustomLog ${APACHE_LOG_DIR}/zenithalms_access.log combined
</VirtualHost>
```

Enable site and modules:
```bash
sudo a2ensite zenithalms.conf
sudo a2enmod rewrite
sudo a2enmod ssl
sudo systemctl restart apache2
```

### Nginx Configuration

Create `/etc/nginx/sites-available/zenithalms`:
```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    root /var/www/zenithalms/public;
    index index.php index.html index.htm;

    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~ /\.(?!well-known).* {
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

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/zenithalms /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## Post-Deployment Configuration

### 1. Queue Worker Setup

Create supervisor configuration `/etc/supervisor/conf.d/zenithalms-worker.conf`:
```ini
[program:zenithalms-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/zenithalms/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/zenithalms/storage/logs/worker.log
stopwaitsecs=3600
```

Start worker:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start zenithalms-worker:*
```

### 2. Task Scheduler

Add cron job:
```bash
sudo crontab -e
```

Add this line:
```bash
* * * * * cd /var/www/zenithalms && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Cache Configuration

For Redis cache:
```bash
# Install Redis
sudo apt install redis-server

# Configure Redis
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

### 4. SSL Certificate

Using Let's Encrypt:
```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Get certificate
sudo certbot --apache -d your-domain.com -d www.your-domain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

## Performance Optimization

### 1. PHP Optimization

Edit `/etc/php/8.2/fpm/php.ini`:
```ini
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 64M
post_max_size = 64M
max_input_vars = 3000
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=0
opcache.fast_shutdown=1
```

### 2. Database Optimization

MySQL configuration:
```sql
-- Enable query cache
SET GLOBAL query_cache_type = ON;
SET GLOBAL query_cache_size = 268435456;

-- Optimize InnoDB
SET GLOBAL innodb_buffer_pool_size = 1073741824;
SET GLOBAL innodb_log_file_size = 268435456;
```

### 3. Web Server Optimization

Nginx optimization:
```nginx
worker_processes auto;
worker_connections 1024;

# Enable HTTP/2
listen 443 ssl http2;

# Client cache
location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

## Monitoring and Maintenance

### 1. Log Monitoring

Monitor application logs:
```bash
tail -f /var/www/zenithalms/storage/logs/laravel.log
```

### 2. Performance Monitoring

Install monitoring tools:
```bash
# Install htop
sudo apt install htop

# Monitor system resources
htop

# Monitor disk usage
df -h

# Monitor memory usage
free -h
```

### 3. Backup Strategy

Database backup script:
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u zenithalms_user -p zenithalms_prod > /backups/zenithalms_$DATE.sql
find /backups -name "zenithalms_*.sql" -mtime +7 -delete
```

File backup:
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
tar -czf /backups/zenithalms_files_$DATE.tar.gz /var/www/zenithalms/storage/app
find /backups -name "zenithalms_files_*.tar.gz" -mtime +7 -delete
```

## Security Hardening

### 1. Firewall Configuration

```bash
# Install UFW
sudo apt install ufw

# Configure firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

### 2. Security Headers

Add to Nginx configuration:
```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
```

### 3. File Permissions

```bash
# Secure file permissions
find /var/www/zenithalms -type f -exec chmod 644 {} \;
find /var/www/zenithalms -type d -exec chmod 755 {} \;
chmod -R 755 /var/www/zenithalms/storage
chmod -R 755 /var/www/zenithalms/bootstrap/cache
chown -R www-data:www-data /var/www/zenithalms
```

## Troubleshooting

### Common Issues

1. **502 Bad Gateway**
   - Check PHP-FPM status: `sudo systemctl status php8.2-fpm`
   - Restart PHP-FPM: `sudo systemctl restart php8.2-fpm`

2. **Database Connection Failed**
   - Test connection: `mysql -u username -p -h localhost database_name`
   - Check credentials in `.env` file

3. **Permission Denied**
   - Check file ownership: `ls -la /var/www/zenithalms`
   - Fix permissions: `sudo chown -R www-data:www-data /var/www/zenithalms`

4. **Queue Not Working**
   - Check worker status: `sudo supervisorctl status`
   - Restart worker: `sudo supervisorctl restart zenithalms-worker:*`

## Scaling Considerations

### Horizontal Scaling
- Load balancer configuration
- Database replication
- Shared storage solutions
- Session management

### Vertical Scaling
- Increase server resources
- Optimize application code
- Implement caching strategies
- Database optimization

## Disaster Recovery

### Backup Strategy
- Regular automated backups
- Off-site backup storage
- Backup verification
- Recovery testing

### Recovery Procedures
1. Assess damage and impact
2. Restore from latest backup
3. Verify data integrity
4. Test application functionality
5. Monitor for issues
