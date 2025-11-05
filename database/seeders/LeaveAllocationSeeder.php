<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{LeaveAllocation, Employee, LeaveType};

class LeaveAllocationSeeder extends Seeder
{
    public function run(): void
    {
        // 🔒 Matikan sementara FK supaya aman saat hapus massal
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // ❌ Jangan pakai truncate — ganti dengan delete agar aman dari FK
        DB::table('leave_allocations')->delete();
        // (Opsional) reset auto increment
        try { DB::unprepared('ALTER TABLE leave_allocations AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}

        // 🔓 Nyalakan lagi FK
        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        // Ambil referensi karyawan & tipe cuti
        $emp = Employee::where('emp_no', 'EMP002')->first();
        $al  = LeaveType::where('code', 'AL')->first();
        $sl  = LeaveType::where('code', 'SL')->first();

        // Cek apakah data referensi tersedia
        if (!$emp || !$al || !$sl) {
            $this->command?->warn('⚠️ LeaveAllocationSeeder: Data employee/leave type belum tersedia. Jalankan EmployeeSeeder & LeaveTypeSeeder dulu.');
            return;
        }

        // ✅ Tambahkan alokasi cuti tahunan & sakit
        LeaveAllocation::create([
            'employee_id'     => $emp->id,
            'leave_type_id'   => $al->id,
            'year'            => 2025,
            'allocated_days'  => 12,
            'used_days'       => 0,
        ]);

        LeaveAllocation::create([
            'employee_id'     => $emp->id,
            'leave_type_id'   => $sl->id,
            'year'            => 2025,
            'allocated_days'  => 10,
            'used_days'       => 1,
        ]);

        $this->command?->info('✅ LeaveAllocationSeeder selesai — 2 alokasi cuti berhasil dibuat.');
    }
}
