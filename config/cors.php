<?php

return [

    /*
    | CORS: какие сайты могут обращаться к API из браузера.
    | Адрес клиента задаётся в .env: CLIENT_URL=http://127.0.0.1:5500
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter(array_map('trim', explode(',', env('CLIENT_URL', 'http://127.0.0.1:5500')))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Авторизация по Bearer-токену, cookie не нужны
    'supports_credentials' => false,

];
