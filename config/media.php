<?php

return [

    'disk' => env('MEDIA_DISK', 'local'),

    'path_prefix' => 'media',

    'video' => [
        'extensions' => ['mp4'],
        'mime_types' => ['video/mp4'],
    ],

    'image' => [
        'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
    ],

];
