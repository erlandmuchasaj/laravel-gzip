<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gzip configuration file
    |--------------------------------------------------------------------------
    |
    | Used to enable or disable gzip globally
    |
    */

    'enabled' => env('GZIP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Compression Level
    |--------------------------------------------------------------------------
    |
    | The compression level (1-9). Higher levels provide better compression
    | but use more CPU. Level 5-6 is recommended for production.
    | - 1: Fastest, least compression
    | - 5: Balanced (recommended)
    | - 9: Slowest, best compression
    |
    */
    'level' => env('GZIP_LEVEL', 5),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, compression is disabled to make debugging easier.
    |
    */
    'debug' => env('GZIP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Minimum Content Length
    |--------------------------------------------------------------------------
    |
    | Minimum response size in bytes before compression is applied.
    | Small responses don't benefit from compression.
    |
    */
    'minimum_content_length' => env('GZIP_MIN_LENGTH', 1024),

    /*
    |--------------------------------------------------------------------------
    | Minimum Compression Ratio
    |--------------------------------------------------------------------------
    |
    | Only use compression if it reduces size below this ratio.
    | For example, 0.90 means compression must achieve at least 10% reduction.
    |
    */
    'minimum_compression_ratio' => env('GZIP_MIN_RATIO', 0.90),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable logging of compression statistics.
    |
    */
    'log' => env('GZIP_LOG', true),

    /*
    |--------------------------------------------------------------------------
    | Skip Local Environment
    |--------------------------------------------------------------------------
    |
    | Skip compression in local development environment.
    |
    */
    'skip_local' => env('GZIP_SKIP_LOCAL', true),


    /*
    |--------------------------------------------------------------------------
    | Skip Testing Environment
    |--------------------------------------------------------------------------
    |
    | Skip compression when running unit tests.
    |
    */
    'skip_testing' => env('GZIP_SKIP_TESTING', true),

    /*
    |--------------------------------------------------------------------------
    | Set Cache-Control Header
    |--------------------------------------------------------------------------
    |
    | Whether to automatically set Cache-Control headers on compressed responses.
    |
    */
    'set_cache_control' => env('GZIP_SET_CACHE_CONTROL', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Max Age
    |--------------------------------------------------------------------------
    |
    | Maximum age for Cache-Control header in seconds (if enabled).
    | 31536000 = 1 year, 86400 = 1 day
    |
    */
    'cache_max_age' => env('GZIP_CACHE_MAX_AGE', 86400),

    /*
    |--------------------------------------------------------------------------
    | Auto Register Middleware
    |--------------------------------------------------------------------------
    |
    | Automatically register the middleware globally.
    | If false, you need to manually add it to your middleware stack.
    |
    */
    'auto_register' => env('GZIP_AUTO_REGISTER', false),

    /*
    |--------------------------------------------------------------------------
    | Excluded MIME Types
    |--------------------------------------------------------------------------
    | Content types that should not be compressed
    */

    'excluded_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/avif',
        'video/',
        'audio/',
        'application/zip',
        'application/x-gzip',
        'application/pdf',
        'application/octet-stream',
        'font/woff',
        'font/ttf',
        'font/otf',
    ],

    /*
    |--------------------------------------------------------------------------
    | Compressible MIME Types
    |--------------------------------------------------------------------------
    | Content types that should be compressed (extensible by user)
    */

    'compressible_mime_types' => [
        'text/html',
        'text/css',
        'text/plain',
        'text/xml',
        'text/javascript',
        'application/json',
        'application/javascript',
        'application/x-javascript',
        'application/xml',
        'application/rss+xml',
        'application/atom+xml',
        'application/xhtml+xml',
        'application/ld+json',
        'image/svg+xml',
        'font/woff2',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Paths
    |--------------------------------------------------------------------------
    |
    | URL patterns that should be excluded from compression.
    | Supports wildcards (*).
    |
    */
    'excluded_paths' => [
        'api/webhook/*',
        // Add paths that should not be compressed
    ],

    /*
    |--------------------------------------------------------------------------
    | Streaming Compression (future)
    |--------------------------------------------------------------------------
    | Enable streaming compression for large responses (not implemented yet)
    */

    //'streaming' => false,


];
