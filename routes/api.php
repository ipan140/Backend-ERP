<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\Quotation\QuotationCRUDController;
use App\Http\Controllers\Api\Quotation\QuotationWorkflowController;
use App\Http\Controllers\Api\Quotation\QuotationConvertController;
use App\Http\Controllers\Api\SalesOrderController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\Quotation\QuotationLogController;

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
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

    /* -------------------- Customers -------------------- */
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/',        [CustomerController::class, 'index'])->name('index');
        Route::post('/',       [CustomerController::class, 'store'])->name('store');
        Route::get('/{id}',    [CustomerController::class, 'show'])->whereNumber('id')->name('show');
        Route::put('/{id}',    [CustomerController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    /* -------------------- Products -------------------- */
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/',        [ProductController::class, 'index'])->name('index');
        Route::post('/',       [ProductController::class, 'store'])->name('store');
        Route::get('/{id}',    [ProductController::class, 'show'])->whereNumber('id')->name('show');
        Route::put('/{id}',    [ProductController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    /* -------------------- Quotations -------------------- */
    Route::prefix('quotations')->name('quotations.')->group(function () {

        // CRUD
        Route::controller(QuotationCRUDController::class)->group(function () {
            Route::get('/',     'index')->name('index');
            Route::post('/',    'store')->name('store');
            Route::get('/{id}', 'show')->whereNumber('id')->name('show');
            Route::put('/{id}', 'update')->whereNumber('id')->name('update');
        });

        // Workflow
        Route::controller(QuotationWorkflowController::class)->group(function () {
            Route::post('/{id}/send',    'send'   )->whereNumber('id')->name('send');
            Route::post('/{id}/approve', 'approve')->whereNumber('id')->name('approve'); // ✅ fixed
            Route::post('/{id}/lose',    'lose'   )->whereNumber('id')->name('lose');
            Route::post('/{id}/expire',  'expire' )->whereNumber('id')->name('expire');
            // ⛔️ Hapus/ga usah definisikan "confirm" kalau ga ada method-nya
            // Route::post('/{id}/confirm', 'confirm')->whereNumber('id')->name('confirm');
        });

        // Convert quotation -> order/invoice (sesuai controller-mu)
        Route::post('/{id}/convert', [QuotationConvertController::class, 'convert'])
            ->whereNumber('id')->name('convert');
    });

    /* --------- Quotation Logs (dipakai oleh halaman Logs) --------- */
    Route::prefix('quotation-logs')->name('quotation-logs.')->group(function () {
        // GET /api/quotation-logs?quotation_id=ID
        Route::get('/',    [QuotationLogController::class, 'index'])->name('index');
        // POST /api/quotation-logs  {quotation_id, status, note?}
        Route::post('/',   [QuotationLogController::class, 'store'])->name('store');
        // DELETE /api/quotation-logs/{id}
        Route::delete('/{id}', [QuotationLogController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    /* -------------------- Orders -------------------- */
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/',     [SalesOrderController::class, 'index'])->name('index');
        Route::get('/{id}', [SalesOrderController::class, 'show'])->whereNumber('id')->name('show');
        Route::post('/{id}/invoice', [SalesOrderController::class, 'makeInvoice'])->whereNumber('id')->name('invoice.create');
        Route::post('/{id}/deliver', [SalesOrderController::class, 'deliver'])->whereNumber('id')->name('deliver');
    });

    /* -------------------- Invoices -------------------- */
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/',     [InvoiceController::class, 'index'])->name('index');
        Route::get('/{id}', [InvoiceController::class, 'show'])->whereNumber('id')->name('show');
        Route::post('/{id}/post', [InvoiceController::class, 'post'])->whereNumber('id')->name('post');
        Route::post('/{id}/pay',  [InvoiceController::class, 'pay'])->whereNumber('id')->name('pay');
    });

    Route::prefix('pricelists')->name('pricelists.')->group(function () {
        Route::get('/',     [\App\Http\Controllers\Api\PricelistController::class, 'index'])->name('index');
        Route::post('/',    [\App\Http\Controllers\Api\PricelistController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\Api\PricelistController::class, 'show'])->whereNumber('id')->name('show');
        Route::put('/{id}', [\App\Http\Controllers\Api\PricelistController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Api\PricelistController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });
});
