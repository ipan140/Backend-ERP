<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{Employee, Department, JobPosition};

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // 🔒 Matikan sementara FK supaya aman saat delete massal
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // ❌ Hapus semua data (bukan truncate)
        DB::table('employees')->delete();

        // (Opsional) reset auto increment biar ID mulai dari 1 lagi
        try { DB::unprepared('ALTER TABLE employees AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}

        // 🔓 Hidupkan FK lagi
        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        // Ambil referensi departemen & posisi
        $hr     = Department::where('code', 'HR')->first();
        $it     = Department::where('code', 'IT')->first();
        $hrmgr  = JobPosition::where('code', 'HRMGR')->first();
        $hrsta  = JobPosition::where('code', 'HRSTA')->first();
        $itmgr  = JobPosition::where('code', 'ITMGR')->first();
        $itdev  = JobPosition::where('code', 'ITDEV')->first();

        // Validasi data referensi sebelum membuat employee
        if (!$hr || !$it || !$hrmgr || !$hrsta || !$itmgr || !$itdev) {
            $this->command?->warn('⚠️ EmployeeSeeder: pastikan DepartmentSeeder & JobPositionSeeder sudah dijalankan.');
            return;
        }

        // 👤 Buat data karyawan
        $e1 = Employee::create([
            'emp_no'           => 'EMP001',
            'first_name'       => 'John',
            'last_name'        => 'Doe',
            'full_name'        => 'John Doe',
            'email'            => 'john@example.com',
            'department_id'    => $hr->id,
            'job_position_id'  => $hrmgr->id,
            'hire_date'        => '2025-01-01',
            'employment_type'  => 'permanent',
            'status'           => 'active',
        ]);

        $e2 = Employee::create([
            'emp_no'           => 'EMP002',
            'first_name'       => 'Jane',
            'last_name'        => 'Smith',
            'full_name'        => 'Jane Smith',
            'email'            => 'jane@example.com',
            'department_id'    => $hr->id,
            'job_position_id'  => $hrsta->id,
            'manager_id'       => $e1->id,
            'hire_date'        => '2025-01-05',
            'employment_type'  => 'permanent',
            'status'           => 'active',
        ]);

        $e3 = Employee::create([
            'emp_no'           => 'EMP003',
            'first_name'       => 'Budi',
            'last_name'        => 'Saputra',
            'full_name'        => 'Budi Saputra',
            'email'            => 'budi@example.com',
            'department_id'    => $it->id,
            'job_position_id'  => $itmgr->id,
            'hire_date'        => '2025-01-03',
            'employment_type'  => 'permanent',
            'status'           => 'active',
        ]);

        Employee::create([
            'emp_no'           => 'EMP004',
            'first_name'       => 'Siti',
            'last_name'        => 'Ayu',
            'full_name'        => 'Siti Ayu',
            'email'            => 'siti@example.com',
            'department_id'    => $it->id,
            'job_position_id'  => $itdev->id,
            'manager_id'       => $e3->id,
            'hire_date'        => '2025-01-10',
            'employment_type'  => 'contract',
            'status'           => 'active',
        ]);

        $this->command?->info('✅ EmployeeSeeder selesai — 4 karyawan berhasil dibuat.');
    }
}
