<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Shift;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        // Matikan FK sementara (jaga-jaga jika direferensikan tabel lain)
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // Bersihkan isi tabel tanpa truncate (anti-1701)
        DB::table('shifts')->delete();

        // (Opsional) reset auto increment biar rapi
        try { DB::unprepared('ALTER TABLE shifts AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}

        // Nyalakan lagi FK
        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        // Seed data shift
        Shift::create([
            'code'          => 'SHIFT1',
            'name'          => 'Regular Day',
            'time_start'    => '08:00',
            'time_end'      => '17:00',
            'break_minutes' => 60,
            'active'        => true,
        ]);

        Shift::create([
            'code'          => 'SHIFT2',
            'name'          => 'Night Shift',
            'time_start'    => '21:00',
            'time_end'      => '06:00',
            'break_minutes' => 60,
            'is_night'      => true,
            'active'        => true,
        ]);

        $this->command?->info('✅ ShiftSeeder selesai — SHIFT1 & SHIFT2 dibuat.');
    }
}
