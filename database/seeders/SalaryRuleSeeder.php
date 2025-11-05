<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SalaryRule;

class SalaryRuleSeeder extends Seeder
{
    public function run(): void
    {
        // 🔒 Nonaktifkan foreign key checks sementara (biar aman)
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // ❌ Ganti truncate dengan delete agar tidak error 1701
        DB::table('salary_rules')->delete();

        // (Opsional) Reset auto increment supaya urutan ID rapi
        try { DB::unprepared('ALTER TABLE salary_rules AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}

        // 🔓 Aktifkan kembali foreign key
        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        // ✅ Insert data master aturan gaji
        SalaryRule::create([
            'code'          => 'ALW_MEAL',
            'name'          => 'Meal Allowance',
            'type'          => 'earning',
            'amount_type'   => 'fixed',
            'fixed_amount'  => 300000,
            'active'        => true,
            'description'   => 'Uang makan bulanan',
        ]);

        SalaryRule::create([
            'code'          => 'DED_BPJS',
            'name'          => 'BPJS Kesehatan',
            'type'          => 'deduction',
            'amount_type'   => 'fixed',
            'fixed_amount'  => 150000,
            'active'        => true,
            'description'   => 'Potongan iuran BPJS',
        ]);

        SalaryRule::create([
            'code'          => 'BONUS',
            'name'          => 'Bonus % Basic',
            'type'          => 'earning',
            'amount_type'   => 'percent',
            'percent'       => 10,
            'percent_base'  => 'basic',
            'active'        => true,
            'description'   => 'Bonus 10% dari gaji pokok',
        ]);

        $this->command?->info('✅ SalaryRuleSeeder selesai — 3 aturan gaji berhasil ditambahkan.');
    }
}
