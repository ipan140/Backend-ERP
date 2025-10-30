<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{Leave, Employee, LeaveType};

class LeaveSeeder extends Seeder
{
    public function run(): void
    {
        // Matikan FK sementara (hindari error saat wipe)
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // Bersihkan data tanpa truncate (anti-1701)
        DB::table('leaves')->delete();
        // Reset auto increment (opsional)
        try { DB::unprepared('ALTER TABLE leaves AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}

        // Nyalakan FK lagi
        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        // Referensi yang dibutuhkan
        $emp = Employee::where('emp_no', 'EMP002')->first();
        $al  = LeaveType::where('code', 'AL')->first(); // Annual Leave

        if (!$emp || !$al) {
            $this->command?->warn('⚠️ LeaveSeeder: Employee EMP002 atau LeaveType AL belum ada. Jalankan EmployeeSeeder & LeaveTypeSeeder dulu.');
            return;
        }

        // Seed 1 pengajuan cuti disetujui
        Leave::create([
            'employee_id'   => $emp->id,
            'leave_type_id' => $al->id,
            'date_start'    => '2025-06-12',
            'date_end'      => '2025-06-14',
            'days'          => 3,
            'reason'        => 'Family vacation',
            'status'        => 'approved',
            'approver_id'   => $emp->manager_id, // boleh null jika tidak ada manager
            'approved_at'   => now(),
        ]);

        $this->command?->info('✅ LeaveSeeder selesai — 1 leave approved dibuat untuk EMP002 (AL, 3 hari).');
    }
}
