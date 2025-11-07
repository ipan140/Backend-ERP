<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asset;
use Carbon\Carbon;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        Asset::updateOrCreate(['code' => 'AST-PMP-01'], [
            'name' => 'Pompa Irigasi 7.5kW',
            'category' => 'Pump',
            'acquired_at' => Carbon::now()->subYear()->toDateString(),
            'serial' => 'PM-7K5-001',
            'active' => true,
        ]);
    }
}
