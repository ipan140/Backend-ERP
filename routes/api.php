<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
// ... controller lain

/*
|--------------------------------------------------------------------------
| Public (no auth)
|--------------------------------------------------------------------------
*/
Route::get('/health', fn () => ['ok' => true]);

// Register & Login (public)
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/login',    [AuthController::class, 'login'])->name('auth.login');

/*
|--------------------------------------------------------------------------
| Protected (Bearer token via Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth utilities
    Route::get('/profile', [AuthController::class, 'profile'])->name('auth.profile');

    // ✅ Logout (revoke token aktif)
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

    // Customers
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/',        [\App\Http\Controllers\Api\CustomerController::class, 'index'])->name('index');
        Route::post('/',       [\App\Http\Controllers\Api\CustomerController::class, 'store'])->name('store');
        Route::get('/{id}',    [\App\Http\Controllers\Api\CustomerController::class, 'show'])->whereNumber('id')->name('show');
        Route::put('/{id}',    [\App\Http\Controllers\Api\CustomerController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Api\CustomerController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    // Products
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/',        [\App\Http\Controllers\Api\ProductController::class, 'index'])->name('index');
        Route::post('/',       [\App\Http\Controllers\Api\ProductController::class, 'store'])->name('store');
        Route::get('/{id}',    [\App\Http\Controllers\Api\ProductController::class, 'show'])->whereNumber('id')->name('show');
        Route::put('/{id}',    [\App\Http\Controllers\Api\ProductController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Api\ProductController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    // Quotations (CRUD + workflow + convert)
    Route::prefix('quotations')->name('quotations.')->group(function () {
        Route::controller(\App\Http\Controllers\Api\Quotation\QuotationCRUDController::class)->group(function () {
            Route::get('/',     'index')->name('index');
            Route::post('/',    'store')->name('store');
            Route::get('/{id}', 'show')->whereNumber('id')->name('show');
            Route::put('/{id}', 'update')->whereNumber('id')->name('update');
        });

        Route::controller(\App\Http\Controllers\Api\Quotation\QuotationWorkflowController::class)->group(function () {
            Route::post('/{id}/send',    'send')->whereNumber('id')->name('send');
            Route::post('/{id}/confirm', 'confirm')->whereNumber('id')->name('confirm');
            Route::post('/{id}/lose',    'lose')->whereNumber('id')->name('lose');
            Route::post('/{id}/expire',  'expire')->whereNumber('id')->name('expire');
            Route::post('/{id}/approve', 'confirm')->whereNumber('id')->name('approve'); // alias
        });

        Route::post('/{id}/convert', [\App\Http\Controllers\Api\Quotation\QuotationConvertController::class, 'convert'])
            ->whereNumber('id')->name('convert');
    });

    // Orders
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/',     [\App\Http\Controllers\Api\SalesOrderController::class, 'index'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\Api\SalesOrderController::class, 'show'])->whereNumber('id')->name('show');
        Route::post('/{id}/invoice', [\App\Http\Controllers\Api\SalesOrderController::class, 'makeInvoice'])->whereNumber('id')->name('invoice.create');
        Route::post('/{id}/deliver', [\App\Http\Controllers\Api\SalesOrderController::class, 'deliver'])->whereNumber('id')->name('deliver');
    });

    // Invoices
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/',     [\App\Http\Controllers\Api\InvoiceController::class, 'index'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\Api\InvoiceController::class, 'show'])->whereNumber('id')->name('show');
        Route::post('/{id}/post', [\App\Http\Controllers\Api\InvoiceController::class, 'post'])->whereNumber('id')->name('post');
        Route::post('/{id}/pay',  [\App\Http\Controllers\Api\InvoiceController::class, 'pay'])->whereNumber('id')->name('pay');
    });
});
