<?php

return [

    /*
    | Dirección HLS que reproducen los televisores cuando el vivo está activo.
    */
    'hls_url' => env('LIVE_HLS_URL', 'https://munichepica.cl/televisor/index.m3u8'),

    /*
    | Servidor RTMP que se configura en OBS. La clave de emisión es el nombre del camino.
    */
    'rtmp_url' => env('LIVE_RTMP_URL', 'rtmp://munichepica.cl:1935'),

    'publish_user' => env('MEDIAMTX_PUBLISH_USER', 'televisor'),

    'publish_password' => env('MEDIAMTX_PUBLISH_PASSWORD'),

    'stream_key' => env('LIVE_STREAM_KEY', 'televisor'),

];
