<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{SalaryStructure, SalaryRule};

class SalaryStructureSeeder extends Seeder
{
    public function run(): void
    {
        // Matikan FK checks biar aman truncate berurutan
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // 1) Truncate pivot dulu, baru parent
        DB::table('salary_structure_rules')->truncate();
        SalaryStructure::truncate();

        // 2) Pastikan rules tersedia (kalau belum, buat)
        $meal = SalaryRule::firstOrCreate(
            ['code' => 'ALW_MEAL'],
            [
                'name' => 'Meal Allowance',
                'type' => 'earning',
                'amount_type' => 'fixed',
                'fixed_amount' => 300000,
                'active' => true,
            ]
        );

        $bpjs = SalaryRule::firstOrCreate(
            ['code' => 'DED_BPJS'],
            [
                'name' => 'BPJS Kesehatan',
                'type' => 'deduction',
                'amount_type' => 'fixed',
                'fixed_amount' => 150000,
                'active' => true,
            ]
        );

        $bonus = SalaryRule::firstOrCreate(
            ['code' => 'BONUS'],
            [
                'name' => 'Bonus % Basic',
                'type' => 'earning',
                'amount_type' => 'percent',
                'percent' => 10,
                'percent_base' => 'basic',
                'active' => true,
            ]
        );

        // 3) Buat struktur
        $str = SalaryStructure::create([
            'code'        => 'STR_STD',
            'name'        => 'Standard Structure',
            'base_basic'  => 5000000,
            'active'      => true,
            'description' => 'Default structure',
        ]);

        // 4) Attach rules ke struktur (dengan seq)
        //    pakai sync tanpa detach = false kalau mau gabung
        $str->rules()->attach([
            $meal->id  => ['seq' => 1],
            $bonus->id => ['seq' => 2],
            $bpjs->id  => ['seq' => 3],
        ]);

        // Aktifkan kembali FK checks
        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}
    }
}
