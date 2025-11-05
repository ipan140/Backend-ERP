<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 0) USER SEEDER
        $this->call(UserSeeder::class);

        if (app()->environment('production')) {
            $this->command?->warn('⚠️ Production environment: skip destructive resets.');

            $this->call([
                DepartmentSeeder::class,
                JobPositionSeeder::class,
                EmployeeSeeder::class,
                ShiftSeeder::class,
                PublicHolidaySeeder::class,
                LeaveTypeSeeder::class,
                SalaryRuleSeeder::class,
            ]);
            return;
        }

        // Disable FK sementara
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        /* ==============================================================
         |  RESET SEEDERS
         ============================================================== */
        $this->call([
            ResetHRDataSeeder::class,
            ResetSalesDataSeeder::class,
        ]);

        /* ==============================================================
         |  HR MODULE
         ============================================================== */
        $this->call([
            DepartmentSeeder::class,
            JobPositionSeeder::class,
            EmployeeSeeder::class,
            ContractSeeder::class,
            ShiftSeeder::class,
            AttendanceSeeder::class,
            PublicHolidaySeeder::class,
            LeaveTypeSeeder::class,
            LeaveAllocationSeeder::class,
            LeaveSeeder::class,
            SalaryRuleSeeder::class,
            SalaryStructureSeeder::class,
            PayslipSeeder::class,
        ]);

        /* ==============================================================
         |  SALES / ERP MODULE
         ============================================================== */
        $this->call([
            CustomerSeeder::class,
            ProductSeeder::class,
            QuotationSeeder::class,
            QuotationItemSeeder::class,
            QuotationStatusLogSeeder::class,
            PaymentTermSeeder::class,
            PricelistSeeder::class,
            SalesSeeder::class,
            InvoiceSeeder::class,
        ]);

        /* ==============================================================
         |  SUPPLY CHAIN MANAGEMENT (SCM) MODULE
         ============================================================== */
        $this->call([
            VendorSeeder::class,
            ItemSeeder::class,
            WarehouseSeeder::class,
            StockLevelSeeder::class,
            StockMoveSeeder::class,

            // Purchase
            PurchaseSeeder::class,
            PurchaseItemSeeder::class,

            // Shipment & Quality
            ShipmentSeeder::class,
            ShipmentItemSeeder::class,
            QualityInspectionSeeder::class,
            QualityInspectionItemSeeder::class,

            // Replenishment
            ReplenishmentSeeder::class,

            // Maintenance / Asset
            AssetSeeder::class,
            WorkOrderSeeder::class,

            // Processing / Manufacturing
            ProcessingOrderSeeder::class,
            ProcessingOrderItemSeeder::class,
        ]);

        // Enable FK lagi
        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        $this->command?->info('✅ Database seeding completed successfully!');
    }
}
