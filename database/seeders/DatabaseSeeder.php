<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /* =========================== 0) USER / BASE =========================== */
        $this->callIfExists(UserSeeder::class);

        /* =========================== PRODUCTION ============================== */
        if (App::environment('production')) {
            $this->command?->warn('⚠️ Production environment: skip destructive resets.');

            // HR + Accounting + SCM (ringan, urutan aman)
            $this->callManyIfExists([
                // HR
                DepartmentSeeder::class,
                JobPositionSeeder::class,
                EmployeeSeeder::class,
                ShiftSeeder::class,
                PublicHolidaySeeder::class,
                LeaveTypeSeeder::class,
                SalaryRuleSeeder::class,

                // SCM (master → stok → transaksi → maintenance)
                VendorSeeder::class,
                ItemSeeder::class,
                WarehouseSeeder::class,
                LocationSeeder::class,      // ← tambahkan lokasi (ada di project-mu)
                LotSeeder::class,           // ← lots tanpa FK (aman)
                StockLevelSeeder::class,
                StockMoveSeeder::class,

                PurchaseSeeder::class,
                PurchaseItemSeeder::class,

                ShipmentSeeder::class,
                ShipmentItemSeeder::class,
                QualityInspectionSeeder::class,
                QualityInspectionItemSeeder::class,

                ReplenishmentSeeder::class,

                AssetSeeder::class,
                EquipmentSeeder::class,
                MaintenancePlanSeeder::class,
                MaintenanceRequestSeeder::class,

                WorkOrderSeeder::class,
                WorkOrderInputSeeder::class,
                WorkOrderOutputSeeder::class,

                // Accounting
                CompanySeeder::class,
                AccountJournalSeeder::class,
                AccountSeeder::class,
                JournalSequenceSeeder::class,
                DemoMovesSeeder::class,
            ]);
            return;
        }

        /* ======================= NON-PROD: FK OFF (safe) ===================== */
        $this->disableForeignKeys();

        /* =============================== RESET =============================== */
        $this->callManyIfExists([
            ResetHRDataSeeder::class,
            ResetSalesDataSeeder::class,
            // ResetAccountingSeeder::class, // opsional
        ]);

        /* ================================ HR ================================= */
        $this->callManyIfExists([
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

        /* ============================== SALES/ERP ============================ */
        $this->callManyIfExists([
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

        /* ================================ SCM ================================ */
        $this->callManyIfExists([
            // Master data
            VendorSeeder::class,
            ItemSeeder::class,
            WarehouseSeeder::class,
            LocationSeeder::class,      // ← ada di repo-mu
            LotSeeder::class,           // ← lots tanpa FK

            // Stok ringkas & pergerakan
            StockLevelSeeder::class,
            StockMoveSeeder::class,

            // Pembelian
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
            EquipmentSeeder::class,
            MaintenancePlanSeeder::class,
            MaintenanceRequestSeeder::class,

            // Processing / Manufacturing / Work Orders
            WorkOrderSeeder::class,
            WorkOrderInputSeeder::class,
            WorkOrderOutputSeeder::class,
        ]);

        /* ============================== ACCOUNTING =========================== */
        $this->callManyIfExists([
            CompanySeeder::class,
            AccountJournalSeeder::class,
            AccountSeeder::class,
            JournalSequenceSeeder::class,
            DemoMovesSeeder::class,
        ]);

        /* ======================= NON-PROD: FK ON (back) ====================== */
        $this->enableForeignKeys();

        $this->command?->info('✅ Database seeding completed (HR + Sales + SCM + Accounting)!');
    }

    /* ============================ Helpers ============================ */

    protected function callIfExists(string $seederClass): void
    {
        if (class_exists($seederClass)) {
            $this->call($seederClass);
        } else {
            $this->command?->line("ℹ️  Skip (seeder tidak ditemukan): {$seederClass}");
        }
    }

    protected function callManyIfExists(array $seeders): void
    {
        foreach ($seeders as $seederClass) {
            $this->callIfExists($seederClass);
        }
    }

    protected function disableForeignKeys(): void
    {
        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }
        } catch (\Throwable $e) {}
    }

    protected function enableForeignKeys(): void
    {
        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        } catch (\Throwable $e) {}
    }
}
