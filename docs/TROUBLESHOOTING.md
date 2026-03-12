# 🔧 ZenithaLMS - Troubleshooting Guide

## Common Issues & Solutions

---

## 🚨 Installation Issues

### Issue: "composer install fails"

**Symptoms:**
```
Your requirements could not be resolved...
```

**Solutions:**
1. Update Composer:
   ```bash
   composer self-update
   ```

2. Clear Composer cache:
   ```bash
   composer clear-cache
   composer install
   ```

3. Check PHP version:
   ```bash
   php -v  # Must be 8.2+
   ```

4. Install required PHP extensions:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip

   # macOS
   brew install php@8.2
   ```

---

### Issue: "npm install fails"

**Symptoms:**
```
ERR! code ELIFECYCLE
```

**Solutions:**
1. Clear npm cache:
   ```bash
   npm cache clean --force
   rm -rf node_modules package-lock.json
   npm install
   ```

2. Use Yarn instead:
   ```bash
   yarn install
   ```

3. Check Node version:
   ```bash
   node -v  # Must be 18+
   ```

---

## 🗄️ Database Issues

### Issue: "Migration fails"

**Symptoms:**
```
SQLSTATE[42S01]: Base table or view already exists
```

**Solutions:**
1. Fresh migration:
   ```bash
   php artisan migrate:fresh
   ```

2. Reset and migrate:
   ```bash
   php artisan migrate:reset
   php artisan migrate
   ```

3. Drop all tables manually, then:
   ```bash
   php artisan migrate
   ```

---

### Issue: "Foreign key constraint fails"

**Symptoms:**
```
SQLSTATE[23000]: Integrity constraint violation
```

**Solutions:**
1. Check migration order in `database/migrations/`
2. Ensure parent tables are created first
3. Run migrations in correct order:
   ```bash
   php artisan migrate:fresh --seed
   ```

---

### Issue: "Database connection refused"

**Symptoms:**
```
SQLSTATE[HY000] [2002] Connection refused
```

**Solutions:**
1. Check database is running:
   ```bash
   # MySQL
   sudo systemctl status mysql
   
   # PostgreSQL
   sudo systemctl status postgresql
   ```

2. Verify `.env` credentials:
   ```env
   DB_HOST=127.0.0.1  # Not 'localhost' for some systems
   DB_PORT=3306
   DB_DATABASE=zenithalms
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

3. Create database if not exists:
   ```sql
   CREATE DATABASE zenithalms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. For SQLite:
   ```bash
   touch database/database.sqlite
   chmod 666 database/database.sqlite
   ```

---

## 🔐 Authentication Issues

### Issue: "419 Page Expired" on login

**Symptoms:**
- CSRF token mismatch error

**Solutions:**
1. Clear all caches:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

2. Check session configuration in `.env`:
   ```env
   SESSION_DRIVER=file
   SESSION_LIFETIME=120
   ```

3. Ensure `storage/framework/sessions/` is writable:
   ```bash
   chmod -R 775 storage
   ```

---

### Issue: "Unauthenticated" API errors

**Symptoms:**
```json
{"message": "Unauthenticated."}
```

**Solutions:**
1. Include Bearer token in headers:
   ```bash
   Authorization: Bearer your_token_here
   ```

2. Check token hasn't expired
3. Generate new token:
   ```bash
   POST /api/login
   ```

---

## 📧 Email Issues

### Issue: "Email not sending"

**Symptoms:**
- No emails received
- No errors in logs

**Solutions:**
1. Test email configuration:
   ```bash
   php artisan tinker
   >>> Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));
   ```

2. Check `.env` settings:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-app-password  # Not regular password!
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=your-email@gmail.com
   ```

3. For Gmail:
   - Enable 2FA
   - Generate App Password at: https://myaccount.google.com/apppasswords
   - Use App Password in `MAIL_PASSWORD`

4. Check firewall allows port 587/465

5. Use queue for emails:
   ```bash
   php artisan queue:work
   ```

---

## 💰 Payment Issues

### Issue: "Stripe key invalid"

**Solutions:**
1. Ensure correct key type (test vs live):
   ```env
   # Test keys start with:
   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   
   # Live keys start with:
   STRIPE_KEY=pk_live_...
   STRIPE_SECRET=sk_live_...
   ```

2. No extra spaces in `.env`
3. Clear config cache:
   ```bash
   php artisan config:clear
   ```

---

### Issue: "PayPal payment not processing"

**Solutions:**
1. Check mode in `.env`:
   ```env
   PAYPAL_MODE=sandbox  # For testing
   # or
   PAYPAL_MODE=live     # For production
   ```

2. Verify credentials match mode (sandbox vs live)
3. Check webhook configuration
4. Enable debug mode temporarily:
   ```env
   APP_DEBUG=true
   ```
   Check `storage/logs/laravel.log`

---

## 🎨 Frontend Issues

### Issue: "CSS/JS not loading"

**Symptoms:**
- Unstyled pages
- Console errors

**Solutions:**
1. Build assets:
   ```bash
   npm run build
   # or for development:
   npm run dev
   ```

2. Clear view cache:
   ```bash
   php artisan view:clear
   ```

3. Check `public/build/` folder exists with files

4. Verify Vite configuration in `vite.config.js`

---

### Issue: "404 on asset files"

**Solutions:**
1. Check `.htaccess` or nginx config
2. Run:
   ```bash
   php artisan storage:link
   ```

3. Ensure `public/` is web root

---

## ⚡ Performance Issues

### Issue: "Slow page loads"

**Solutions:**
1. Enable caching:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. Use Redis for cache:
   ```env
   CACHE_DRIVER=redis
   SESSION_DRIVER=redis
   ```

3. Enable OPcache in `php.ini`:
   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=10000
   ```

4. Optimize autoloader:
   ```bash
   composer dump-autoload --optimize
   ```

---

### Issue: "High memory usage"

**Solutions:**
1. Increase PHP memory limit in `php.ini`:
   ```ini
   memory_limit = 512M
   ```

2. Use chunk() for large datasets:
   ```php
   Model::chunk(100, function($items) { ... });
   ```

3. Eager load relationships to avoid N+1:
   ```php
   Course::with(['instructor', 'category'])->get();
   ```

---

## 🔒 Permission Issues

### Issue: "Permission denied" errors

**Solutions:**
1. Set correct permissions:
   ```bash
   # Linux/macOS
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   
   # Development (not for production)
   sudo chmod -R 777 storage bootstrap/cache
   ```

2. For Docker:
   ```bash
   docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
   ```

---

## 🐛 Error 500

### Issue: "Internal Server Error"

**Solutions:**
1. Check Laravel logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. Enable debug mode temporarily:
   ```env
   APP_DEBUG=true
   ```
   ⚠️ Disable in production!

3. Check web server error logs:
   ```bash
   # Apache
   tail -f /var/log/apache2/error.log
   
   # Nginx
   tail -f /var/log/nginx/error.log
   ```

4. Common causes:
   - Missing PHP extensions
   - Permission issues
   - Syntax errors
   - Memory limit exceeded

---

## 🔄 Cache Issues

### Issue: "Changes not reflecting"

**Solutions:**
1. Clear all caches:
   ```bash
   php artisan optimize:clear
   ```

2. Restart PHP-FPM:
   ```bash
   sudo systemctl restart php8.2-fpm
   ```

3. For development, disable caching:
   ```env
   CACHE_DRIVER=array
   ```

---

## 📱 API Issues

### Issue: "CORS errors"

**Symptoms:**
```
Access to fetch blocked by CORS policy
```

**Solutions:**
1. Configure CORS in `config/cors.php`
2. Or install package:
   ```bash
   composer require fruitcake/laravel-cors
   ```

3. Add to `.env`:
   ```env
   SANCTUM_STATEFUL_DOMAINS=localhost:3000,yourdomain.com
   ```

---

## 🗄️ Queue Issues

### Issue: "Jobs not processing"

**Solutions:**
1. Start queue worker:
   ```bash
   php artisan queue:work
   ```

2. For production, use Supervisor:
   ```ini
   [program:zenithalms-worker]
   command=php /path/to/artisan queue:work --sleep=3 --tries=3
   autostart=true
   autorestart=true
   ```

3. Check failed jobs:
   ```bash
   php artisan queue:failed
   php artisan queue:retry all
   ```

---

## 🔍 Debugging Tips

### Enable Query Logging

```php
// In AppServiceProvider boot()
\DB::listen(function($query) {
    \Log::info($query->sql, $query->bindings);
});
```

### Check System Requirements

```bash
php artisan zenithalms:check-requirements
# Or manually:
php -m  # Check installed extensions
composer diagnose
```

### Test Database Connection

```bash
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## 📞 Still Need Help?

1. **Check Logs:**
   - Laravel: `storage/logs/laravel.log`
   - Web server: `/var/log/apache2/` or `/var/log/nginx/`
   - PHP: `/var/log/php8.2-fpm.log`

2. **Search Issues:**
   - Check GitHub Issues
   - Search Laravel forums
   - Stack Overflow

3. **Contact Support:**
   - Email: support@zenithalms.test
   - Include error logs and steps to reproduce

---

## 📋 Diagnostic Commands

Run these to gather information for support:

```bash
php -v
composer --version
npm -v
php artisan --version
php -m | grep -E "pdo|mbstring|xml|curl"
php artisan config:show
php artisan route:list | head -20
```

---

**Last Updated:** 2026-03-11  
**Version:** 1.0
