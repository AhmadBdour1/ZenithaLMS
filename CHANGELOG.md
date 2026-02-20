# Changelog

All notable changes to ZenithaLMS will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-XX

### Security
- Fixed XSS vulnerability in virtual-class display template
- Fixed mass assignment vulnerabilities in API controllers (Quiz, Forum, Ebook)
- Added authorization checks for sensitive operations using policies
- Enhanced Stripe webhook signature verification with proper error handling
- Implemented rate limiting for financial API endpoints (10 requests/minute)
- Implemented stricter rate limiting for sensitive operations (5 requests/minute)
- Enabled Sanctum token expiration (30 days)
- Added proper input validation and sanitization across API endpoints

### Added
- MIT License for open source distribution
- Comprehensive `.env.example` with all required configuration variables
- Enhanced security documentation and best practices
- Rate limiting configuration for API protection
- Authorization policies for Quiz, Forum, and Ebook models
- Security-focused middleware configurations

### Changed
- Improved error handling in webhook processing
- Enhanced input validation across all API controllers
- Updated payment processing with enhanced security measures
- Improved logging for security events and violations
- Enhanced API response structures for better error reporting

### Fixed
- PHP syntax errors in webhook controller switch statements
- XSS vulnerability in Blade template output escaping
- Mass assignment vulnerabilities in multiple API controllers
- Missing authorization checks in update/delete operations
- Insecure webhook signature verification

### Security Improvements
- Implemented defense-in-depth security architecture
- Added comprehensive input sanitization
- Enhanced authentication and authorization mechanisms
- Improved protection against common web vulnerabilities
- Added security headers and CORS configurations
- Implemented proper session management

### Deprecated
- Legacy authentication methods (replaced with Sanctum)
- Insecure direct object references (replaced with policy-based authorization)

### Removed
- Hardcoded credentials and sensitive data
- Unnecessary middleware and configurations

### Infrastructure
- Added Docker support for consistent development environments
- Enhanced configuration management
- Improved deployment security practices

---

## Security Policy

For information about reporting security vulnerabilities, please see [SECURITY.md](SECURITY.md).

## Contributing

For information about contributing to ZenithaLMS, please see [CONTRIBUTING.md](CONTRIBUTING.md).
