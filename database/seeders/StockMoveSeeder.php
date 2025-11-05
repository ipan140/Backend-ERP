<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StockMove;
use App\Models\Item;
use App\Models\Warehouse;

class StockMoveSeeder extends Seeder
{
    public function run(): void
    {
        if (Item::count() === 0 || Warehouse::count() < 2) {
            $this->command?->warn('⚠️ Skipping StockMoveSeeder: need at least 2 warehouses and some items.');
            return;
        }

        if (StockMove::count() > 0) return;

        $warehouses = Warehouse::take(2)->get();
        $item = Item::inRandomOrder()->first();

        $moves = [
            [
                'item_id' => $item->id,
                'from_warehouse_id' => $warehouses[0]->id,
                'to_warehouse_id'   => $warehouses[1]->id,
                'qty' => 20,
                'status' => 'done',
                'reference_type' => 'shipment',
                'reference_id' => null,
            ],
            [
                'item_id' => $item->id,
                'from_warehouse_id' => $warehouses[1]->id,
                'to_warehouse_id'   => $warehouses[0]->id,
                'qty' => 10,
                'status' => 'draft',
                'reference_type' => 'replenishment',
                'reference_id' => null,
            ],
        ];

        foreach ($moves as $m) {
            StockMove::create($m);
        }
    }
}
