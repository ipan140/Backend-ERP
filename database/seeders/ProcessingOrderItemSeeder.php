<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProcessingOrder;
use App\Models\ProcessingOrderItem;
use App\Models\Item;

class ProcessingOrderItemSeeder extends Seeder
{
    public function run(): void
    {
        $orders = ProcessingOrder::get();
        if ($orders->isEmpty()) return;

        if (Item::count() < 3) return; // minimal 1 FG + 2 bahan baku

        foreach ($orders as $mo) {
            // Pastikan MO punya finished item & qty
            if (!$mo->finished_item_id) {
                $fg = Item::inRandomOrder()->first();
                $mo->finished_item_id = $fg->id;
                if (!$mo->qty || $mo->qty == 0) {
                    $mo->qty = rand(20, 120);
                }
                $mo->save();
            }

            // INPUT: 2 bahan baku random (beda dengan FG)
            $inputs = Item::where('id', '!=', $mo->finished_item_id)
                          ->inRandomOrder()->take(2)->get();

            foreach ($inputs as $bb) {
                ProcessingOrderItem::create([
                    'processing_order_id' => $mo->id,
                    'item_id'             => $bb->id,
                    'qty'                 => rand(5, 15),
                    'role'                => 'input',
                    'notes'               => null,
                ]);
            }

            // OUTPUT: finished good (menggunakan finished_item_id & qty MO)
            ProcessingOrderItem::create([
                'processing_order_id' => $mo->id,
                'item_id'             => $mo->finished_item_id,
                'qty'                 => $mo->qty ?: 50,
                'role'                => 'output',
                'notes'               => 'Hasil produksi',
            ]);
        }
    }
}
