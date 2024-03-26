<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gzip configuration file
    |--------------------------------------------------------------------------
    |
    | Used to enable or disable gzip
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
     | This setting determines if debugbar is enabled or not
     */

    'debug' => env('APP_DEBUG', false),

];
