# ZenithaLMS Database Setup Guide

## Database Configuration

### Development Environment
For development, you can use SQLite (default):
```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

### Production Environment
For production, MySQL or PostgreSQL is strongly recommended:

#### MySQL Configuration
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zenithalms
DB_USERNAME=zenithalms_user
DB_PASSWORD=secure_password
```

#### PostgreSQL Configuration
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=zenithalms
DB_USERNAME=zenithalms_user
DB_PASSWORD=secure_password
```

## Migration Order

Run migrations in the following order:

1. Basic Laravel tables:
```bash
php artisan migrate
```

2. ZenithaLMS core tables (should run automatically):
- `2026_02_10_064655_create_roles_table.php`
- `2026_02_10_064713_create_permissions_table.php`
- `2026_02_10_070453_add_zenithalms_fields_to_users_table.php`
- `2026_02_21_000001_create_user_roles_table.php`
- `2026_02_21_000002_create_user_permissions_table.php`
- `2026_02_21_000003_create_role_permissions_table.php`
- `2026_02_21_000004_add_foreign_keys_to_users_table.php`

## Database Requirements

### MySQL
- Version: 8.0 or higher
- Storage Engine: InnoDB
- Character Set: utf8mb4
- Collation: utf8mb4_unicode_ci

### PostgreSQL
- Version: 12 or higher
- Character Set: UTF8

## Performance Optimizations

### MySQL
```sql
-- Enable query cache
SET GLOBAL query_cache_type = ON;
SET GLOBAL query_cache_size = 268435456; -- 256MB

-- Optimize InnoDB
SET GLOBAL innodb_buffer_pool_size = 1073741824; -- 1GB
SET GLOBAL innodb_log_file_size = 268435456; -- 256MB
```

### PostgreSQL
```sql
-- Optimize memory settings
-- shared_buffers = 256MB
-- effective_cache_size = 1GB
-- work_mem = 4MB
```

## Security Recommendations

1. **Use dedicated database user** with limited privileges
2. **Enable SSL connections** in production
3. **Regular backups** with automated scheduling
4. **Monitor slow queries** and optimize indexes
5. **Use environment variables** for credentials (never commit to git)

## Backup Strategy

### MySQL
```bash
# Full backup
mysqldump -u username -p zenithalms > backup_$(date +%Y%m%d).sql

# Compressed backup
mysqldump -u username -p zenithalms | gzip > backup_$(date +%Y%m%d).sql.gz
```

### PostgreSQL
```bash
# Full backup
pg_dump -U username zenithalms > backup_$(date +%Y%m%d).sql

# Compressed backup
pg_dump -U username zenithalms | gzip > backup_$(date +%Y%m%d).sql.gz
```

## Troubleshooting

### Common Issues

1. **Foreign key constraints**: Ensure parent tables exist before adding constraints
2. **Character encoding**: Use utf8mb4 for full Unicode support
3. **Connection limits**: Adjust max_connections in production
4. **Slow queries**: Add appropriate indexes and use query optimization

### Migration Issues
If migrations fail, try:
```bash
php artisan migrate:rollback
php artisan migrate:fresh --seed
```
