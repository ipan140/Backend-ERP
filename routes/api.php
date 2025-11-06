<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;

/* ===== Sales ===== */
use App\Http\Controllers\Api\Sales\CustomerController;
use App\Http\Controllers\Api\Sales\ProductController;
use App\Http\Controllers\Api\Sales\PricelistController;
use App\Http\Controllers\Api\Sales\SalesOrderController;
use App\Http\Controllers\Api\Sales\InvoiceController;
use App\Http\Controllers\Api\Sales\Quotation\QuotationCRUDController;
use App\Http\Controllers\Api\Sales\Quotation\QuotationWorkflowController;
use App\Http\Controllers\Api\Sales\Quotation\QuotationConvertController;
use App\Http\Controllers\Api\Sales\Quotation\QuotationItemController;
use App\Http\Controllers\Api\Sales\Quotation\QuotationLogController;

/* ===== SCM ===== */
use App\Http\Controllers\Api\SCM\InventoryController;
use App\Http\Controllers\Api\SCM\LogisticsController;
use App\Http\Controllers\Api\SCM\MaintenanceController;
use App\Http\Controllers\Api\SCM\ProcessingController;
use App\Http\Controllers\Api\SCM\PurchaseController;
use App\Http\Controllers\Api\SCM\QualityController;
use App\Http\Controllers\Api\SCM\ReplenishmentController;
use App\Http\Controllers\Api\SCM\ReportController as ScmReportController;
use App\Http\Controllers\Api\SCM\VendorController;

/* ===== HR ===== */
use App\Http\Controllers\Api\HR\DepartmentController;
use App\Http\Controllers\Api\HR\JobPositionController;
use App\Http\Controllers\Api\HR\EmployeeController;
use App\Http\Controllers\Api\HR\ContractController;
use App\Http\Controllers\Api\HR\ShiftController;
use App\Http\Controllers\Api\HR\AttendanceController;
use App\Http\Controllers\Api\HR\LeaveTypeController;
use App\Http\Controllers\Api\HR\LeaveAllocationController;
use App\Http\Controllers\Api\HR\LeaveController;
use App\Http\Controllers\Api\HR\PublicHolidayController;
use App\Http\Controllers\Api\HR\SalaryStructureController;
use App\Http\Controllers\Api\HR\SalaryRuleController;
use App\Http\Controllers\Api\HR\PayslipController;

/*
|--------------------------------------------------------------------------
| Public (no auth)
|--------------------------------------------------------------------------
*/
Route::get('/health', fn () => ['ok' => true]);

Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/login',    [AuthController::class, 'login'])->name('auth.login');

/*
|--------------------------------------------------------------------------
| Protected (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth utils
    Route::get('/profile', [AuthController::class, 'profile'])->name('auth.profile');
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

    /* ==================== SALES ==================== */
    Route::prefix('sales')->as('sales.')->group(function () {

        /* ---- Customers ---- */
        Route::prefix('customers')->as('customers.')->group(function () {
            Route::get('/',        [CustomerController::class, 'index'])->name('index');
            Route::post('/',       [CustomerController::class, 'store'])->name('store');
            Route::get('{id}',     [CustomerController::class, 'show'])->whereNumber('id')->name('show');
            Route::match(['put','patch'], '{id}', [CustomerController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('{id}',  [CustomerController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });

        /* ---- Products ---- */
        Route::prefix('products')->as('products.')->group(function () {
            Route::get('/',        [ProductController::class, 'index'])->name('index');
            Route::post('/',       [ProductController::class, 'store'])->name('store');
            Route::get('{id}',     [ProductController::class, 'show'])->whereNumber('id')->name('show');
            Route::match(['put','patch'], '{id}', [ProductController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('{id}',  [ProductController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });

        /* ---- Pricelists ---- */
        Route::prefix('pricelists')->as('pricelists.')->group(function () {
            Route::get('/',        [PricelistController::class, 'index'])->name('index');
            Route::post('/',       [PricelistController::class, 'store'])->name('store');
            Route::get('{id}',     [PricelistController::class, 'show'])->whereNumber('id')->name('show');
            Route::match(['put','patch'], '{id}', [PricelistController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('{id}',  [PricelistController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });

        /* ---- Quotations ---- */
        Route::prefix('quotations')->as('quotations.')->group(function () {
            // CRUD
            Route::controller(QuotationCRUDController::class)->group(function () {
                Route::get('/',     'index')->name('index');
                Route::post('/',    'store')->name('store');
                Route::get('{id}',  'show')->whereNumber('id')->name('show');
                Route::match(['put','patch'], '{id}', 'update')->whereNumber('id')->name('update');
            });

            // Workflow
            Route::controller(QuotationWorkflowController::class)->group(function () {
                Route::post('{id}/send',    'send')->whereNumber('id')->name('send');
                Route::post('{id}/approve', 'approve')->whereNumber('id')->name('approve');
                Route::post('{id}/lose',    'lose')->whereNumber('id')->name('lose');
                Route::post('{id}/expire',  'expire')->whereNumber('id')->name('expire');
            });

            // Convert
            Route::post('{id}/convert', [QuotationConvertController::class, 'convert'])
                ->whereNumber('id')->name('convert');

            // Items & logs per quotation
            Route::get('{id}/items', [QuotationItemController::class, 'byQuotation'])
                ->whereNumber('id')->name('items.by-quotation');

            Route::get('{id}/logs', function (Request $request, int $id, QuotationLogController $ctl) {
                $request->merge(['quotation_id' => $id]);
                return $ctl->index($request);
            })->whereNumber('id')->name('logs.by-quotation');
        });

        /* ---- Quotation Items (generic) ---- */
        Route::prefix('quotation-items')->as('quotation-items.')->group(function () {
            Route::get('/',        [QuotationItemController::class, 'index'])->name('index');
            Route::post('/',       [QuotationItemController::class, 'store'])->name('store');
            Route::match(['put','patch'], '{id}', [QuotationItemController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('{id}',  [QuotationItemController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });

        /* ---- Quotation Logs (generic) ---- */
        Route::prefix('quotation-logs')->as('quotation-logs.')->group(function () {
            Route::get('/',        [QuotationLogController::class, 'index'])->name('index');
            Route::post('/',       [QuotationLogController::class, 'store'])->name('store');
            Route::delete('{id}',  [QuotationLogController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });

        /* ---- Orders ---- */
        Route::prefix('orders')->as('orders.')->group(function () {
            Route::get('/',                 [SalesOrderController::class, 'index'])->name('index');
            Route::get('{id}',              [SalesOrderController::class, 'show'])->whereNumber('id')->name('show');
            Route::post('/',                [SalesOrderController::class, 'store'])->name('store');
            Route::match(['put','patch'], '{id}', [SalesOrderController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('{id}',           [SalesOrderController::class, 'destroy'])->whereNumber('id')->name('destroy');

            Route::post('{id}/invoice',     [SalesOrderController::class, 'makeInvoice'])->whereNumber('id')->name('invoice.create');
            Route::post('{id}/deliver',     [SalesOrderController::class, 'deliver'])->whereNumber('id')->name('deliver');
        });

        /* ---- Invoices ---- */
        Route::prefix('invoices')->as('invoices.')->group(function () {
            Route::get('/',          [InvoiceController::class, 'index'])->name('index');
            Route::get('{id}',       [InvoiceController::class, 'show'])->whereNumber('id')->name('show');
            Route::post('{id}/post', [InvoiceController::class, 'post'])->whereNumber('id')->name('post');
            Route::post('{id}/pay',  [InvoiceController::class, 'pay'])->whereNumber('id')->name('pay');
        });
    });

    /* ==================== SCM ==================== */
    Route::prefix('scm')->as('scm.')->group(function () {

        /* ---- Inventory custom endpoints (letakkan SEBELUM resource) ---- */
        Route::prefix('inventory')->as('inventory.')->group(function () {
            // Read
            Route::get('stocks',        [InventoryController::class, 'stocks'])->name('stocks');
            Route::get('lots',          [InventoryController::class, 'lots'])->name('lots');
            Route::get('expiry-alerts', [InventoryController::class, 'expiryAlerts'])->name('expiry-alerts');

            // Create / Actions
            Route::post('lots',     [InventoryController::class, 'storeLot'])->name('lots.store');
            Route::post('receipt',  [InventoryController::class, 'receipt'])->name('receipt');
            Route::post('transfer', [InventoryController::class, 'transfer'])->name('transfer');
            Route::post('adjust',   [InventoryController::class, 'adjust'])->name('adjust');
        });

        // Hindari konflik 'lots' dkk → buang 'show'
        Route::apiResource('inventory', InventoryController::class)->except(['show']);

        /* ---- Logistics ---- */
        Route::apiResource('logistics', LogisticsController::class);
        Route::post('logistics/{id}/confirm', [LogisticsController::class, 'confirm'])
            ->whereNumber('id')->name('logistics.confirm');
        Route::post('logistics/{id}/pod',     [LogisticsController::class, 'proofOfDelivery'])
            ->whereNumber('id')->name('logistics.pod');

        /* ---- Purchases ---- */
        // Aksi khusus dulu supaya tidak ditangkap sebagai {purchase} milik resource
        Route::post('purchases/{id}/confirm', [PurchaseController::class, 'confirm'])
            ->whereNumber('id')->name('purchases.confirm');
        Route::post('purchases/{id}/receive', [PurchaseController::class, 'receive'])
            ->whereNumber('id')->name('purchases.receive');
        Route::apiResource('purchases', PurchaseController::class);

        /* ---- Replenishments ---- */
        // Actions FIRST
        Route::post('replenishments/check',         [ReplenishmentController::class, 'check'])->name('replenishments.check');
        Route::post('replenishments/auto-generate', [ReplenishmentController::class, 'autoGenerate'])->name('replenishments.auto-generate');
        Route::apiResource('replenishments', ReplenishmentController::class);

        /* ---- Vendors ---- */
        Route::post('vendors/rating', [VendorController::class, 'rating'])->name('vendors.rating');
        Route::apiResource('vendors', VendorController::class);

        /* ---- Maintenance (custom endpoints) ---- */
        Route::prefix('maintenance')->as('maintenance.')->group(function () {
            Route::get('equipments',      [MaintenanceController::class, 'equipments'])->name('equipments');
            Route::post('equipments',     [MaintenanceController::class, 'storeEquipment'])->name('equipments.store');
            Route::get('plans',           [MaintenanceController::class, 'plans'])->name('plans');
            Route::post('request',        [MaintenanceController::class, 'request'])->name('request');
            Route::post('complete/{id}',  [MaintenanceController::class, 'complete'])->whereNumber('id')->name('complete');
        });

        /* ---- Processing (Work Orders - custom endpoints) ---- */
        Route::prefix('processing')->as('processing.')->group(function () {
            Route::get('workorders',               [ProcessingController::class, 'index'])->name('workorders.index');
            Route::get('workorders/{id}',          [ProcessingController::class, 'show'])->whereNumber('id')->name('workorders.show');
            Route::post('workorders',              [ProcessingController::class, 'store'])->name('workorders.store');
            Route::post('workorders/{id}/start',   [ProcessingController::class, 'start'])->whereNumber('id')->name('workorders.start');
            Route::post('workorders/{id}/finish',  [ProcessingController::class, 'finish'])->whereNumber('id')->name('workorders.finish');
        });

        /* ---- Quality (custom endpoints) ---- */
        Route::prefix('quality')->as('quality.')->group(function () {
            Route::get('checkpoints',        [QualityController::class, 'checkpoints'])->name('checkpoints');
            Route::get('checks',             [QualityController::class, 'index'])->name('checks.index');
            Route::post('checks',            [QualityController::class, 'store'])->name('checks.store');
            Route::post('nonconformance',    [QualityController::class, 'nonconformance'])->name('nonconformance.store');
            Route::get('reports',            [QualityController::class, 'reports'])->name('reports');
        });

        /* ---- Reports (read-only) ---- */
        Route::apiResource('reports', ScmReportController::class)->only(['index','show']);
    });

    /* ==================== HR (Core + Payroll) ==================== */
    Route::prefix('hr')->as('hr.')->group(function () {
        // Master
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('jobs',        JobPositionController::class);
        Route::apiResource('employees',   EmployeeController::class);

        // Public Holidays
        Route::apiResource('holidays',    PublicHolidayController::class);

        // Time Off
        Route::apiResource('leave-types',       LeaveTypeController::class);
        Route::apiResource('leave-allocations', LeaveAllocationController::class);
        Route::apiResource('leaves',            LeaveController::class);
        Route::put('leaves/{id}/approve', [LeaveController::class, 'approve'])
            ->whereNumber('id')->name('leaves.approve');

        // Attendance
        Route::apiResource('shifts',      ShiftController::class);
        Route::get('attendances',         [AttendanceController::class, 'index'])->name('attendances.index');
        Route::post('attendances',        [AttendanceController::class, 'store'])->name('attendances.store');
        Route::get('attendances/{id}',    [AttendanceController::class, 'show'])->whereNumber('id')->name('attendances.show');
        Route::match(['put','patch'], 'attendances/{id}', [AttendanceController::class, 'update'])->whereNumber('id')->name('attendances.update');
        Route::delete('attendances/{id}', [AttendanceController::class, 'destroy'])->whereNumber('id')->name('attendances.destroy');
        Route::post('attendances/checkin',  [AttendanceController::class, 'checkIn'])->name('attendances.checkin');
        Route::post('attendances/checkout', [AttendanceController::class, 'checkOut'])->name('attendances.checkout');

        // Contracts
        Route::apiResource('contracts',   ContractController::class);

        // Payroll
        Route::apiResource('salary-structures', SalaryStructureController::class);
        Route::apiResource('salary-rules',      SalaryRuleController::class);

        Route::get('payslips',            [PayslipController::class, 'index'])->name('payslips.index');
        Route::post('payslips',           [PayslipController::class, 'store'])->name('payslips.store');
        Route::get('payslips/{id}',       [PayslipController::class, 'show'])->whereNumber('id')->name('payslips.show');
        Route::match(['put','patch'], 'payslips/{id}', [PayslipController::class, 'update'])->whereNumber('id')->name('payslips.update');
        Route::delete('payslips/{id}',    [PayslipController::class, 'destroy'])->whereNumber('id')->name('payslips.destroy');

        // Actions
        Route::post('payslips/generate',  [PayslipController::class, 'generate'])->name('payslips.generate');
        Route::post('payslips/{id}/post', [PayslipController::class, 'post'])->whereNumber('id')->name('payslips.post');
        Route::post('payslips/{id}/pay',  [PayslipController::class, 'pay'])->whereNumber('id')->name('payslips.pay');
    });

    /* ==================== ACCOUNTING (GL) ==================== */
    // Route::prefix('accounting')->as('accounting.')->group(function () {

    //     // Master Data
    //     Route::apiResource('accounts', AccAccountController::class);   // Chart of Accounts
    //     Route::apiResource('journals', AccJournalController::class);   // Journals (Bank/Cash/Sales/General)

    //     // Journal Entries (Moves)
    //     Route::apiResource('moves', AccMoveController::class);         // draft CRUD

    //     // Posting / Unposting
    //     Route::post('moves/{move}/post',   [AccMovePostController::class, 'post'])
    //         ->whereNumber('move')->name('moves.post');
    //     Route::post('moves/{move}/unpost', [AccMovePostController::class, 'unpost'])
    //         ->whereNumber('move')->name('moves.unpost');

    //     // Reports
    //     Route::get('reports/trial-balance',  [AccReportController::class, 'trialBalance'])
    //         ->name('reports.trial-balance');
    //     Route::get('reports/general-ledger', [AccReportController::class, 'generalLedger'])
    //         ->name('reports.general-ledger');
    // });
});
