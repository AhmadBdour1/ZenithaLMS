# ZenithaLMS Marketplace Package Structure

## ZIP Structure for Marketplace Upload

```
zenithalms-v1.0.0/
├── README.md                           # Main documentation
├── INSTALLATION.md                     # Installation guide
├── CHANGELOG.md                        # Version history
├── LICENSE                            # License file
├── .env.example                       # Environment template
├── composer.json                      # PHP dependencies
├── package.json                       # Node.js dependencies
├── artisan                            # Laravel CLI
├── app/                              # Application code
├── bootstrap/                         # Bootstrap files
├── config/                            # Configuration files
├── database/                          # Database migrations & seeds
│   ├── migrations/
│   └── seeders/
├── public/                            # Web root
│   ├── index.php
│   └── .htaccess
├── resources/                         # Views & assets
│   ├── views/
│   └── lang/
├── routes/                            # Route definitions
├── storage/                           # Storage (empty, with .gitignore)
├── tests/                             # Test suite
├── vendor/                            # PHP dependencies (include)
└── docs/                             # Additional documentation
    ├── SETUP.md
    ├── TENANCY.md
    └── CREDENTIALS.md
```

## Files to EXCLUDE from package:

### Development Artifacts:
- `.env` (local environment)
- `database/database.sqlite` (local database)
- `storage/app/installed.json` (install bypass)
- `storage/logs/` (log files)
- `storage/framework/cache/` (cache files)
- `node_modules/` (if including vendor)
- `.vscode/`, `.idea/` (IDE files)

### Optional Inclusions:
- `vendor/` - Include for easy installation
- `node_modules/` - Exclude unless required for demo

## Package Naming Convention:
- `zenithalms-v{version}.zip`
- Example: `zenithalms-v1.0.0.zip`

## Installation Flow:
1. Extract ZIP to web directory
2. Copy `.env.example` to `.env`
3. Configure database in `.env`
4. Run `composer install`
5. Run `npm install && npm run build`
6. Run `php artisan key:generate`
7. Run `php artisan migrate`
8. Visit domain to complete setup
