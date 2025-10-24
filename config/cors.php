<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS Configuration
    |--------------------------------------------------------------------------
    | Konfigurasi ini mengizinkan frontend (Vue/React) yang berjalan di
    | http://localhost:8081 atau http://localhost:5173 mengakses API Laravel.
    |
    | Catatan:
    | - Konfigurasi ini diasumsikan auth-nya memakai Bearer token (Authorization: Bearer ...)
    |   sehingga supports_credentials = false.
    | - Jika nanti berpindah ke Sanctum berbasis cookie, lihat contoh di bagian bawah file ini.
    */

    // Batasi jalur CORS hanya untuk endpoint API
    'paths' => ['api/*'],

    // Izinkan semua method HTTP (GET, POST, PUT, PATCH, DELETE, OPTIONS)
    'allowed_methods' => ['*'],

    // Origin yang diizinkan (tambahkan sesuai port dev kamu)
    'allowed_origins' => [
        'http://localhost:8081',   // Vue CLI/dev server
        'http://127.0.0.1:8081',
        'http://localhost:5173',   // Vite default
        'http://127.0.0.1:5173',
    ],

    // Pola origin (biasanya tidak perlu)
    'allowed_origins_patterns' => [],

    // Header yang diizinkan dari client
    'allowed_headers' => ['*'],

    // Header yang diekspos ke client (biasanya kosong)
    'exposed_headers' => [],

    // Cache preflight (detik). 0 = tidak di-cache
    'max_age' => 0,

    // Karena pakai Bearer token (bukan cookie), ini tetap false
    'supports_credentials' => false,
];

/*
|--------------------------------------------------------------------------
| Jika nanti pakai Sanctum berbasis cookie (SPA)
|--------------------------------------------------------------------------
| Ganti konfigurasi di atas menjadi seperti ini:
|
| 'paths' => ['api/*', 'sanctum/csrf-cookie'],
| 'allowed_origins' => [
|     'http://localhost:8081',
|     'http://127.0.0.1:8081',
|     'http://localhost:5173',
|     'http://127.0.0.1:5173',
| ],
| 'supports_credentials' => true,
|
| Lalu jalankan:
| php artisan config:clear
| php artisan cache:clear
*/
