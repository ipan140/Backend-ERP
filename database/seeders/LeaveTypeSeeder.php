<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\LeaveType;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Matikan FK sementara agar aman saat hapus massal
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // Bersihkan tabel tanpa truncate (anti-1701)
        DB::table('leave_types')->delete();
        // Reset auto increment (opsional)
        try { DB::unprepared('ALTER TABLE leave_types AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}

        // Hidupkan FK kembali
        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        // ✅ Seed data master leave types
        LeaveType::create([
            'code'          => 'AL',
            'name'          => 'Annual Leave',
            'default_days'  => 12,
            'active'        => true,
        ]);

        LeaveType::create([
            'code'          => 'SL',
            'name'          => 'Sick Leave',
            'default_days'  => 10,
            'active'        => true,
        ]);

        $this->command?->info('✅ LeaveTypeSeeder selesai — 2 tipe cuti berhasil dibuat (AL & SL).');
    }
}
