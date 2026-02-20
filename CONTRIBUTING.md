# Contributing to ZenithaLMS

Thank you for your interest in contributing to ZenithaLMS! This document provides guidelines and information for contributors.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)
- [Testing Guidelines](#testing-guidelines)
- [Security](#security)
- [Documentation](#documentation)

## Code of Conduct

By participating in this project, you agree to abide by our Code of Conduct:
- Be respectful and inclusive
- Welcome newcomers and help them learn
- Focus on what is best for the community
- Show empathy towards other community members

## Getting Started

### Prerequisites

- PHP 8.2+ 
- Composer
- Node.js 18+
- npm or yarn
- Git

### Fork and Clone

1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/your-username/ZenithaLMS.git
   cd ZenithaLMS
   ```

3. Add the original repository as upstream:
   ```bash
   git remote add upstream https://github.com/AhmadBdour1/ZenithaLMS.git
   ```

## Development Setup

1. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. **Frontend Build**
   ```bash
   npm run build
   ```

5. **Start Development Server**
   ```bash
   php artisan serve
   ```

## Pull Request Process

### 1. Create a Branch

Create a descriptive branch for your feature or fix:
```bash
git checkout -b feature/your-feature-name
# or
git checkout -b fix/issue-description
```

### 2. Make Your Changes

- Follow the coding standards outlined below
- Write tests for new functionality
- Update documentation as needed
- Ensure all tests pass

### 3. Test Your Changes

```bash
# Run PHP tests
php artisan test

# Run JavaScript tests
npm test

# Check code style
./vendor/bin/pint
npm run lint
```

### 4. Submit Your Pull Request

1. Commit your changes with clear messages
2. Push to your fork:
   ```bash
   git push origin feature/your-feature-name
   ```
3. Create a pull request on GitHub
4. Fill out the pull request template completely
5. Wait for code review

## Coding Standards

### PHP

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style
- Use Laravel conventions and best practices
- Write clear, self-documenting code
- Add type hints where possible
- Keep methods small and focused

### JavaScript

- Use modern ES6+ syntax
- Follow ESLint configuration
- Use meaningful variable and function names
- Add JSDoc comments for complex functions

### General

- Write meaningful commit messages
- Keep pull requests focused and reasonably sized
- Update documentation for any API changes
- Consider backward compatibility

## Testing Guidelines

### PHP Tests

- Write unit tests for business logic
- Write feature tests for user interactions
- Mock external dependencies
- Aim for high code coverage

### JavaScript Tests

- Write unit tests for utility functions
- Write integration tests for components
- Test user interactions
- Use appropriate test assertions

### Test Commands

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Run with coverage
php artisan test --coverage

# Run JavaScript tests
npm test
```

## Security

### Reporting Vulnerabilities

If you discover a security vulnerability, please follow our [Security Policy](SECURITY.md):
- Do not create a public issue
- Email security@zenithalms.com
- Provide detailed information about the vulnerability
- Allow us time to fix before disclosure

### Security Best Practices

- Never commit sensitive data
- Use environment variables for configuration
- Validate all user inputs
- Sanitize all outputs
- Follow secure coding practices
- Keep dependencies updated

## Documentation

### API Documentation

- Update API documentation for any endpoint changes
- Include request/response examples
- Document authentication requirements
- Provide error response details

### Code Documentation

- Add PHPDoc blocks for classes and methods
- Comment complex logic
- Update README.md for major changes
- Maintain clear inline comments

## Getting Help

- Check existing [Issues](https://github.com/AhmadBdour1/ZenithaLMS/issues)
- Search [Discussions](https://github.com/AhmadBdour1/ZenithaLMS/discussions)
- Ask questions in appropriate channels
- Be patient and respectful

## License

By contributing to ZenithaLMS, you agree that your contributions will be licensed under the same [MIT License](LICENSE) as the project.

---

Thank you for contributing to ZenithaLMS! Your contributions help make this project better for everyone.
