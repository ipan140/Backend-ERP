<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseItem;
use App\Models\Purchase;
use App\Models\Item;

class PurchaseItemSeeder extends Seeder
{
    public function run(): void
    {
        if (Purchase::count() === 0 || Item::count() === 0) {
            $this->command?->warn('⚠️ Skipping PurchaseItemSeeder: need purchases and items.');
            return;
        }

        if (PurchaseItem::count() > 0) return;

        $items = Item::all();

        foreach (Purchase::all() as $purchase) {
            $pick = $items->random(rand(2, 5));
            foreach ($pick as $item) {
                $qty = rand(5, 30);
                $price = $item->price ?? rand(20000, 150000);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'item_id'     => $item->id,
                    'qty'         => $qty,
                    'price'       => $price,
                    'subtotal'    => $qty * $price,
                ]);
            }
        }
    }
}
