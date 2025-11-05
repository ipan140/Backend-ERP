<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Replenishment;
use App\Models\Item;
use App\Models\Warehouse;

class ReplenishmentSeeder extends Seeder
{
    public function run(): void
    {
        if (Item::count() === 0 || Warehouse::count() === 0) return;
        if (Replenishment::count() > 0) return;

        $i1 = Item::inRandomOrder()->first();
        $i2 = Item::inRandomOrder()->where('id','!=',$i1->id)->first();
        $wh = Warehouse::inRandomOrder()->first();

        $rows = [
            [
                'item_id'      => $i1->id,
                'warehouse_id' => $wh->id,
                'method'       => 'minmax',
                'min_qty'      => 20,
                'max_qty'      => 100,
                'reorder_qty'  => 40,
                'status'       => 'active',
            ],
            [
                'item_id'      => $i2->id,
                'warehouse_id' => $wh->id,
                'method'       => 'minmax',
                'min_qty'      => 10,
                'max_qty'      => 60,
                'reorder_qty'  => 30,
                'status'       => 'active',
            ],
        ];
        foreach ($rows as $r) Replenishment::create($r);
    }
}
