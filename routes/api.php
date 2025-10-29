<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProductController;

use App\Http\Controllers\Api\Quotation\QuotationCRUDController;
use App\Http\Controllers\Api\Quotation\QuotationWorkflowController;
use App\Http\Controllers\Api\Quotation\QuotationConvertController;
use App\Http\Controllers\Api\Quotation\QuotationItemController;
use App\Http\Controllers\Api\Quotation\QuotationLogController;

use App\Http\Controllers\Api\SalesOrderController;
use App\Http\Controllers\Api\InvoiceController;

/*
|--------------------------------------------------------------------------
| Public (no auth)
|--------------------------------------------------------------------------
*/
Route::get('/health', fn () => ['ok' => true]);

// Auth (public)
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

    /* ==================== Customers ==================== */
    Route::prefix('customers')->as('customers.')->group(function () {
        Route::get('/',        [CustomerController::class, 'index'])->name('index');
        Route::post('/',       [CustomerController::class, 'store'])->name('store');
        Route::get('{id}',     [CustomerController::class, 'show'])->whereNumber('id')->name('show');
        Route::put('{id}',     [CustomerController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('{id}',  [CustomerController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    /* ==================== Products ==================== */
    Route::prefix('products')->as('products.')->group(function () {
        Route::get('/',        [ProductController::class, 'index'])->name('index');
        Route::post('/',       [ProductController::class, 'store'])->name('store');
        Route::get('{id}',     [ProductController::class, 'show'])->whereNumber('id')->name('show');
        Route::put('{id}',     [ProductController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('{id}',  [ProductController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    /* ==================== Quotations ==================== */
    Route::prefix('quotations')->as('quotations.')->group(function () {
        // CRUD
        Route::controller(QuotationCRUDController::class)->group(function () {
            Route::get('/',     'index')->name('index');
            Route::post('/',    'store')->name('store');
            Route::get('{id}',  'show')->whereNumber('id')->name('show');
            Route::put('{id}',  'update')->whereNumber('id')->name('update');
            // Route::delete('{id}', 'destroy')->whereNumber('id')->name('destroy'); // opsional
        });

        // Workflow
        Route::controller(QuotationWorkflowController::class)->group(function () {
            Route::post('{id}/send',    'send'   )->whereNumber('id')->name('send');
            Route::post('{id}/approve', 'approve')->whereNumber('id')->name('approve');
            Route::post('{id}/lose',    'lose'   )->whereNumber('id')->name('lose');
            Route::post('{id}/expire',  'expire' )->whereNumber('id')->name('expire');
        });

        // Convert quotation -> order/invoice
        Route::post('{id}/convert', [QuotationConvertController::class, 'convert'])
            ->whereNumber('id')->name('convert');

        // Items by quotation (fallback untuk FE yang pakai pola ini)
        Route::get('{id}/items', [QuotationItemController::class, 'byQuotation'])
            ->whereNumber('id')->name('items.by-quotation');

        // Alias logs per quotation: GET /api/quotations/{id}/logs
        Route::get('{id}/logs', function (Request $request, int $id, QuotationLogController $ctl) {
            $request->merge(['quotation_id' => $id]);
            return $ctl->index($request);
        })->whereNumber('id')->name('logs.by-quotation');
    });

    /* ==================== Quotation Logs ==================== */
    Route::prefix('quotation-logs')->as('quotation-logs.')->group(function () {
        // GET /api/quotation-logs?quotation_id=ID
        Route::get('/',    [QuotationLogController::class, 'index'])->name('index');
        // POST /api/quotation-logs {quotation_id, status, note?}
        Route::post('/',   [QuotationLogController::class, 'store'])->name('store');
        // DELETE /api/quotation-logs/{id}
        Route::delete('{id}', [QuotationLogController::class, 'destroy'])
            ->whereNumber('id')->name('destroy');
    });

    /* ==================== Quotation Items ==================== */
    Route::prefix('quotation-items')->as('quotation-items.')->group(function () {
        // GET /api/quotation-items?quotation_id=ID
        Route::get('/',        [QuotationItemController::class, 'index'])->name('index');
        Route::post('/',       [QuotationItemController::class, 'store'])->name('store');
        Route::put('{id}',     [QuotationItemController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('{id}',  [QuotationItemController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    /* ==================== Orders ==================== */
    Route::prefix('orders')->as('orders.')->group(function () {
        Route::get('/',              [SalesOrderController::class, 'index'])->name('index');
        Route::get('{id}',           [SalesOrderController::class, 'show'])->whereNumber('id')->name('show');

        // Tambahan agar tombol di UI jalan
        Route::post('/',             [SalesOrderController::class, 'store'])->name('store');
        Route::delete('{id}',        [SalesOrderController::class, 'destroy'])->whereNumber('id')->name('destroy');

        // Aksi
        Route::post('{id}/invoice',  [SalesOrderController::class, 'makeInvoice'])->whereNumber('id')->name('invoice.create');
        Route::post('{id}/deliver',  [SalesOrderController::class, 'deliver'])->whereNumber('id')->name('deliver');
    });

    /* ==================== Invoices ==================== */
    Route::prefix('invoices')->as('invoices.')->group(function () {
        Route::get('/',        [InvoiceController::class, 'index'])->name('index');
        Route::get('{id}',     [InvoiceController::class, 'show'])->whereNumber('id')->name('show');
        Route::post('{id}/post', [InvoiceController::class, 'post'])->whereNumber('id')->name('post');
        Route::post('{id}/pay',  [InvoiceController::class, 'pay'])->whereNumber('id')->name('pay');
    });

    /* ==================== Pricelists ==================== */
    Route::prefix('pricelists')->as('pricelists.')->group(function () {
        $ctl = \App\Http\Controllers\Api\PricelistController::class;
        Route::get('/',       [$ctl, 'index'])->name('index');
        Route::post('/',      [$ctl, 'store'])->name('store');
        Route::get('{id}',    [$ctl, 'show'])->whereNumber('id')->name('show');
        Route::put('{id}',    [$ctl, 'update'])->whereNumber('id')->name('update');
        Route::delete('{id}', [$ctl, 'destroy'])->whereNumber('id')->name('destroy');
    });
});
