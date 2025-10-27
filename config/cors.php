<?php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:8081',
        'http://127.0.0.1:8081',
        'http://localhost:5173',
        'http://127.0.0.1:5173',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false, // ⬅️ Bearer = TIDAK pakai cookie
];
