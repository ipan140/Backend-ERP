<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StockLevel;

class StockLevelSeeder extends Seeder
{
    public function run(): void
    {
        if (StockLevel::count() === 0) {
            StockLevel::insert([
                ['warehouse_id' => 1, 'item_id' => 1, 'quantity' => 100],
                ['warehouse_id' => 1, 'item_id' => 2, 'quantity' => 50],
                ['warehouse_id' => 2, 'item_id' => 3, 'quantity' => 75],
            ]);
        }
    }
}
