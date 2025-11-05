<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{JobPosition, Department};

class JobPositionSeeder extends Seeder
{
    public function run(): void
    {
        // 🔒 Matikan FK sementara agar aman saat penghapusan massal
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // ❌ Ganti truncate dengan delete agar tidak error FK
        DB::table('job_positions')->delete();

        // (Opsional) reset auto increment biar rapi
        try { DB::unprepared('ALTER TABLE job_positions AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}

        // 🔓 Nyalakan lagi FK
        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        // Ambil referensi department
        $hr = Department::where('code', 'HR')->first();
        $it = Department::where('code', 'IT')->first();

        // Validasi biar nggak error kalau department belum ada
        if (!$hr || !$it) {
            $this->command?->warn('⚠️ JobPositionSeeder: Department HR/IT belum ada. Jalankan DepartmentSeeder dulu.');
            return;
        }

        // ✅ Seed data posisi jabatan
        JobPosition::create([
            'code'           => 'HRMGR',
            'name'           => 'HR Manager',
            'department_id'  => $hr->id,
            'active'         => true,
        ]);

        JobPosition::create([
            'code'           => 'HRSTA',
            'name'           => 'HR Staff',
            'department_id'  => $hr->id,
            'active'         => true,
        ]);

        JobPosition::create([
            'code'           => 'ITMGR',
            'name'           => 'IT Manager',
            'department_id'  => $it->id,
            'active'         => true,
        ]);

        JobPosition::create([
            'code'           => 'ITDEV',
            'name'           => 'Software Engineer',
            'department_id'  => $it->id,
            'active'         => true,
        ]);

        $this->command?->info('✅ JobPositionSeeder selesai — 4 posisi berhasil dibuat.');
    }
}
