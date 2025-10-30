<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 0) Seed user dulu (admin, hr, dll) — password 12345678 (plain) sesuai UserSeeder kamu
        $this->call(UserSeeder::class);

        // Jika production, skip reset destruktif
        if (app()->environment('production')) {
            $this->command?->warn('⚠️ Production environment: skip reset data. Hanya menjalankan seeders dasar.');

            // Jalankan seeders dasar yang aman (tanpa reset)
            $this->call([
                DepartmentSeeder::class,
                JobPositionSeeder::class,
                EmployeeSeeder::class,
                ShiftSeeder::class,
                PublicHolidaySeeder::class,
                LeaveTypeSeeder::class,
                SalaryRuleSeeder::class,
                // Tambahkan lainnya bila perlu untuk prod
            ]);

            return;
        }

        // Dev / staging: reset & isi dummy lengkap
        // OFF FK global (pengaman tambahan; di reset seeder juga sudah aman)
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // 1) RESET per modul (anak → induk)
        $this->call([
            ResetHRDataSeeder::class,
            ResetSalesDataSeeder::class,
        ]);

        // 2) SEED HR (urutan aman)
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

        // 3) SEED Sales/ERP
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

        // ON FK lagi
        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}
    }
}
