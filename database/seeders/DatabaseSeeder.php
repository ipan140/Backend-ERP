<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /* ============================================================== 
         |  0) USER / BASE SEEDER 
         ============================================================== */
        $this->call(UserSeeder::class);

        /* ============================================================== 
         |  PRODUCTION MODE (skip resets)
         ============================================================== */
        if (app()->environment('production')) {
            $this->command?->warn('⚠️ Production environment: skip destructive resets.');

            $this->call([
                // HR (aman di production)
                DepartmentSeeder::class,
                JobPositionSeeder::class,
                EmployeeSeeder::class,
                ShiftSeeder::class,
                PublicHolidaySeeder::class,
                LeaveTypeSeeder::class,
                SalaryRuleSeeder::class,

                // ✅ Accounting (dipanggil satu-per-satu)
                CompanySeeder::class,
                AccountJournalSeeder::class,
                AccountSeeder::class,
                JournalSequenceSeeder::class,
                DemoMovesSeeder::class,
            ]);
            return;
        }

        /* ============================================================== 
         |  Disable foreign key temporarily 
         ============================================================== */
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        /* ============================================================== 
         |  RESET SEEDERS 
         ============================================================== */
        $this->call([
            ResetHRDataSeeder::class,
            ResetSalesDataSeeder::class,
            // ResetAccountingSeeder::class, // (opsional) tambahkan bila sudah ada
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

        /* ============================================================== 
         |  ACCOUNTING MODULE (💰 Opsi B: panggil satu-per-satu)
         ============================================================== */
        $this->call([
            CompanySeeder::class,
            AccountJournalSeeder::class,
            AccountSeeder::class,
            JournalSequenceSeeder::class,
            DemoMovesSeeder::class,
        ]);

        /* ============================================================== 
         |  Re-enable foreign key checks 
         ============================================================== */
        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        $this->command?->info('✅ Database seeding completed successfully (HR + Sales + SCM + Accounting)!');
    }
}
