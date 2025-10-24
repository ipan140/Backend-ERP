<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PricelistController;

use App\Http\Controllers\Api\Quotation\QuotationCRUDController;
use App\Http\Controllers\Api\Quotation\QuotationWorkflowController;
use App\Http\Controllers\Api\Quotation\QuotationConvertController;

/*
|--------------------------------------------------------------------------
| Public (no auth)
|--------------------------------------------------------------------------
*/
Route::get('/health', fn () => ['ok' => true]);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth utilities
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ========================
    // Customers (CRUD dasar)
    // ========================
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/',        [CustomerController::class, 'index'])->name('index');
        Route::post('/',       [CustomerController::class, 'store'])->name('store');
        Route::get('/{id}',    [CustomerController::class, 'show'])->whereNumber('id')->name('show');
        Route::put('/{id}',    [CustomerController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->whereNumber('id')->name('destroy'); // optional
    });

    // ========================
    // Products (CRUD dasar)
    // ========================
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/',        [ProductController::class, 'index'])->name('index');
        Route::post('/',       [ProductController::class, 'store'])->name('store');
        Route::get('/{id}',    [ProductController::class, 'show'])->whereNumber('id')->name('show');
        Route::put('/{id}',    [ProductController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->whereNumber('id')->name('destroy'); // optional
    });

    // ==========================================
    // Quotations (CRUD + workflow + conversion)
    // ==========================================
    Route::prefix('quotations')->name('quotations.')->group(function () {

        // CRUD
        Route::controller(QuotationCRUDController::class)->group(function () {
            Route::get('/',      'index' )->name('index');
            Route::post('/',     'store' )->name('store');
            Route::get('/{id}',  'show'  )->whereNumber('id')->name('show');
            Route::put('/{id}',  'update')->whereNumber('id')->name('update');
        });

        // Workflow
        Route::controller(QuotationWorkflowController::class)->group(function () {
            Route::post('/{id}/send',    'send'   )->whereNumber('id')->name('send');
            Route::post('/{id}/approve', 'approve')->whereNumber('id')->name('approve');
            Route::post('/{id}/lose',    'lose'   )->whereNumber('id')->name('lose');
            Route::post('/{id}/expire',  'expire' )->whereNumber('id')->name('expire');
        });

        // Convert → Sales Order (stub)
        Route::post('/{id}/convert', [QuotationConvertController::class, 'convert'])
            ->whereNumber('id')->name('convert');
    });

    // ========================
    // Pricelists (CRUD)
    // ========================
    Route::prefix('pricelists')->name('pricelists.')->group(function () {
        Route::get('/',        [PricelistController::class, 'index'])->name('index');   // ?search=&active=&type=
        Route::post('/',       [PricelistController::class, 'store'])->name('store');
        Route::get('/{id}',    [PricelistController::class, 'show'])->whereNumber('id')->name('show');
        Route::put('/{id}',    [PricelistController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [PricelistController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });
    
});
