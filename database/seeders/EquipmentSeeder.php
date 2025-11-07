<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asset;
use App\Models\Equipment;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $asset = Asset::where('code','AST-PMP-01')->first();
        if (!$asset) return;

        Equipment::updateOrCreate(
            ['asset_id' => $asset->id, 'name' => 'Pump A'],
            ['serial' => 'PMP-001', 'category' => 'Pump', 'active' => true]
        );
    }
}
