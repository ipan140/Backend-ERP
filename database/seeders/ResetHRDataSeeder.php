<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResetHRDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('⚠️ Skip: ResetHRDataSeeder on production.');
            return;
        }

        // Matikan FK supaya aman saat wipe
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        /**
         * URUTAN PASTI (CHILD → PARENT):
         * - payslip_lines → payslips
         * - salary_structure_rules → salary_structures → salary_rules
         * - attendances, contracts, leaves, leave_allocations → leave_types
         * - public_holidays, shifts (tak berelasi ke dept/emp)
         * - employees → job_positions → departments (PALING TERAKHIR)
         *
         * Catatan:
         * - `employees.department_id` biasanya RESTRICT → employees harus dibersihkan dulu sebelum departments.
         * - `departments.parent_id` sebaiknya ON DELETE SET NULL (nullOnDelete), aman untuk delete massal.
         */

        $tables = [
            // Payroll
            'payslip_lines',
            'payslips',
            'salary_structure_rules',
            'salary_structures',
            'salary_rules',

            // Time & Leave
            'attendances',
            'contracts',
            'leaves',
            'leave_allocations',
            'leave_types',

            // Lain-lain yang tidak bergantung langsung
            'public_holidays',
            'shifts',

            // Master (urut paling akhir)
            'employees',      // HARUS SEBELUM job_positions & departments
            'job_positions',  // HARUS SEBELUM departments
            'departments',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                // Pakai delete() agar anti 1701. Jika ingin cepat, bisa truncate di blok FK off ini.
                DB::table($table)->delete();
                // Kalau mau truncate (opsional):
                // DB::table($table)->truncate();
            }
        }

        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        $this->command?->info('✅ HR data reset selesai (anak → induk, anti-1701).');
    }
}
