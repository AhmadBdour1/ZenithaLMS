<?php return array (
  'broadcasting' => 
  array (
    'default' => 'log',
    'connections' => 
    array (
      'reverb' => 
      array (
        'driver' => 'reverb',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'host' => NULL,
          'port' => 443,
          'scheme' => 'https',
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'pusher' => 
      array (
        'driver' => 'pusher',
        'key' => '',
        'secret' => '',
        'app_id' => '',
        'options' => 
        array (
          'cluster' => 'mt1',
          'host' => 'api-mt1.pusher.com',
          'port' => '443',
          'scheme' => 'https',
          'encrypted' => true,
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'ably' => 
      array (
        'driver' => 'ably',
        'key' => NULL,
      ),
      'log' => 
      array (
        'driver' => 'log',
      ),
      'null' => 
      array (
        'driver' => 'null',
      ),
    ),
  ),
  'concurrency' => 
  array (
    'default' => 'process',
  ),
  'cors' => 
  array (
    'paths' => 
    array (
      0 => 'api/*',
      1 => 'sanctum/csrf-cookie',
    ),
    'allowed_methods' => 
    array (
      0 => '*',
    ),
    'allowed_origins' => 
    array (
      0 => '*',
    ),
    'allowed_origins_patterns' => 
    array (
    ),
    'allowed_headers' => 
    array (
      0 => '*',
    ),
    'exposed_headers' => 
    array (
    ),
    'max_age' => 0,
    'supports_credentials' => false,
  ),
  'hashing' => 
  array (
    'driver' => 'bcrypt',
    'bcrypt' => 
    array (
      'rounds' => '12',
      'verify' => true,
      'limit' => NULL,
    ),
    'argon' => 
    array (
      'memory' => 65536,
      'threads' => 1,
      'time' => 4,
      'verify' => true,
    ),
    'rehash_on_login' => true,
  ),
  'view' => 
  array (
    'paths' => 
    array (
      0 => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\resources\\views',
    ),
    'compiled' => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\storage\\framework\\views',
  ),
  'app' => 
  array (
    'name' => 'ZenithaLMS',
    'env' => 'local',
    'debug' => true,
    'url' => 'http://127.0.0.1:8080',
    'frontend_url' => 'http://localhost:3000',
    'asset_url' => NULL,
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'cipher' => 'AES-256-CBC',
    'key' => 'base64:d481E/5CpeOPrjdXeGqhvNWsYRj86sLJlDbCi8hMch0=',
    'previous_keys' => 
    array (
    ),
    'maintenance' => 
    array (
      'driver' => 'file',
      'store' => 'database',
    ),
    'providers' => 
    array (
      0 => 'Illuminate\\Auth\\AuthServiceProvider',
      1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
      2 => 'Illuminate\\Bus\\BusServiceProvider',
      3 => 'Illuminate\\Cache\\CacheServiceProvider',
      4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
      5 => 'Illuminate\\Cookie\\CookieServiceProvider',
      6 => 'Illuminate\\Database\\DatabaseServiceProvider',
      7 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
      8 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
      9 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
      10 => 'Illuminate\\Hashing\\HashServiceProvider',
      11 => 'Illuminate\\Mail\\MailServiceProvider',
      12 => 'Illuminate\\Notifications\\NotificationServiceProvider',
      13 => 'Illuminate\\Pagination\\PaginationServiceProvider',
      14 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
      15 => 'Illuminate\\Queue\\QueueServiceProvider',
      16 => 'Illuminate\\Redis\\RedisServiceProvider',
      17 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
      18 => 'Illuminate\\Session\\SessionServiceProvider',
      19 => 'Illuminate\\Translation\\TranslationServiceProvider',
      20 => 'Illuminate\\Validation\\ValidationServiceProvider',
      21 => 'Illuminate\\View\\ViewServiceProvider',
      22 => 'Laravel\\Sanctum\\SanctumServiceProvider',
      23 => 'App\\Providers\\AppServiceProvider',
      24 => 'App\\Providers\\AuthServiceProvider',
      25 => 'App\\Providers\\BroadcastServiceProvider',
      26 => 'App\\Providers\\EventServiceProvider',
      27 => 'App\\Providers\\RouteServiceProvider',
      28 => 'App\\Providers\\QueryOptimizationServiceProvider',
      29 => 'App\\Providers\\SQLiteBootstrapServiceProvider',
      30 => 'App\\Providers\\AppServiceProvider',
      31 => 'App\\Providers\\TenancyServiceProvider',
      32 => 'App\\Providers\\ZenithaLmsServiceProvider',
      33 => 'App\\Modules\\ModuleServiceProvider',
    ),
    'aliases' => 
    array (
      'App' => 'Illuminate\\Support\\Facades\\App',
      'Arr' => 'Illuminate\\Support\\Arr',
      'Artisan' => 'Illuminate\\Support\\Facades\\Artisan',
      'Auth' => 'Illuminate\\Support\\Facades\\Auth',
      'Benchmark' => 'Illuminate\\Support\\Benchmark',
      'Blade' => 'Illuminate\\Support\\Facades\\Blade',
      'Broadcast' => 'Illuminate\\Support\\Facades\\Broadcast',
      'Bus' => 'Illuminate\\Support\\Facades\\Bus',
      'Cache' => 'Illuminate\\Support\\Facades\\Cache',
      'Concurrency' => 'Illuminate\\Support\\Facades\\Concurrency',
      'Config' => 'Illuminate\\Support\\Facades\\Config',
      'Context' => 'Illuminate\\Support\\Facades\\Context',
      'Cookie' => 'Illuminate\\Support\\Facades\\Cookie',
      'Crypt' => 'Illuminate\\Support\\Facades\\Crypt',
      'Date' => 'Illuminate\\Support\\Facades\\Date',
      'DB' => 'Illuminate\\Support\\Facades\\DB',
      'Eloquent' => 'Illuminate\\Database\\Eloquent\\Model',
      'Event' => 'Illuminate\\Support\\Facades\\Event',
      'File' => 'Illuminate\\Support\\Facades\\File',
      'Gate' => 'Illuminate\\Support\\Facades\\Gate',
      'Hash' => 'Illuminate\\Support\\Facades\\Hash',
      'Http' => 'Illuminate\\Support\\Facades\\Http',
      'Js' => 'Illuminate\\Support\\Js',
      'Lang' => 'Illuminate\\Support\\Facades\\Lang',
      'Log' => 'Illuminate\\Support\\Facades\\Log',
      'Mail' => 'Illuminate\\Support\\Facades\\Mail',
      'Notification' => 'Illuminate\\Support\\Facades\\Notification',
      'Number' => 'Illuminate\\Support\\Number',
      'Password' => 'Illuminate\\Support\\Facades\\Password',
      'Process' => 'Illuminate\\Support\\Facades\\Process',
      'Queue' => 'Illuminate\\Support\\Facades\\Queue',
      'RateLimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
      'Redirect' => 'Illuminate\\Support\\Facades\\Redirect',
      'Request' => 'Illuminate\\Support\\Facades\\Request',
      'Response' => 'Illuminate\\Support\\Facades\\Response',
      'Route' => 'Illuminate\\Support\\Facades\\Route',
      'Schedule' => 'Illuminate\\Support\\Facades\\Schedule',
      'Schema' => 'Illuminate\\Support\\Facades\\Schema',
      'Session' => 'Illuminate\\Support\\Facades\\Session',
      'Storage' => 'Illuminate\\Support\\Facades\\Storage',
      'Str' => 'Illuminate\\Support\\Str',
      'Uri' => 'Illuminate\\Support\\Uri',
      'URL' => 'Illuminate\\Support\\Facades\\URL',
      'Validator' => 'Illuminate\\Support\\Facades\\Validator',
      'View' => 'Illuminate\\Support\\Facades\\View',
      'Vite' => 'Illuminate\\Support\\Facades\\Vite',
    ),
    'product_mode' => 'standard',
  ),
  'auth' => 
  array (
    'defaults' => 
    array (
      'guard' => 'web',
      'passwords' => 'users',
    ),
    'guards' => 
    array (
      'web' => 
      array (
        'driver' => 'session',
        'provider' => 'users',
      ),
      'sanctum' => 
      array (
        'driver' => 'sanctum',
        'provider' => 'users',
        'hash' => false,
      ),
    ),
    'providers' => 
    array (
      'users' => 
      array (
        'driver' => 'eloquent',
        'model' => 'App\\Models\\User',
      ),
    ),
    'passwords' => 
    array (
      'users' => 
      array (
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
      ),
    ),
    'password_timeout' => 10800,
  ),
  'cache' => 
  array (
    'default' => 'file',
    'stores' => 
    array (
      'array' => 
      array (
        'driver' => 'array',
        'serialize' => false,
      ),
      'session' => 
      array (
        'driver' => 'session',
        'key' => '_cache',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'cache',
        'lock_connection' => NULL,
        'lock_table' => NULL,
      ),
      'file' => 
      array (
        'driver' => 'file',
        'path' => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\storage\\framework/cache/data',
        'lock_path' => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\storage\\framework/cache/data',
      ),
      'memcached' => 
      array (
        'driver' => 'memcached',
        'persistent_id' => NULL,
        'sasl' => 
        array (
          0 => NULL,
          1 => NULL,
        ),
        'options' => 
        array (
        ),
        'servers' => 
        array (
          0 => 
          array (
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 100,
          ),
        ),
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
      ),
      'dynamodb' => 
      array (
        'driver' => 'dynamodb',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'table' => 'cache',
        'endpoint' => NULL,
      ),
      'octane' => 
      array (
        'driver' => 'octane',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'stores' => 
        array (
          0 => 'database',
          1 => 'array',
        ),
      ),
    ),
    'prefix' => 'zenithalms-cache-',
  ),
  'database' => 
  array (
    'default' => 'sqlite',
    'connections' => 
    array (
      'sqlite' => 
      array (
        'driver' => 'sqlite',
        'url' => NULL,
        'database' => 'database/database.sqlite',
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => NULL,
        'journal_mode' => NULL,
        'synchronous' => NULL,
        'transaction_mode' => 'DEFERRED',
      ),
      'mysql' => 
      array (
        'driver' => 'mysql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'database/database.sqlite',
        'username' => 'root',
        'password' => '',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'mariadb' => 
      array (
        'driver' => 'mariadb',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'database/database.sqlite',
        'username' => 'root',
        'password' => '',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'pgsql' => 
      array (
        'driver' => 'pgsql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '5432',
        'database' => 'database/database.sqlite',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
      ),
      'sqlsrv' => 
      array (
        'driver' => 'sqlsrv',
        'url' => NULL,
        'host' => 'localhost',
        'port' => '1433',
        'database' => 'database/database.sqlite',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
      ),
      'central' => 
      array (
        'driver' => 'sqlite',
        'url' => NULL,
        'database' => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\database\\central.sqlite',
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => NULL,
        'journal_mode' => NULL,
        'synchronous' => NULL,
        'transaction_mode' => 'DEFERRED',
      ),
    ),
    'migrations' => 
    array (
      'table' => 'migrations',
      'update_date_on_publish' => true,
    ),
    'redis' => 
    array (
      'client' => 'phpredis',
      'options' => 
      array (
        'cluster' => 'redis',
        'prefix' => 'zenithalms-database-',
        'persistent' => false,
      ),
      'default' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '0',
        'max_retries' => 3,
        'backoff_algorithm' => 'decorrelated_jitter',
        'backoff_base' => 100,
        'backoff_cap' => 1000,
      ),
      'cache' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '1',
        'max_retries' => 3,
        'backoff_algorithm' => 'decorrelated_jitter',
        'backoff_base' => 100,
        'backoff_cap' => 1000,
      ),
    ),
  ),
  'filesystems' => 
  array (
    'default' => 'local',
    'disks' => 
    array (
      'local' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\storage\\app/private',
        'serve' => true,
        'throw' => false,
        'report' => false,
      ),
      'public' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\storage\\app/public',
        'url' => 'http://127.0.0.1:8080/storage',
        'visibility' => 'public',
        'throw' => false,
        'report' => false,
      ),
      's3' => 
      array (
        'driver' => 's3',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'bucket' => '',
        'url' => NULL,
        'endpoint' => NULL,
        'use_path_style_endpoint' => false,
        'throw' => false,
        'report' => false,
      ),
      'private' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\storage\\app/private',
        'serve' => false,
        'throw' => false,
        'report' => false,
      ),
    ),
    'links' => 
    array (
      'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\public\\storage' => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\storage\\app/public',
    ),
  ),
  'logging' => 
  array (
    'default' => 'stack',
    'deprecations' => 
    array (
      'channel' => NULL,
      'trace' => false,
    ),
    'channels' => 
    array (
      'stack' => 
      array (
        'driver' => 'stack',
        'channels' => 
        array (
          0 => 'single',
        ),
        'ignore_exceptions' => false,
      ),
      'single' => 
      array (
        'driver' => 'single',
        'path' => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\storage\\logs/laravel.log',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'daily' => 
      array (
        'driver' => 'daily',
        'path' => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\storage\\logs/laravel.log',
        'level' => 'debug',
        'days' => 14,
        'replace_placeholders' => true,
      ),
      'slack' => 
      array (
        'driver' => 'slack',
        'url' => NULL,
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'papertrail' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\SyslogUdpHandler',
        'handler_with' => 
        array (
          'host' => NULL,
          'port' => NULL,
          'connectionString' => 'tls://:',
        ),
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'stderr' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\StreamHandler',
        'handler_with' => 
        array (
          'stream' => 'php://stderr',
        ),
        'formatter' => NULL,
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'syslog' => 
      array (
        'driver' => 'syslog',
        'level' => 'debug',
        'facility' => 8,
        'replace_placeholders' => true,
      ),
      'errorlog' => 
      array (
        'driver' => 'errorlog',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'null' => 
      array (
        'driver' => 'monolog',
        'handler' => 'Monolog\\Handler\\NullHandler',
      ),
      'emergency' => 
      array (
        'path' => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\storage\\logs/laravel.log',
      ),
    ),
  ),
  'mail' => 
  array (
    'default' => 'log',
    'mailers' => 
    array (
      'smtp' => 
      array (
        'transport' => 'smtp',
        'scheme' => NULL,
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '2525',
        'username' => NULL,
        'password' => NULL,
        'timeout' => NULL,
        'local_domain' => '127.0.0.1',
      ),
      'ses' => 
      array (
        'transport' => 'ses',
      ),
      'postmark' => 
      array (
        'transport' => 'postmark',
      ),
      'resend' => 
      array (
        'transport' => 'resend',
      ),
      'sendmail' => 
      array (
        'transport' => 'sendmail',
        'path' => '/usr/sbin/sendmail -bs -i',
      ),
      'log' => 
      array (
        'transport' => 'log',
        'channel' => NULL,
      ),
      'array' => 
      array (
        'transport' => 'array',
      ),
      'failover' => 
      array (
        'transport' => 'failover',
        'mailers' => 
        array (
          0 => 'smtp',
          1 => 'log',
        ),
        'retry_after' => 60,
      ),
      'roundrobin' => 
      array (
        'transport' => 'roundrobin',
        'mailers' => 
        array (
          0 => 'ses',
          1 => 'postmark',
        ),
        'retry_after' => 60,
      ),
    ),
    'from' => 
    array (
      'address' => 'hello@example.com',
      'name' => 'ZenithaLMS',
    ),
    'markdown' => 
    array (
      'theme' => 'default',
      'paths' => 
      array (
        0 => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\resources\\views/vendor/mail',
      ),
      'extensions' => 
      array (
      ),
    ),
  ),
  'media' => 
  array (
    'folders' => 
    array (
      'courses.thumbnail' => 'courses/thumbnails',
      'courses.video' => 'courses/videos',
      'courses.materials' => 'courses/materials',
      'ebooks.thumbnail' => 'ebooks/thumbnails',
      'ebooks.file' => 'ebooks/files',
      'users.avatar' => 'avatars',
      'blog.image' => 'blog/images',
      'certificates.qr_code' => 'certificates/qr-codes',
    ),
    'validation' => 
    array (
      'courses.thumbnail' => 
      array (
        'max_size_kb' => 5120,
        'allowed_extensions' => 
        array (
          0 => 'jpeg',
          1 => 'jpg',
          2 => 'png',
          3 => 'webp',
        ),
        'allowed_mimes' => 
        array (
          0 => 'image/jpeg',
          1 => 'image/png',
          2 => 'image/webp',
        ),
      ),
      'courses.video' => 
      array (
        'max_size_kb' => 102400,
        'allowed_extensions' => 
        array (
          0 => 'mp4',
          1 => 'avi',
          2 => 'mov',
          3 => 'wmv',
        ),
        'allowed_mimes' => 
        array (
          0 => 'video/mp4',
          1 => 'video/avi',
          2 => 'video/quicktime',
          3 => 'video/x-ms-wmv',
        ),
      ),
      'courses.materials' => 
      array (
        'max_size_kb' => 20480,
        'allowed_extensions' => 
        array (
          0 => 'pdf',
          1 => 'doc',
          2 => 'docx',
          3 => 'ppt',
          4 => 'pptx',
          5 => 'txt',
        ),
        'allowed_mimes' => 
        array (
          0 => 'application/pdf',
          1 => 'application/msword',
          2 => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          3 => 'application/vnd.ms-powerpoint',
          4 => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
          5 => 'text/plain',
        ),
      ),
      'ebooks.thumbnail' => 
      array (
        'max_size_kb' => 5120,
        'allowed_extensions' => 
        array (
          0 => 'jpeg',
          1 => 'jpg',
          2 => 'png',
          3 => 'webp',
        ),
        'allowed_mimes' => 
        array (
          0 => 'image/jpeg',
          1 => 'image/png',
          2 => 'image/webp',
        ),
      ),
      'ebooks.file' => 
      array (
        'max_size_kb' => 20480,
        'allowed_extensions' => 
        array (
          0 => 'pdf',
          1 => 'epub',
        ),
        'allowed_mimes' => 
        array (
          0 => 'application/pdf',
          1 => 'application/epub+zip',
        ),
      ),
      'users.avatar' => 
      array (
        'max_size_kb' => 2048,
        'allowed_extensions' => 
        array (
          0 => 'jpeg',
          1 => 'jpg',
          2 => 'png',
          3 => 'webp',
        ),
        'allowed_mimes' => 
        array (
          0 => 'image/jpeg',
          1 => 'image/png',
          2 => 'image/webp',
        ),
      ),
      'blog.image' => 
      array (
        'max_size_kb' => 5120,
        'allowed_extensions' => 
        array (
          0 => 'jpeg',
          1 => 'jpg',
          2 => 'png',
          3 => 'webp',
        ),
        'allowed_mimes' => 
        array (
          0 => 'image/jpeg',
          1 => 'image/png',
          2 => 'image/webp',
        ),
      ),
      'certificates.qr_code' => 
      array (
        'max_size_kb' => 1024,
        'allowed_extensions' => 
        array (
          0 => 'png',
          1 => 'svg',
        ),
        'allowed_mimes' => 
        array (
          0 => 'image/png',
          1 => 'image/svg+xml',
        ),
      ),
    ),
    'limits' => 
    array (
      'images' => 
      array (
        'max_size' => 5120,
        'allowed' => 
        array (
          0 => 'jpeg',
          1 => 'jpg',
          2 => 'png',
          3 => 'webp',
        ),
      ),
      'avatars' => 
      array (
        'max_size' => 2048,
        'allowed' => 
        array (
          0 => 'jpeg',
          1 => 'jpg',
          2 => 'png',
          3 => 'webp',
        ),
      ),
      'ebooks' => 
      array (
        'max_size' => 20480,
        'allowed' => 
        array (
          0 => 'pdf',
          1 => 'epub',
        ),
      ),
      'videos' => 
      array (
        'max_size' => 102400,
        'allowed' => 
        array (
          0 => 'mp4',
          1 => 'avi',
          2 => 'mov',
          3 => 'wmv',
        ),
      ),
    ),
    'fallbacks' => 
    array (
      'course_thumbnail' => '/images/course-placeholder.png',
      'avatar' => '/images/default-avatar.png',
      'ebook_thumbnail' => '/images/course-placeholder.png',
    ),
    'disks' => 
    array (
      'public' => 'public',
      'private' => 'local',
    ),
  ),
  'queue' => 
  array (
    'default' => 'sync',
    'connections' => 
    array (
      'sync' => 
      array (
        'driver' => 'sync',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
      ),
      'beanstalkd' => 
      array (
        'driver' => 'beanstalkd',
        'host' => 'localhost',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 0,
        'after_commit' => false,
      ),
      'sqs' => 
      array (
        'driver' => 'sqs',
        'key' => '',
        'secret' => '',
        'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
        'queue' => 'default',
        'suffix' => NULL,
        'region' => 'us-east-1',
        'after_commit' => false,
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => NULL,
        'after_commit' => false,
      ),
      'deferred' => 
      array (
        'driver' => 'deferred',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'connections' => 
        array (
          0 => 'database',
          1 => 'deferred',
        ),
      ),
      'background' => 
      array (
        'driver' => 'background',
      ),
    ),
    'batching' => 
    array (
      'database' => 'sqlite',
      'table' => 'job_batches',
    ),
    'failed' => 
    array (
      'driver' => 'database-uuids',
      'database' => 'sqlite',
      'table' => 'failed_jobs',
    ),
  ),
  'sanctum' => 
  array (
    'stateful' => 
    array (
      0 => 'localhost',
      1 => 'localhost:3000',
      2 => '127.0.0.1',
      3 => '127.0.0.1:8000',
      4 => '::1',
      5 => '127.0.0.1:8080',
    ),
    'guard' => 
    array (
      0 => 'web',
    ),
    'expiration' => 43200,
    'token_prefix' => '',
    'middleware' => 
    array (
      'authenticate_session' => 'Laravel\\Sanctum\\Http\\Middleware\\AuthenticateSession',
      'encrypt_cookies' => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
      'validate_csrf_token' => 'Illuminate\\Foundation\\Http\\Middleware\\ValidateCsrfToken',
    ),
  ),
  'services' => 
  array (
    'postmark' => 
    array (
      'key' => NULL,
    ),
    'resend' => 
    array (
      'key' => NULL,
    ),
    'ses' => 
    array (
      'key' => '',
      'secret' => '',
      'region' => 'us-east-1',
    ),
    'slack' => 
    array (
      'notifications' => 
      array (
        'bot_user_oauth_token' => NULL,
        'channel' => NULL,
      ),
    ),
    'stripe' => 
    array (
      'key' => '',
      'secret' => '',
      'webhook_secret' => '',
    ),
    'paypal' => 
    array (
      'client_id' => '',
      'client_secret' => '',
      'mode' => 'sandbox',
    ),
  ),
  'session' => 
  array (
    'driver' => 'file',
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\storage\\framework/sessions',
    'connection' => NULL,
    'table' => 'sessions',
    'store' => NULL,
    'lottery' => 
    array (
      0 => 2,
      1 => 100,
    ),
    'cookie' => 'zenithalms-session',
    'path' => '/',
    'domain' => '',
    'secure' => NULL,
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
  ),
  'tenancy' => 
  array (
    'tenant_model' => 'App\\Models\\Central\\Tenant',
    'id_generator' => 'Stancl\\Tenancy\\UUIDGenerator',
    'domain_model' => 'Stancl\\Tenancy\\Database\\Models\\Domain',
    'central_domains' => 
    array (
      0 => '127.0.0.1',
      1 => 'localhost',
      2 => 'code-review-lab.preview.emergentagent.com',
      3 => 'zenithalms.test',
    ),
    'bootstrappers' => 
    array (
      0 => 'Stancl\\Tenancy\\Bootstrappers\\DatabaseTenancyBootstrapper',
      1 => 'Stancl\\Tenancy\\Bootstrappers\\CacheTenancyBootstrapper',
      2 => 'Stancl\\Tenancy\\Bootstrappers\\FilesystemTenancyBootstrapper',
      3 => 'Stancl\\Tenancy\\Bootstrappers\\QueueTenancyBootstrapper',
    ),
    'database' => 
    array (
      'central_connection' => 'sqlite',
      'template_tenant_connection' => NULL,
      'prefix' => 'tenant',
      'suffix' => '',
      'managers' => 
      array (
        'sqlite' => 'Stancl\\Tenancy\\TenantDatabaseManagers\\SQLiteDatabaseManager',
        'mysql' => 'Stancl\\Tenancy\\TenantDatabaseManagers\\MySQLDatabaseManager',
        'pgsql' => 'Stancl\\Tenancy\\TenantDatabaseManagers\\PostgreSQLDatabaseManager',
      ),
    ),
    'cache' => 
    array (
      'tag_base' => 'tenant',
    ),
    'filesystem' => 
    array (
      'suffix_base' => 'tenant',
      'disks' => 
      array (
        0 => 'local',
        1 => 'public',
      ),
      'root_override' => 
      array (
        'local' => '%storage_path%/app/',
        'public' => '%storage_path%/app/public/',
      ),
      'suffix_storage_path' => true,
      'asset_helper_tenancy' => true,
    ),
    'redis' => 
    array (
      'prefix_base' => 'tenant',
      'prefixed_connections' => 
      array (
      ),
    ),
    'features' => 
    array (
    ),
    'routes' => true,
    'migration_parameters' => 
    array (
      '--force' => true,
      '--path' => 
      array (
        0 => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\database\\migrations/tenant',
      ),
      '--realpath' => true,
    ),
    'seeder_parameters' => 
    array (
      '--class' => 'DatabaseSeeder',
    ),
  ),
  'zenithalms' => 
  array (
    'ai' => 
    array (
      'api_key' => '',
      'base_url' => 'https://api.openai.com/v1',
      'model' => 'gpt-3.5-turbo',
      'max_tokens' => 2000,
      'temperature' => 0.7,
      'timeout' => 30,
    ),
    'payment' => 
    array (
      'default_currency' => 'USD',
      'webhook_secret' => NULL,
      'auto_refund_days' => 7,
      'minimum_amount' => 1.0,
      'gateways' => 
      array (
        'stripe' => 
        array (
          'enabled' => true,
          'secret_key' => '',
          'publishable_key' => '',
          'webhook_secret' => '',
        ),
        'paypal' => 
        array (
          'enabled' => true,
          'client_id' => '',
          'client_secret' => NULL,
          'sandbox' => true,
          'webhook_id' => NULL,
        ),
        'wallet' => 
        array (
          'enabled' => true,
          'minimum_balance' => 0,
          'maximum_balance' => 10000,
        ),
      ),
    ),
    'theme' => 
    array (
      'default_theme' => 'zenithalms-default',
      'allow_user_themes' => true,
      'themes_path' => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\resources\\views/themes',
      'custom_css_path' => 'C:\\Users\\ahmad\\Herd\\ZenithaLMS-main\\public\\css/themes',
    ),
    'notifications' => 
    array (
      'email' => 
      array (
        'enabled' => true,
        'from_address' => 'hello@example.com',
        'from_name' => 'ZenithaLMS',
      ),
      'push' => 
      array (
        'enabled' => true,
        'fcm_key' => NULL,
        'fcm_sender_id' => NULL,
      ),
      'sms' => 
      array (
        'enabled' => false,
        'provider' => 'twilio',
        'twilio_sid' => NULL,
        'twilio_token' => NULL,
        'twilio_from' => NULL,
      ),
    ),
    'storage' => 
    array (
      'default' => 'local',
      'cloud' => false,
      'max_file_size' => 10240,
      'allowed_extensions' => 
      array (
        0 => 'jpg',
        1 => 'jpeg',
        2 => 'png',
        3 => 'gif',
        4 => 'pdf',
        5 => 'doc',
        6 => 'docx',
        7 => 'mp4',
        8 => 'mp3',
      ),
    ),
    'cache' => 
    array (
      'default_ttl' => 3600,
      'user_cache_ttl' => 1800,
      'course_cache_ttl' => 7200,
      'analytics_cache_ttl' => 300,
    ),
    'security' => 
    array (
      'password_min_length' => 8,
      'session_timeout' => 120,
      'max_login_attempts' => 5,
      'lockout_duration' => 15,
      'require_email_verification' => true,
      'two_factor_enabled' => false,
    ),
    'analytics' => 
    array (
      'enabled' => true,
      'tracking_code' => NULL,
      'retention_days' => 365,
      'real_time_enabled' => true,
    ),
    'features' => 
    array (
      'ai_recommendations' => true,
      'adaptive_learning' => true,
      'virtual_classes' => true,
      'certificates' => true,
      'forums' => true,
      'blogs' => true,
      'ebooks' => true,
      'quizzes' => true,
      'assignments' => true,
      'gamification' => false,
      'social_learning' => true,
    ),
    'limits' => 
    array (
      'max_courses_per_user' => 50,
      'max_enrollments_per_user' => 100,
      'max_file_upload_size' => 100,
      'max_quiz_attempts' => 3,
      'max_forum_posts_per_day' => 10,
      'max_virtual_class_participants' => 100,
    ),
    'webhooks' => 
    array (
      'payment_completed' => NULL,
      'user_registered' => NULL,
      'course_enrolled' => NULL,
      'quiz_completed' => NULL,
      'certificate_issued' => NULL,
      'timeout' => 30,
      'retry_attempts' => 3,
    ),
    'integrations' => 
    array (
      'google_analytics' => 
      array (
        'enabled' => false,
        'tracking_id' => NULL,
      ),
      'facebook_pixel' => 
      array (
        'enabled' => false,
        'pixel_id' => NULL,
      ),
      'linkedin_insights' => 
      array (
        'enabled' => false,
        'partner_id' => NULL,
      ),
      'zoom' => 
      array (
        'enabled' => false,
        'api_key' => NULL,
        'api_secret' => NULL,
      ),
      'teams' => 
      array (
        'enabled' => false,
        'client_id' => NULL,
        'client_secret' => NULL,
      ),
    ),
    'maintenance' => 
    array (
      'enabled' => false,
      'message' => 'ZenithaLMS is currently under maintenance. Please try again later.',
      'allowed_ips' => 
      array (
        0 => '',
      ),
      'retry_after' => 300,
    ),
    'performance' => 
    array (
      'enable_query_cache' => true,
      'enable_page_cache' => false,
      'enable_asset_cache' => true,
      'cache_headers' => true,
      'compress_output' => true,
    ),
    'localization' => 
    array (
      'default_locale' => 'en',
      'supported_locales' => 
      array (
        0 => 'en',
        1 => 'es',
        2 => 'fr',
        3 => 'de',
        4 => 'ar',
      ),
      'auto_detect' => true,
      'rtl_locales' => 
      array (
        0 => 'ar',
        1 => 'he',
      ),
    ),
    'seo' => 
    array (
      'default_title' => 'ZenithaLMS - Advanced Learning Management System',
      'default_description' => 'Comprehensive AI-powered learning management system for online education',
      'default_keywords' => 'LMS, online learning, education, courses, AI',
      'sitemap_enabled' => true,
      'robots_enabled' => true,
    ),
    'backup' => 
    array (
      'enabled' => true,
      'schedule' => '0 2 * * *',
      'retention_days' => 30,
      'include_files' => true,
      'compress' => true,
      'storage_disk' => 'local',
    ),
  ),
  'tinker' => 
  array (
    'commands' => 
    array (
    ),
    'alias' => 
    array (
    ),
    'dont_alias' => 
    array (
      0 => 'App\\Nova',
    ),
    'trust_project' => 'always',
  ),
);
