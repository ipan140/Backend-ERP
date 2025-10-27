<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::get('/health', fn () => ['ok' => true])->name('health');

// 🔐 Login/logout via WEB (cookie session)
Route::post('/login',  [AuthController::class, 'login'])->name('auth.login');
Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

// (Opsional) cek user via WEB
Route::get('/me', [AuthController::class, 'profile'])
    ->middleware('auth:sanctum')
    ->name('auth.me');
