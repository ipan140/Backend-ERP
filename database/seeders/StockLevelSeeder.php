<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use App\Models\Location;
use App\Models\Item;
use App\Models\StockLevel;

class StockLevelSeeder extends Seeder
{
    public function run(): void
    {
        $stockCode = 'STK-001';
        $items = Item::get();
        foreach (Warehouse::get() as $wh) {
            $loc = Location::where('warehouse_id', $wh->id)->where('code', $stockCode)->first();
            if (!$loc) continue;
            foreach ($items as $it) {
                StockLevel::firstOrCreate(
                    ['item_id' => $it->id, 'location_id' => $loc->id],
                    ['qty' => 0]
                );
            }
        }
    }
}
