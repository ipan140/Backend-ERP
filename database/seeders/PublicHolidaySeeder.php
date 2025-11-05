<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\PublicHoliday;

class PublicHolidaySeeder extends Seeder
{
    public function run(): void
    {
        // 🔒 Matikan FK sementara agar aman saat penghapusan massal
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // ❌ Ganti truncate dengan delete agar tidak error FK
        DB::table('public_holidays')->delete();

        // (Opsional) reset auto increment supaya ID mulai dari 1
        try { DB::unprepared('ALTER TABLE public_holidays AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}

        // 🔓 Aktifkan lagi FK
        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        // ✅ Seed data hari libur nasional
        PublicHoliday::create([
            'name'         => 'New Year',
            'date'         => '2025-01-01',
            'is_national'  => true,
            'note'         => 'Tahun Baru Masehi',
        ]);

        PublicHoliday::create([
            'name'         => 'Independence Day',
            'date'         => '2025-08-17',
            'is_national'  => true,
            'note'         => 'Hari Kemerdekaan Republik Indonesia',
        ]);

        $this->command?->info('✅ PublicHolidaySeeder selesai — 2 hari libur nasional berhasil dibuat.');
    }
}
