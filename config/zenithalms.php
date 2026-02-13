<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ZenithaLMS Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the ZenithaLMS Learning Management System.
    | Here you can configure AI services, payment gateways, themes, and other
    | platform-specific settings.
    |
    */
    
    'ai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 2000),
        'temperature' => env('OPENAI_TEMPERATURE', 0.7),
        'timeout' => env('OPENAI_TIMEOUT', 30),
    ],
    
    'payment' => [
        'default_currency' => env('DEFAULT_CURRENCY', 'USD'),
        'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET'),
        'auto_refund_days' => env('AUTO_REFUND_DAYS', 7),
        'minimum_amount' => env('MINIMUM_PAYMENT_AMOUNT', 1.00),
        'gateways' => [
            'stripe' => [
                'enabled' => env('STRIPE_ENABLED', true),
                'secret_key' => env('STRIPE_SECRET'),
                'publishable_key' => env('STRIPE_KEY'),
                'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            ],
            'paypal' => [
                'enabled' => env('PAYPAL_ENABLED', true),
                'client_id' => env('PAYPAL_CLIENT_ID'),
                'client_secret' => env('PAYPAL_SECRET'),
                'sandbox' => env('PAYPAL_SANDBOX', true),
                'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
            ],
            'wallet' => [
                'enabled' => env('WALLET_ENABLED', true),
                'minimum_balance' => env('WALLET_MINIMUM_BALANCE', 0),
                'maximum_balance' => env('WALLET_MAXIMUM_BALANCE', 10000),
            ],
        ],
    ],
    
    'theme' => [
        'default_theme' => env('DEFAULT_THEME', 'zenithalms-default'),
        'allow_user_themes' => env('ALLOW_USER_THEMES', true),
        'themes_path' => resource_path('views/themes'),
        'custom_css_path' => public_path('css/themes'),
    ],
    
    'notifications' => [
        'email' => [
            'enabled' => env('EMAIL_NOTIFICATIONS_ENABLED', true),
            'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@zenithalms.com'),
            'from_name' => env('MAIL_FROM_NAME', 'ZenithaLMS'),
        ],
        'push' => [
            'enabled' => env('PUSH_NOTIFICATIONS_ENABLED', true),
            'fcm_key' => env('FCM_KEY'),
            'fcm_sender_id' => env('FCM_SENDER_ID'),
        ],
        'sms' => [
            'enabled' => env('SMS_NOTIFICATIONS_ENABLED', false),
            'provider' => env('SMS_PROVIDER', 'twilio'),
            'twilio_sid' => env('TWILIO_SID'),
            'twilio_token' => env('TWILIO_TOKEN'),
            'twilio_from' => env('TWILIO_FROM'),
        ],
    ],
    
    'storage' => [
        'default' => env('FILESYSTEM_DISK', 'local'),
        'cloud' => env('CLOUD_STORAGE_ENABLED', false),
        'max_file_size' => env('MAX_FILE_SIZE', 10240), // KB
        'allowed_extensions' => explode(',', env('ALLOWED_FILE_EXTENSIONS', 'jpg,jpeg,png,gif,pdf,doc,docx,mp4,mp3')),
    ],
    
    'cache' => [
        'default_ttl' => env('CACHE_DEFAULT_TTL', 3600), // seconds
        'user_cache_ttl' => env('CACHE_USER_TTL', 1800),
        'course_cache_ttl' => env('CACHE_COURSE_TTL', 7200),
        'analytics_cache_ttl' => env('CACHE_ANALYTICS_TTL', 300),
    ],
    
    'security' => [
        'password_min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'session_timeout' => env('SESSION_TIMEOUT', 120), // minutes
        'max_login_attempts' => env('MAX_LOGIN_ATTEMPTS', 5),
        'lockout_duration' => env('LOCKOUT_DURATION', 15), // minutes
        'require_email_verification' => env('REQUIRE_EMAIL_VERIFICATION', true),
        'two_factor_enabled' => env('TWO_FACTOR_ENABLED', false),
    ],
    
    'analytics' => [
        'enabled' => env('ANALYTICS_ENABLED', true),
        'tracking_code' => env('ANALYTICS_TRACKING_CODE'),
        'retention_days' => env('ANALYTICS_RETENTION_DAYS', 365),
        'real_time_enabled' => env('REAL_TIME_ANALYTICS_ENABLED', true),
    ],
    
    'features' => [
        'ai_recommendations' => env('AI_RECOMMENDATIONS_ENABLED', true),
        'adaptive_learning' => env('ADAPTIVE_LEARNING_ENABLED', true),
        'virtual_classes' => env('VIRTUAL_CLASSES_ENABLED', true),
        'certificates' => env('CERTIFICATES_ENABLED', true),
        'forums' => env('FORUMS_ENABLED', true),
        'blogs' => env('BLOGS_ENABLED', true),
        'ebooks' => env('EBOOKS_ENABLED', true),
        'quizzes' => env('QUIZZES_ENABLED', true),
        'assignments' => env('ASSIGNMENTS_ENABLED', true),
        'gamification' => env('GAMIFICATION_ENABLED', false),
        'social_learning' => env('SOCIAL_LEARNING_ENABLED', true),
    ],
    
    'limits' => [
        'max_courses_per_user' => env('MAX_COURSES_PER_USER', 50),
        'max_enrollments_per_user' => env('MAX_ENROLLMENTS_PER_USER', 100),
        'max_file_upload_size' => env('MAX_FILE_UPLOAD_SIZE', 100), // MB
        'max_quiz_attempts' => env('MAX_QUIZ_ATTEMPTS', 3),
        'max_forum_posts_per_day' => env('MAX_FORUM_POSTS_PER_DAY', 10),
        'max_virtual_class_participants' => env('MAX_VIRTUAL_CLASS_PARTICIPANTS', 100),
    ],
    
    'webhooks' => [
        'payment_completed' => env('WEBHOOK_PAYMENT_COMPLETED_URL'),
        'user_registered' => env('WEBHOOK_USER_REGISTERED_URL'),
        'course_enrolled' => env('WEBHOOK_COURSE_ENROLLED_URL'),
        'quiz_completed' => env('WEBHOOK_QUIZ_COMPLETED_URL'),
        'certificate_issued' => env('WEBHOOK_CERTIFICATE_ISSUED_URL'),
        'timeout' => env('WEBHOOK_TIMEOUT', 30),
        'retry_attempts' => env('WEBHOOK_RETRY_ATTEMPTS', 3),
    ],
    
    'integrations' => [
        'google_analytics' => [
            'enabled' => env('GOOGLE_ANALYTICS_ENABLED', false),
            'tracking_id' => env('GOOGLE_ANALYTICS_TRACKING_ID'),
        ],
        'facebook_pixel' => [
            'enabled' => env('FACEBOOK_PIXEL_ENABLED', false),
            'pixel_id' => env('FACEBOOK_PIXEL_ID'),
        ],
        'linkedin_insights' => [
            'enabled' => env('LINKEDIN_INSIGHTS_ENABLED', false),
            'partner_id' => env('LINKEDIN_PARTNER_ID'),
        ],
        'zoom' => [
            'enabled' => env('ZOOM_ENABLED', false),
            'api_key' => env('ZOOM_API_KEY'),
            'api_secret' => env('ZOOM_API_SECRET'),
        ],
        'teams' => [
            'enabled' => env('TEAMS_ENABLED', false),
            'client_id' => env('TEAMS_CLIENT_ID'),
            'client_secret' => env('TEAMS_CLIENT_SECRET'),
        ],
    ],
    
    'maintenance' => [
        'enabled' => env('MAINTENANCE_MODE', false),
        'message' => env('MAINTENANCE_MESSAGE', 'ZenithaLMS is currently under maintenance. Please try again later.'),
        'allowed_ips' => explode(',', env('MAINTENANCE_ALLOWED_IPS', '')),
        'retry_after' => env('MAINTENANCE_RETRY_AFTER', 300), // seconds
    ],
    
    'performance' => [
        'enable_query_cache' => env('ENABLE_QUERY_CACHE', true),
        'enable_page_cache' => env('ENABLE_PAGE_CACHE', false),
        'enable_asset_cache' => env('ENABLE_ASSET_CACHE', true),
        'cache_headers' => env('CACHE_HEADERS_ENABLED', true),
        'compress_output' => env('COMPRESS_OUTPUT', true),
    ],
    
    'localization' => [
        'default_locale' => env('APP_LOCALE', 'en'),
        'supported_locales' => explode(',', env('SUPPORTED_LOCALES', 'en,es,fr,de,ar')),
        'auto_detect' => env('AUTO_DETECT_LOCALE', true),
        'rtl_locales' => explode(',', env('RTL_LOCALES', 'ar,he')),
    ],
    
    'seo' => [
        'default_title' => env('SEO_DEFAULT_TITLE', 'ZenithaLMS - Advanced Learning Management System'),
        'default_description' => env('SEO_DEFAULT_DESCRIPTION', 'Comprehensive AI-powered learning management system for online education'),
        'default_keywords' => env('SEO_DEFAULT_KEYWORDS', 'LMS, online learning, education, courses, AI'),
        'sitemap_enabled' => env('SITEMAP_ENABLED', true),
        'robots_enabled' => env('ROBOTS_ENABLED', true),
    ],
    
    'backup' => [
        'enabled' => env('BACKUP_ENABLED', true),
        'schedule' => env('BACKUP_SCHEDULE', '0 2 * * *'), // Daily at 2 AM
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
        'include_files' => env('BACKUP_INCLUDE_FILES', true),
        'compress' => env('BACKUP_COMPRESS', true),
        'storage_disk' => env('BACKUP_STORAGE_DISK', 'local'),
    ],
];
