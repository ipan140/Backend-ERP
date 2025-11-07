<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'WH-MAIN', 'name' => 'Main Warehouse', 'address' => 'Jl. Industri No.1', 'active' => true],
            ['code' => 'WH-SEC',  'name' => 'Secondary WH',   'address' => 'Jl. Pergudangan',   'active' => true],
        ];
        foreach ($rows as $r) Warehouse::updateOrCreate(['code' => $r['code']], $r);
    }
}
