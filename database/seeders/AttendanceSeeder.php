<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{Attendance, Employee, Shift};
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // Matikan FK sementara (jaga-jaga kalau Attendance direferensikan oleh tabel lain)
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // Hapus data tanpa truncate → aman untuk tabel dengan foreign key
        DB::table('attendances')->delete();
        // Reset auto increment (opsional)
        try { DB::unprepared('ALTER TABLE attendances AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}

        // Hidupkan FK kembali
        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        // Ambil data employee & shift untuk digunakan
        $emp   = Employee::where('emp_no', 'EMP002')->first();
        $shift = Shift::where('code', 'SHIFT1')->first();

        if (!$emp || !$shift) {
            $this->command?->warn('⚠️ AttendanceSeeder: Employee atau Shift belum ada. Jalankan EmployeeSeeder & ShiftSeeder dulu.');
            return;
        }

        // Tambahkan 3 hari hadir
        for ($i = 1; $i <= 3; $i++) {
            $in  = Carbon::parse("2025-06-0{$i} 08:03:00");
            $out = Carbon::parse("2025-06-0{$i} 17:02:00");
            Attendance::create([
                'employee_id'      => $emp->id,
                'shift_id'         => $shift->id,
                'check_in'         => $in,
                'check_out'        => $out,
                'work_minutes'     => 9 * 60,
                'late_minutes'     => 3,
                'overtime_minutes' => 2,
                'status'           => 'present',
                'note'             => 'Auto seed',
            ]);
        }

        $this->command?->info('✅ AttendanceSeeder selesai — 3 record dummy berhasil dibuat.');
    }
}
