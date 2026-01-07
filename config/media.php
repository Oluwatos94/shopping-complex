<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media Upload Settings
    |--------------------------------------------------------------------------
    |
    | Configure the default settings for media uploads including file size
    | limits, image dimensions, and quality settings.
    |
    */

    'max_file_size' => env('MEDIA_MAX_FILE_SIZE', 5242880), // 5MB in bytes

    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/webp',
    ],

    'max_width' => env('MEDIA_MAX_WIDTH', 1920),

    'max_height' => env('MEDIA_MAX_HEIGHT', 1920),

    'quality' => env('MEDIA_QUALITY', 85),

    'png_compression' => env('MEDIA_PNG_COMPRESSION', 8), // 0-9 compression level

    'storage_disk' => env('MEDIA_STORAGE_DISK', 'public'),

    'storage_path' => env('MEDIA_STORAGE_PATH', 'products'),

];
