<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Media Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for media handling including folder paths, file sizes,
    | and allowed extensions for different media types.
    |
    */

    'folders' => [
        'courses.thumbnail' => 'courses/thumbnails',
        'courses.video' => 'courses/videos',
        'courses.materials' => 'courses/materials',
        'ebooks.thumbnail' => 'ebooks/thumbnails',
        'ebooks.file' => 'ebooks/files',
        'users.avatar' => 'avatars',
        'blog.image' => 'blog/images',
        'certificates.qr_code' => 'certificates/qr-codes',
    ],

    'validation' => [
        'courses.thumbnail' => [
            'max_size_kb' => 5120, // 5MB
            'allowed_extensions' => ['jpeg', 'jpg', 'png', 'webp'],
            'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        ],
        'courses.video' => [
            'max_size_kb' => 102400, // 100MB
            'allowed_extensions' => ['mp4', 'avi', 'mov', 'wmv'],
            'allowed_mimes' => ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-ms-wmv'],
        ],
        'courses.materials' => [
            'max_size_kb' => 20480, // 20MB
            'allowed_extensions' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt'],
            'allowed_mimes' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'text/plain'],
        ],
        'ebooks.thumbnail' => [
            'max_size_kb' => 5120, // 5MB
            'allowed_extensions' => ['jpeg', 'jpg', 'png', 'webp'],
            'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        ],
        'ebooks.file' => [
            'max_size_kb' => 20480, // 20MB
            'allowed_extensions' => ['pdf', 'epub'],
            'allowed_mimes' => ['application/pdf', 'application/epub+zip'],
        ],
        'users.avatar' => [
            'max_size_kb' => 2048, // 2MB
            'allowed_extensions' => ['jpeg', 'jpg', 'png', 'webp'],
            'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        ],
        'blog.image' => [
            'max_size_kb' => 5120, // 5MB
            'allowed_extensions' => ['jpeg', 'jpg', 'png', 'webp'],
            'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        ],
        'certificates.qr_code' => [
            'max_size_kb' => 1024, // 1MB
            'allowed_extensions' => ['png', 'svg'],
            'allowed_mimes' => ['image/png', 'image/svg+xml'],
        ],
    ],

    'limits' => [
        'images' => [
            'max_size' => 5 * 1024, // 5MB in KB
            'allowed' => ['jpeg', 'jpg', 'png', 'webp'],
        ],
        'avatars' => [
            'max_size' => 2 * 1024, // 2MB in KB
            'allowed' => ['jpeg', 'jpg', 'png', 'webp'],
        ],
        'ebooks' => [
            'max_size' => 20 * 1024, // 20MB in KB
            'allowed' => ['pdf', 'epub'],
        ],
        'videos' => [
            'max_size' => 100 * 1024, // 100MB in KB
            'allowed' => ['mp4', 'avi', 'mov', 'wmv'],
        ],
    ],

    'fallbacks' => [
        'course_thumbnail' => '/images/course-placeholder.png',
        'avatar' => '/images/default-avatar.png',
        'ebook_thumbnail' => '/images/course-placeholder.png',
    ],

    'disks' => [
        'public' => 'public',
        'private' => 'local',
    ],
];
