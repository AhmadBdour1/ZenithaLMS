# ZenithaLMS Multi-Tenancy Setup Guide

## Overview

ZenithaLMS uses Stancl Tenancy for multi-tenant architecture. Each tenant gets:
- Isolated database
- Separate user management
- Independent course catalog
- Custom branding options

## Architecture

### Central vs Tenant Data

**Central Database** (Shared):
- Tenant management
- Domain mapping
- Subscription plans
- System-wide settings

**Tenant Databases** (Isolated):
- Users and authentication
- Courses and content
- Enrollments and progress
- Forums and discussions
- File uploads and media

## Tenant Creation Methods

### 1. Command Line Creation
```bash
# Create new tenant
php artisan tenant:create \
    --id="organization-name" \
    --domain="tenant.yourdomain.com" \
    --email="admin@tenant.com"

# Create with custom settings
php artisan tenant:create \
    --id="company-xyz" \
    --domain="company-xyz.yourdomain.com" \
    --email="admin@company-xyz.com" \
    --plan="enterprise"
```

### 2. Programmatic Creation
```php
// Create tenant
$tenant = App\Models\Central\Tenant::create([
    'id' => 'organization-name',
    'organization_name' => 'Organization Name',
    'admin_name' => 'Admin Name',
    'admin_email' => 'admin@organization.com',
    'plan' => 'premium',
    'logo_url' => 'https://example.com/logo.png',
    'primary_color' => '#3B82F6',
    'secondary_color' => '#10B981',
]);

// Create domain
$tenant->domains()->create([
    'domain' => 'tenant.yourdomain.com'
]);
```

### 3. Web Interface Creation
Access: `https://yourdomain.com/register-tenant`

## Domain Configuration

### 1. DNS Setup
For each tenant, configure:
```dns
# Subdomain approach
tenant1.yourdomain.com. A 192.168.1.100

# Custom domain approach
client1.com. CNAME tenant1.yourdomain.com
```

### 2. SSL Certificates
```bash
# Let's Encrypt for subdomains
certbot --apache -d tenant1.yourdomain.com

# Wildcard certificate (recommended)
certbot --apache -d *.yourdomain.com
```

### 3. Web Server Configuration

#### Apache VirtualHost
```apache
<VirtualHost *:443>
    ServerName tenant1.yourdomain.com
    DocumentRoot /var/www/zenithalms/public
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem
    
    <Directory /var/www/zenithalms/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx Server Block
```nginx
server {
    listen 443 ssl;
    server_name tenant1.yourdomain.com;
    root /var/www/zenithalms/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Tenant Management

### List All Tenants
```bash
php artisan tenants:list
```

### Migrate Specific Tenant
```bash
php artisan tenants:migrate --tenants=tenant-id
```

### Run Command on Tenant
```bash
php artisan tenants:run "cache:clear" --tenants=tenant-id
php artisan tenants:run "db:seed --class=DemoSeeder" --tenants=tenant-id
```

### Delete Tenant
```bash
php artisan tenants:delete tenant-id
```

## Tenant Customization

### 1. Branding Settings
```php
// Update tenant branding
$tenant->update([
    'logo_url' => 'https://example.com/new-logo.png',
    'primary_color' => '#FF6B6B',
    'secondary_color' => '#4ECDC4',
    'custom_css' => '.navbar { background: #FF6B6B; }'
]);
```

### 2. Feature Flags
```php
// Enable/disable features per tenant
$tenant->update([
    'data' => [
        'features' => [
            'forums' => true,
            'certificates' => true,
            'live_classes' => false,
            'ai_assistant' => true
        ]
    ]
]);
```

### 3. Custom Domains
```php
// Add custom domain to tenant
$tenant->domains()->create([
    'domain' => 'client-brand.com'
]);

// Remove domain
$tenant->domains()->where('domain', 'old-domain.com')->delete();
```

## Data Migration

### 1. Import Existing Data
```bash
# Import into specific tenant
php artisan tenants:run "db:import --file=backup.sql" --tenants=tenant-id
```

### 2. Export Tenant Data
```bash
# Export tenant database
php artisan tenants:run "db:export --file=tenant-backup.sql" --tenants=tenant-id
```

## Security Considerations

### 1. Database Isolation
- Each tenant has separate database
- No cross-tenant data access
- Tenant-specific database credentials

### 2. File Storage Isolation
```php
// Tenant-specific storage path
$tenantStoragePath = 'tenants/' . tenant('id') . '/uploads';

// Configure in tenant context
tenancy()->initialize($tenant);
config(['filesystems.disks.tenant.root' => $tenantStoragePath]);
```

### 3. Session Security
- Sessions isolated per tenant
- Cross-tenant session hijacking prevented
- Tenant-specific session cookies

## Performance Optimization

### 1. Database Optimization
```sql
-- Optimize for multi-tenant setup
SET GLOBAL innodb_file_per_table = ON;
SET GLOBAL innodb_buffer_pool_size = 1G;
```

### 2. Caching Strategy
```php
// Tenant-specific cache keys
$cacheKey = "tenant_" . tenant('id') . "_courses";

// Redis tenant isolation
Redis::set("tenant:{$tenantId}:data", $value);
```

### 3. Connection Pooling
```php
// Configure tenant database connections
'tenancy' => [
    'tenant_database_connections' => true,
    'database_connections' => [
        'tenant' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'database' => null, // Will be set dynamically
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
        ]
    ]
]
```

## Troubleshooting

### Common Issues

**Tenant Not Found (404)**
```bash
# Check tenant exists
php artisan tenants:list

# Check domain mapping
php artisan tinker
>>> App\Models\Central\Domain::where('domain', 'tenant.domain.com')->first();
```

**Database Connection Failed**
```bash
# Test tenant database
php artisan tenants:run "tinker" --tenants=tenant-id
>>> DB::connection()->getPdo();
```

**Migration Issues**
```bash
# Reset tenant migrations
php artisan tenants:migrate-fresh --tenants=tenant-id

# Check migration status
php artisan tenants:run "migrate:status" --tenants=tenant-id
```

## Monitoring

### 1. Tenant Activity
```php
// Track tenant usage
$tenantStats = [
    'users_count' => $tenant->users()->count(),
    'courses_count' => $tenant->courses()->count(),
    'storage_used' => $this->getTenantStorageUsage($tenant),
    'last_active' => $tenant->updated_at
];
```

### 2. Resource Monitoring
```bash
# Monitor tenant databases
mysql> SELECT table_schema, SUM(data_length + index_length) / 1024 / 1024 AS "Size (MB)" 
FROM information_schema.tables 
WHERE table_schema LIKE 'tenant_%' 
GROUP BY table_schema;
```

## Best Practices

1. **Always use tenant context** when working with tenant data
2. **Implement proper cleanup** when deleting tenants
3. **Monitor resource usage** per tenant
4. **Regular backups** of tenant databases
5. **Test tenant isolation** thoroughly
6. **Document custom domains** and their configurations
