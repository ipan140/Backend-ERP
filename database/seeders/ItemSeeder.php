<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        if (Item::count() === 0) {
            Item::insert([
                [
                    'sku' => 'ITM-001',
                    'name' => 'Pupuk NPK 50kg',
                    'unit' => 'kg',
                    'price' => 250000,
                    'description' => 'Pupuk NPK 15-15-15 untuk tanaman hortikultura',
                ],
                [
                    'sku' => 'ITM-002',
                    'name' => 'Bibit Cabai Merah',
                    'unit' => 'pack',
                    'price' => 50000,
                    'description' => 'Bibit unggul cabai merah tahan penyakit',
                ],
                [
                    'sku' => 'ITM-003',
                    'name' => 'Pestisida Cair 1L',
                    'unit' => 'liter',
                    'price' => 85000,
                    'description' => 'Pestisida untuk pengendalian hama daun',
                ],
            ]);
        }
    }
}
