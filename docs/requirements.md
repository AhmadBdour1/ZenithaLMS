# ZenithaLMS System Requirements

This document outlines the system requirements for installing and running ZenithaLMS.

## Server Requirements

### PHP
- **Version**: PHP 8.1 or higher
- **Recommended**: PHP 8.2 or 8.3 for better performance

### PHP Extensions (Required)
- **PDO** - Database abstraction layer
- **PDO_MySQL** - MySQL driver (if using MySQL)
- **PDO_PGSQL** - PostgreSQL driver (if using PostgreSQL)  
- **PDO_SQLite** - SQLite driver (if using SQLite)
- **MBString** - Multi-byte string handling
- **Tokenizer** - PHP tokenizer
- **XML** - XML parsing
- **CTYPE** - Character type checking
- **JSON** - JSON handling
- **FileInfo** - File information
- **OpenSSL** - SSL/TLS support
- **BCMath** - Precision mathematics (optional, recommended)

### Web Server
- **Apache** 2.4+ with mod_rewrite
- **Nginx** 1.18+ with PHP-FPM
- **Laravel Valet** (for local development)

### Database
Choose one of the following:

#### MySQL
- **Version**: MySQL 8.0+ or MariaDB 10.3+
- **Privileges**: CREATE, ALTER, INDEX, DROP, SELECT, INSERT, UPDATE, DELETE

#### PostgreSQL  
- **Version**: PostgreSQL 12+
- **Privileges**: CREATE, ALTER, DROP, SELECT, INSERT, UPDATE, DELETE

#### SQLite
- **Version**: SQLite 3.8+
- **File System**: Write permissions for database directory

### Node.js (for asset compilation)
- **Version**: Node.js 18+ or 20+
- **Package Manager**: npm 9+ or yarn 1.22+

## Hardware Requirements

### Minimum Requirements
- **CPU**: 2 cores
- **RAM**: 4GB
- **Storage**: 20GB available space
- **Network**: 1 Mbps

### Recommended Requirements
- **CPU**: 4 cores
- **RAM**: 8GB
- **Storage**: 50GB available space (SSD recommended)
- **Network**: 10 Mbps

### Enterprise Requirements
- **CPU**: 8+ cores
- **RAM**: 16GB+
- **Storage**: 100GB+ (SSD recommended)
- **Network**: 100 Mbps+

## Software Dependencies

### Composer
- **Version**: Composer 2.0+
- **Purpose**: PHP dependency management

### Git (optional)
- **Version**: Git 2.0+
- **Purpose**: Version control (if cloning from repository)

## Optional Requirements

### Redis (for caching and queues)
- **Version**: Redis 6.0+
- **Purpose**: Application caching, queue management, sessions
- **Alternative**: File-based caching (default), Memcached

### Supervisor (for queue workers)
- **Version**: Supervisor 3.0+
- **Purpose**: Process management for background jobs
- **Alternative**: Cron jobs, systemd services

### SSL Certificate
- **Purpose**: HTTPS encryption
- **Required for**: Production environments
- **Options**: Let's Encrypt (free), commercial certificate

## Browser Requirements

### Supported Browsers
- **Chrome** 90+
- **Firefox** 88+
- **Safari** 14+
- **Edge** 90+

### Mobile Support
- **iOS Safari** 14+
- **Chrome Mobile** 90+
- **Samsung Internet** 15+

## Operating System Support

### Supported Systems
- **Linux**: Ubuntu 20.04+, CentOS 8+, Debian 11+
- **Windows**: Windows 10+, Windows Server 2019+
- **macOS**: macOS 11+

### Recommended for Production
- **Linux** (Ubuntu LTS recommended)
- **Windows Server** (for Windows-based deployments)

## Network Requirements

### Ports
- **HTTP**: Port 80
- **HTTPS**: Port 443
- **Database**: 3306 (MySQL), 5432 (PostgreSQL)
- **Redis**: 6379 (if using Redis)

### Firewall Configuration
- Allow inbound HTTP/HTTPS traffic
- Secure database ports (restrict to localhost if possible)
- Consider CDN for static assets

## Performance Considerations

### PHP Configuration
```ini
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 64M
post_max_size = 64M
max_input_vars = 3000
```

### Database Optimization
- Enable query cache
- Configure appropriate buffer sizes
- Regular maintenance and optimization

### File System
- Use SSD storage for better performance
- Ensure proper file permissions
- Configure backup strategies

## Security Requirements

### PHP Security
- Disable dangerous functions if not needed
- Configure open_basedir restriction
- Enable error reporting only in development

### Web Server Security
- Hide server signatures
- Implement security headers
- Use WAF if available

### Database Security
- Use strong passwords
- Restrict database user privileges
- Enable SSL connections

## Compliance Considerations

### Data Privacy
- GDPR compliance features
- Data encryption at rest
- Audit logging capabilities

### Accessibility
- WCAG 2.1 AA compliance
- Screen reader support
- Keyboard navigation

## Testing Requirements

### Development Environment
- Local PHP development server
- Database testing instance
- Version control setup

### Staging Environment  
- Production-like configuration
- Performance testing tools
- Security scanning tools

## Support and Maintenance

### Monitoring
- Application performance monitoring
- Error tracking
- Resource usage monitoring

### Backup Requirements
- Regular database backups
- File system backups
- Disaster recovery plan

### Update Management
- Regular security updates
- Dependency updates
- Feature updates
