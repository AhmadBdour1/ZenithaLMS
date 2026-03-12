# ZenithaLMS Docker Setup

This directory contains Docker configuration files for ZenithaLMS.

## Quick Start

1. **Build and start all services:**
   ```bash
   docker-compose up -d --build
   ```

2. **Install Laravel dependencies:**
   ```bash
   docker-compose exec app composer install
   ```

3. **Generate application key:**
   ```bash
   docker-compose exec app php artisan key:generate
   ```

4. **Run database migrations:**
   ```bash
   docker-compose exec app php artisan migrate
   ```

5. **Seed the database:**
   ```bash
   docker-compose exec app php artisan db:seed
   ```

6. **Access the application:**
   - Main application: http://localhost:8000
   - MySQL: localhost:3306
   - Redis: localhost:6379

## Services

### App (PHP-FPM)
- **Container**: `zenithalms_app`
- **Image**: Built from Dockerfile
- **Purpose**: Runs the Laravel application
- **Ports**: 8000 (via nginx)

### Nginx
- **Container**: `zenithalms_nginx`
- **Image**: nginx:alpine
- **Purpose**: Web server and reverse proxy
- **Ports**: 80, 443

### MySQL
- **Container**: `zenithalms_mysql`
- **Image**: mysql:8.0
- **Purpose**: Database server
- **Ports**: 3306
- **Credentials**: 
  - Database: `zenithalms`
  - Username: `zenitha`
  - Password: `secret`
  - Root password: `root_secret`

### Redis
- **Container**: `zenithalms_redis`
- **Image**: redis:7-alpine
- **Purpose**: Cache and session storage
- **Ports**: 6379

### Queue Worker
- **Container**: `zenithalms_queue`
- **Image**: Built from Dockerfile
- **Purpose**: Processes background jobs
- **Command**: `php artisan queue:work`

### Scheduler
- **Container**: `zenithalms_scheduler`
- **Image**: Built from Dockerfile
- **Purpose**: Runs scheduled tasks
- **Command**: `php artisan schedule:run` every 60 seconds

## Development Workflow

### Running Commands

Execute commands inside the app container:

```bash
# Artisan commands
docker-compose exec app php artisan

# Composer commands
docker-compose exec app composer

# NPM commands
docker-compose exec app npm

# Interactive shell
docker-compose exec app bash
```

### Logs

View logs for specific services:

```bash
# App logs
docker-compose logs app

# Nginx logs
docker-compose logs nginx

# MySQL logs
docker-compose logs mysql

# All logs
docker-compose logs -f
```

### Database Management

```bash
# Access MySQL CLI
docker-compose exec mysql mysql -u zenitha -psecret zenithalms

# Create database backup
docker-compose exec mysql mysqldump -u root -proot_secret zenithalms > backup.sql

# Restore database backup
docker-compose exec -T mysql mysql -u root -proot_secret zenithalms < backup.sql
```

## Environment Configuration

The Docker setup uses environment variables defined in `docker-compose.yml`. 
For development, you can override these by creating a `.env` file:

```env
# Database
DB_HOST=mysql
DB_DATABASE=zenithalms
DB_USERNAME=zenitha
DB_PASSWORD=secret

# Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis

# Queue
QUEUE_CONNECTION=redis
```

## Production Deployment

For production deployment:

1. **Update environment variables** in `docker-compose.yml`
2. **Use production-ready secrets**
3. **Enable SSL/TLS** in nginx configuration
4. **Set up proper backup strategies**
5. **Configure monitoring and logging**
6. **Use Docker volumes** for persistent data

## Troubleshooting

### Common Issues

1. **Permission errors:**
   ```bash
   docker-compose exec app chown -R www-data:www-data /var/www/html/storage
   docker-compose exec app chmod -R 755 /var/www/html/storage
   ```

2. **Database connection issues:**
   ```bash
   docker-compose restart mysql
   docker-compose exec app php artisan config:cache
   ```

3. **Queue not processing:**
   ```bash
   docker-compose restart queue
   docker-compose exec app php artisan queue:failed
   ```

### Performance Optimization

1. **Enable OPcache** (already configured)
2. **Use Redis for caching** (already configured)
3. **Optimize MySQL settings** (already configured)
4. **Enable gzip compression** (already configured)

## Security Considerations

- Database credentials are exposed in docker-compose.yml
- Use environment files or secrets management in production
- Regularly update base images
- Implement proper network isolation
- Use HTTPS in production

## Customization

### Adding New Services

1. Add service to `docker-compose.yml`
2. Update nginx configuration if needed
3. Add environment variables
4. Update documentation

### Modifying PHP Configuration

Edit `docker/php.ini` and rebuild:

```bash
docker-compose build app
docker-compose up -d app
```

### Modifying Nginx Configuration

Edit `docker/nginx/nginx.conf` and restart:

```bash
docker-compose restart nginx
```

## Support

For Docker-related issues:
1. Check container logs: `docker-compose logs [service]`
2. Verify configuration files
3. Check Docker and Docker Compose versions
4. Review system resources (memory, disk space)

For application issues, refer to the main ZenithaLMS documentation.
