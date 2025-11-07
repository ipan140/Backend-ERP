<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use App\Models\Item;
use App\Models\Replenishment;

class ReplenishmentSeeder extends Seeder
{
    public function run(): void
    {
        $wh = Warehouse::where('code','WH-MAIN')->first();
        if (!$wh) return;

        $rows = [
            ['sku' => 'ITM-UREA', 'min' => 500, 'max' => 1500, 'reorder' => 1000],
            ['sku' => 'ITM-NPK',  'min' => 300, 'max' => 1200, 'reorder' => 800],
        ];
        foreach ($rows as $r) {
            $item = Item::where('sku',$r['sku'])->first();
            if (!$item) continue;
            Replenishment::updateOrCreate(
                ['item_id' => $item->id, 'warehouse_id' => $wh->id],
                ['min_qty' => $r['min'], 'max_qty' => $r['max'], 'reorder_qty' => $r['reorder'], 'active' => true]
            );
        }
    }
}
