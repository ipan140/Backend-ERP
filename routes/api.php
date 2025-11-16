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
use App\Http\Controllers\Api\SCM\ItemsController;
use App\Http\Controllers\Api\SCM\WarehousesController;

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

/* ===== Accounting ===== */
use App\Http\Controllers\Api\Accounting\AccountController;
use App\Http\Controllers\Api\Accounting\JournalController;
use App\Http\Controllers\Api\Accounting\MoveController;
use App\Http\Controllers\Api\Accounting\MovePostController;
use App\Http\Controllers\Api\Accounting\ReportController as AccReportController;

/* ===== Models (lookup) ===== */
use App\Models\{Item, Warehouse};

/* --------------------------------------------------------------------------
| Public (no auth)
|-------------------------------------------------------------------------- */

Route::get('/health', fn() => response()->json(['ok' => true, 'ts' => now()->toISOString()]));

/* Auth (public) */
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/login',    [AuthController::class, 'login'])->name('auth.login');

/* --------------------------------------------------------------------------
| Protected (Sanctum)
|-------------------------------------------------------------------------- */
Route::middleware('auth:sanctum')->group(function () {

    /* ===== Auth utils ===== */
    Route::get('/profile', [AuthController::class, 'profile'])->name('auth.profile');
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

    /* ======= My Attendance (untuk user yang login) ======= */
    Route::get('/my-attendance/today',     [AttendanceController::class, 'myToday'])->name('my-attendance.today');
    Route::get('/my-attendance',           [AttendanceController::class, 'myIndex'])->name('my-attendance.index');
    Route::post('/my-attendance/checkin',  [AttendanceController::class, 'checkIn'])->name('my-attendance.checkin');
    Route::post('/my-attendance/checkout', [AttendanceController::class, 'checkOut'])->name('my-attendance.checkout');

    // ✨ Tambahan opsional (tidak mengganti yang lama)
    Route::get('/my-attendance/open',      [AttendanceController::class, 'myOpen'])->name('my-attendance.open');
    Route::get('/my-attendance/summary',   [AttendanceController::class, 'myMonthlySummary'])->name('my-attendance.summary');

    /* ==================== ACCOUNTING ==================== */
    Route::prefix('accounting')->as('accounting.')->group(function () {
        Route::apiResource('accounts', AccountController::class);
        Route::apiResource('journals', JournalController::class);

        Route::get('moves',                         [MoveController::class, 'index'])->name('moves.index');
        Route::post('moves',                        [MoveController::class, 'store'])->name('moves.store');
        Route::get('moves/{move}',                  [MoveController::class, 'show'])->whereNumber('move')->name('moves.show');
        Route::match(['put', 'patch'], 'moves/{move}', [MoveController::class, 'update'])->whereNumber('move')->name('moves.update');
        Route::delete('moves/{move}',               [MoveController::class, 'destroy'])->whereNumber('move')->name('moves.destroy');

        Route::post('moves/{move}/post',            [MovePostController::class, 'post'])->whereNumber('move')->name('moves.post');
        Route::post('moves/bulk-post',              [MovePostController::class, 'bulkPost'])->name('moves.bulk-post');

        Route::get('reports',                       [AccReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{type}',                [AccReportController::class, 'show'])
            ->where('type', 'trial-balance|general-ledger|income-statement|balance-sheet|cash-flow|aged-receivable')
            ->name('reports.show');
    });

    /* ==================== SALES ==================== */
    Route::prefix('sales')->as('sales.')->group(function () {

        /* Customers */
        Route::prefix('customers')->as('customers.')->group(function () {
            Route::get('/',        [CustomerController::class, 'index'])->name('index');
            Route::post('/',       [CustomerController::class, 'store'])->name('store');
            Route::get('{id}',     [CustomerController::class, 'show'])->whereNumber('id')->name('show');
            Route::match(['put', 'patch'], '{id}', [CustomerController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('{id}',  [CustomerController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });

        /* Products */
        Route::prefix('products')->as('products.')->group(function () {
            Route::get('/',        [ProductController::class, 'index'])->name('index');
            Route::post('/',       [ProductController::class, 'store'])->name('store');
            Route::get('{id}',     [ProductController::class, 'show'])->whereNumber('id')->name('show');
            Route::match(['put', 'patch'], '{id}', [ProductController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('{id}',  [ProductController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });

        /* Quotations */
        Route::prefix('quotations')->as('quotations.')->group(function () {
            Route::controller(QuotationCRUDController::class)->group(function () {
                Route::get('/',     'index')->name('index');
                Route::post('/',    'store')->name('store');
                Route::get('{id}',  'show')->whereNumber('id')->name('show');
                Route::match(['put', 'patch'], '{id}', 'update')->whereNumber('id')->name('update');
            });

            Route::controller(QuotationWorkflowController::class)->group(function () {
                Route::post('{id}/send',    'send')->whereNumber('id')->name('send');
                Route::post('{id}/approve', 'approve')->whereNumber('id')->name('approve');
                Route::post('{id}/lose',    'lose')->whereNumber('id')->name('lose');
                Route::post('{id}/expire',  'expire')->whereNumber('id')->name('expire');
            });

            Route::post('{id}/convert', [QuotationConvertController::class, 'convert'])
                ->whereNumber('id')->name('convert');

            Route::get('{id}/items', [QuotationItemController::class, 'byQuotation'])
                ->whereNumber('id')->name('items.by-quotation');

            // proxy logs by quotation id
            Route::get('{id}/logs', function (Request $request, int $id, QuotationLogController $ctl) {
                $request->merge(['quotation_id' => $id]);
                return $ctl->index($request);
            })->whereNumber('id')->name('logs.by-quotation');
        });

        Route::apiResource('pricelists', PricelistController::class);

        /* Quotation Items (generic) */
        Route::prefix('quotation-items')->as('quotation-items.')->group(function () {
            Route::get('/',        [QuotationItemController::class, 'index'])->name('index');
            Route::post('/',       [QuotationItemController::class, 'store'])->name('store');
            Route::match(['put', 'patch'], '{id}', [QuotationItemController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('{id}',  [QuotationItemController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });

        /* Quotation Logs (generic) */
        Route::prefix('quotation-logs')->as('quotation-logs.')->group(function () {
            Route::get('/',        [QuotationLogController::class, 'index'])->name('index');
            Route::post('/',       [QuotationLogController::class, 'store'])->name('store');
            Route::delete('{id}',  [QuotationLogController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });

        /* Orders */
        Route::prefix('orders')->as('orders.')->group(function () {
            Route::get('/',                 [SalesOrderController::class, 'index'])->name('index');
            Route::get('{id}',              [SalesOrderController::class, 'show'])->whereNumber('id')->name('show');
            Route::post('/',                [SalesOrderController::class, 'store'])->name('store');
            Route::match(['put', 'patch'], '{id}', [SalesOrderController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('{id}',           [SalesOrderController::class, 'destroy'])->whereNumber('id')->name('destroy');

            Route::post('{id}/invoice',     [SalesOrderController::class, 'makeInvoice'])->whereNumber('id')->name('invoice.create');
            Route::post('{id}/deliver',     [SalesOrderController::class, 'deliver'])->whereNumber('id')->name('deliver');
        });

        /* Invoices */
        Route::prefix('invoices')->as('invoices.')->group(function () {
            Route::get('/',          [InvoiceController::class, 'index'])->name('index');
            Route::get('{id}',       [InvoiceController::class, 'show'])->whereNumber('id')->name('show');
            Route::post('{id}/post', [InvoiceController::class, 'post'])->whereNumber('id')->name('post');
            Route::post('{id}/pay',  [InvoiceController::class, 'pay'])->whereNumber('id')->name('pay');
        });
    });

    /* ==================== SCM ==================== */
    Route::prefix('scm')->as('scm.')->group(function () {

        Route::apiResource('vendors', VendorController::class);

        /* Inventory (custom endpoints agar tidak konflik show) */
        Route::prefix('inventory')->as('inventory.')->group(function () {
            Route::get('stocks',        [InventoryController::class, 'stocks'])->name('stocks');
            Route::get('lots',          [InventoryController::class, 'lots'])->name('lots');
            Route::get('expiry-alerts', [InventoryController::class, 'expiryAlerts'])->name('expiry-alerts');

            Route::post('lots',     [InventoryController::class, 'storeLot'])->name('lots.store');
            Route::post('receipt',  [InventoryController::class, 'receipt'])->name('receipt');
            Route::post('transfer', [InventoryController::class, 'transfer'])->name('transfer');
            Route::post('adjust',   [InventoryController::class, 'adjust'])->name('adjust');
        });
        Route::apiResource('inventory', InventoryController::class)->except(['show']);

        /* Logistics */
        Route::post('logistics/{id}/confirm', [LogisticsController::class, 'confirm'])->whereNumber('id')->name('logistics.confirm');
        Route::post('logistics/{id}/pod',     [LogisticsController::class, 'proofOfDelivery'])->whereNumber('id')->name('logistics.pod');
        Route::apiResource('logistics', LogisticsController::class);

        /* Purchases */
        Route::post('purchases/{id}/confirm', [PurchaseController::class, 'confirm'])->whereNumber('id')->name('purchases.confirm');
        Route::post('purchases/{id}/receive', [PurchaseController::class, 'receive'])->whereNumber('id')->name('purchases.receive');
        Route::apiResource('purchases', PurchaseController::class);

        /* Replenishments */
        Route::post('replenishments/check',         [ReplenishmentController::class, 'check'])->name('replenishments.check');
        Route::post('replenishments/auto-generate', [ReplenishmentController::class, 'autoGenerate'])->name('replenishments.auto-generate');

        /* ✨ DROPDOWN KHUSUS REPLENISHMENT (INI YANG DITAMBAHKAN) */
        Route::get('replenishments/items',        [\App\Http\Controllers\Api\SCM\ReplenishmentController::class, 'items']);
        Route::get('replenishments/warehouses',   [\App\Http\Controllers\Api\SCM\ReplenishmentController::class, 'warehouses']);
        Route::get('replenishments/form-data',    [\App\Http\Controllers\Api\SCM\ReplenishmentController::class, 'formData']);

        /* Resource utama */
        Route::apiResource('replenishments', ReplenishmentController::class);

        /* ✨ Lookup endpoints (global lookup) */
        Route::get('items', function (Request $r) {
            $q = Item::query()->select('id', 'name');
            if ($r->filled('search')) {
                $s = trim((string) $r->search);
                $q->where('name', 'like', "%{$s}%");
            }
            return $q->orderBy('name')->limit(50)->get();
        })->name('items.lookup');

        Route::get('warehouses', function (Request $r) {
            $q = Warehouse::query()->select('id', 'name');
            if ($r->filled('search')) {
                $s = trim((string) $r->search);
                $q->where('name', 'like', "%{$s}%");
            }
            return $q->orderBy('name')->limit(50)->get();
        })->name('warehouses.lookup');

        /* Quality */
        Route::prefix('quality')->as('quality.')->group(function () {
            Route::get('checkpoints',     [QualityController::class, 'checkpoints'])->name('checkpoints');
            Route::get('checks',          [QualityController::class, 'index'])->name('checks.index');
            Route::post('checks',         [QualityController::class, 'store'])->name('checks.store');
            Route::post('nonconformance', [QualityController::class, 'nonconformance'])->name('nonconformance.store');
            Route::get('reports',         [QualityController::class, 'reports'])->name('reports');
        });

        /* Processing */
        Route::prefix('processing')->as('processing.')->group(function () {
            Route::get('workorders',              [ProcessingController::class, 'index'])->name('workorders.index');
            Route::get('workorders/{id}',         [ProcessingController::class, 'show'])->whereNumber('id')->name('workorders.show');
            Route::post('workorders',             [ProcessingController::class, 'store'])->name('workorders.store');
            Route::post('workorders/{id}/start',  [ProcessingController::class, 'start'])->whereNumber('id')->name('workorders.start');
            Route::post('workorders/{id}/finish', [ProcessingController::class, 'finish'])->whereNumber('id')->name('workorders.finish');
        });

        /* Maintenance */
        Route::prefix('maintenance')->as('maintenance.')->group(function () {

            /* =========================
     * EQUIPMENTS
     * ========================= */
            Route::get('equipments',  [MaintenanceController::class, 'equipments'])
                ->name('equipments');

            Route::post('equipments', [MaintenanceController::class, 'storeEquipment'])
                ->name('equipments.store');

            // NEW → UPDATE EQUIPMENT
            Route::put('equipments/{id}', [MaintenanceController::class, 'updateEquipment'])
                ->whereNumber('id')
                ->name('equipments.update');

            // NEW → DELETE EQUIPMENT
            Route::delete('equipments/{id}', [MaintenanceController::class, 'destroyEquipment'])
                ->whereNumber('id')
                ->name('equipments.destroy');


            /* =========================
     * MAINTENANCE PLANS
     * ========================= */
            Route::get('plans', [MaintenanceController::class, 'plans'])
                ->name('plans');

            Route::post('plans', [MaintenanceController::class, 'storePlan'])
                ->name('plans.store');

            Route::get('plans/{id}', [MaintenanceController::class, 'showPlan'])
                ->whereNumber('id')
                ->name('plans.show');

            Route::put('plans/{id}', [MaintenanceController::class, 'updatePlan'])
                ->whereNumber('id')
                ->name('plans.update');

            Route::delete('plans/{id}', [MaintenanceController::class, 'destroyPlan'])
                ->whereNumber('id')
                ->name('plans.destroy');


            /* =========================
     * MAINTENANCE REQUESTS
     * ========================= */
            Route::get('requests', [MaintenanceController::class, 'index'])
                ->name('requests.index');

            Route::post('requests', [MaintenanceController::class, 'storeRequest'])
                ->name('requests.store');

            Route::post('request', [MaintenanceController::class, 'storeRequest'])
                ->name('request.single');

            Route::get('requests/{id}', [MaintenanceController::class, 'showRequest'])
                ->whereNumber('id')
                ->name('requests.show');

            Route::put('requests/{id}', [MaintenanceController::class, 'updateRequest'])
                ->whereNumber('id')
                ->name('requests.update');

            Route::delete('requests/{id}', [MaintenanceController::class, 'destroyRequest'])
                ->whereNumber('id')
                ->name('requests.destroy');

            Route::post('requests/{id}/complete', [MaintenanceController::class, 'complete'])
                ->whereNumber('id')
                ->name('requests.complete');

            Route::post('complete/{id}', [MaintenanceController::class, 'complete'])
                ->whereNumber('id');
        });

        /* Reports */
        Route::get('reports', [ScmReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{type}', [ScmReportController::class, 'show'])
            ->where('type', 'stock-summary|stock-movement|valuation|aging|expiry')
            ->name('reports.show');
    });


    /* ==================== HR ==================== */
    Route::prefix('hr')->as('hr.')->group(function () {
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('jobs',        JobPositionController::class);
        Route::apiResource('employees',   EmployeeController::class);
        Route::apiResource('holidays',    PublicHolidayController::class);

        Route::apiResource('leave-types',       LeaveTypeController::class);
        Route::apiResource('leave-allocations', LeaveAllocationController::class);
        Route::apiResource('leaves',            LeaveController::class);
        Route::put('leaves/{id}/approve', [LeaveController::class, 'approve'])->whereNumber('id')->name('leaves.approve');

        Route::apiResource('shifts',      ShiftController::class);

        Route::get('attendances',         [AttendanceController::class, 'index'])->name('attendances.index');
        Route::post('attendances',        [AttendanceController::class, 'store'])->name('attendances.store');
        Route::get('attendances/{id}',    [AttendanceController::class, 'show'])->whereNumber('id')->name('attendances.show');
        Route::match(['put', 'patch'], 'attendances/{id}', [AttendanceController::class, 'update'])->whereNumber('id')->name('attendances.update');
        Route::delete('attendances/{id}', [AttendanceController::class, 'destroy'])->whereNumber('id')->name('attendances.destroy');

        // Generic checkin/checkout (admin/HR)
        Route::post('attendances/checkin',  [AttendanceController::class, 'checkIn'])->name('attendances.checkin');
        Route::post('attendances/checkout', [AttendanceController::class, 'checkOut'])->name('attendances.checkout');

        // ✨ Tambahan opsional untuk admin/HR (tidak mengganti yang lama)
        Route::get('attendances/open', [AttendanceController::class, 'open'])->name('attendances.open');

        Route::apiResource('contracts',   ContractController::class);
        Route::apiResource('salary-structures', SalaryStructureController::class);
        Route::apiResource('salary-rules',      SalaryRuleController::class);

        Route::get('payslips',            [PayslipController::class, 'index'])->name('payslips.index');
        Route::post('payslips',           [PayslipController::class, 'store'])->name('payslips.store');
        Route::get('payslips/{id}',       [PayslipController::class, 'show'])->whereNumber('id')->name('payslips.show');
        Route::match(['put', 'patch'], 'payslips/{id}', [PayslipController::class, 'update'])->whereNumber('id')->name('payslips.update');
        Route::delete('payslips/{id}',    [PayslipController::class, 'destroy'])->whereNumber('id')->name('payslips.destroy');

        Route::post('payslips/generate',  [PayslipController::class, 'generate'])->name('payslips.generate');
        Route::post('payslips/{id}/post', [PayslipController::class, 'post'])->whereNumber('id')->name('payslips.post');
        Route::post('payslips/{id}/pay',  [PayslipController::class, 'pay'])->whereNumber('id')->name('payslips.pay');
    });
});

/* --------------------------------------------------------------------------
| CORS preflight helper (agar OPTIONS tidak 404 → “Network Error”)
|-------------------------------------------------------------------------- */
Route::options('/{any}', fn() => response()->noContent(204))
    ->where('any', '.*');
