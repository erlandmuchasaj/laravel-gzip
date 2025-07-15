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
     | The level of compression.
     | Can be given as 0 for no compression up to 9 for maximum compression.
     | 5 is a perfect compromise between size and CPU
     */

    'level' => env('GZIP_LEVEL', 5),

    /*
     *
     | This setting determines if the debugger is enabled or not
     */

    'debug' => env('APP_DEBUG', false),

    /*
     | The minimum content length that we apply the gZip 
     */

    'minimum_content_length' => env('GZIP_MIN_LENGTH', 1024),

    /*
     | Minimum bytes before compression (avoid overhead)
     */

    'minimum_compression_ratio' => env('GZIP_MIN_RATIO', 0.95),

    'log' => env('GZIP_LOG', false),

    /*
    |--------------------------------------------------------------------------
    | Excluded MIME Types
    |--------------------------------------------------------------------------
    | Content types that should not be compressed
    */
    'excluded_mime_types' => [
        'image/',
        'video/',
        'audio/',
        'font/',
        'application/pdf',
        'application/zip',
        'application/gzip',
        'application/x-gzip',
        'application/x-bzip',
    ],


    'compressible_mime_types' => [
        'text/',
        'application/json',
        'application/javascript',
        'application/x-javascript',
        'application/xml',
        'application/rss+xml',
        'application/atom+xml',
        'image/svg+xml',
    ],

];
