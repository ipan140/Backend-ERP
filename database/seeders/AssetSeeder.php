<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asset;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        // Cegah duplikasi data
        if (Asset::count() > 0) return;

        Asset::insert([
            [
                'code'          => 'AST-0001',
                'name'          => 'Pompa Air 5HP',
                'location'      => 'Plant 1',
                'category'      => 'Mesin',
                'purchase_date' => now()->subYears(2),
                'value'         => 12500000,
                'status'        => 'active',
            ],
            [
                'code'          => 'AST-0002',
                'name'          => 'Conveyor Line A',
                'location'      => 'Plant 1',
                'category'      => 'Peralatan Produksi',
                'purchase_date' => now()->subYear(),
                'value'         => 48000000,
                'status'        => 'maintenance',
            ],
            [
                'code'          => 'AST-0003',
                'name'          => 'Mixer Tank',
                'location'      => 'Plant 2',
                'category'      => 'Mesin',
                'purchase_date' => now()->subYears(3),
                'value'         => 32000000,
                'status'        => 'active',
            ],
        ]);
    }
}
