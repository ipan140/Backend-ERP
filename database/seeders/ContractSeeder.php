<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{Contract, Employee};

class ContractSeeder extends Seeder
{
    public function run(): void
    {
        // 🔒 Matikan FK sementara supaya aman saat penghapusan massal
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // ❌ Jangan truncate, gunakan delete agar aman dari FK constraint
        DB::table('contracts')->delete();
        // (opsional) reset auto increment
        try { DB::unprepared('ALTER TABLE contracts AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}

        // 🔓 Nyalakan lagi FK
        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        // Ambil karyawan berdasarkan emp_no
        $emp1 = Employee::where('emp_no', 'EMP001')->first();
        $emp4 = Employee::where('emp_no', 'EMP004')->first();

        // Validasi jika employee belum ada
        if (!$emp1 || !$emp4) {
            $this->command?->warn('⚠️ ContractSeeder: Data employee belum tersedia. Jalankan EmployeeSeeder dulu.');
            return;
        }

        // Seed data kontrak karyawan
        Contract::create([
            'employee_id'   => $emp1->id,
            'contract_no'   => 'CT-2025-0001',
            'type'          => 'permanent',
            'start_date'    => '2025-01-01',
            'basic_salary'  => 7_000_000,
        ]);

        Contract::create([
            'employee_id'   => $emp4->id,
            'contract_no'   => 'CT-2025-0002',
            'type'          => 'contract',
            'start_date'    => '2025-01-10',
            'end_date'      => '2025-12-31',
            'basic_salary'  => 5_500_000,
        ]);

        $this->command?->info('✅ ContractSeeder selesai — 2 kontrak berhasil dibuat.');
    }
}
