<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Item;

class PurchaseItemSeeder extends Seeder
{
    public function run(): void
    {
        $po1 = Purchase::where('number', 'PO-'.date('Ymd').'-001')->first();
        $po2 = Purchase::where('number', 'PO-'.date('Ymd').'-002')->first();

        $urea = Item::where('sku','ITM-UREA')->first();
        $npk  = Item::where('sku','ITM-NPK')->first();
        $bag  = Item::where('sku','ITM-BAG')->first();

        $rows = [
            ['purchase' => $po1, 'item' => $urea, 'qty' => 1000, 'uom' => 'kg',  'price' => 6300],
            ['purchase' => $po1, 'item' => $bag,  'qty' => 100,  'uom' => 'pcs', 'price' => 2400],
            ['purchase' => $po2, 'item' => $npk,  'qty' => 800,  'uom' => 'kg',  'price' => 7900],
        ];

        foreach ($rows as $r) {
            if (!$r['purchase'] || !$r['item']) continue;
            $subtotal = $r['qty'] * $r['price'];
            PurchaseItem::updateOrCreate(
                ['purchase_id' => $r['purchase']->id, 'item_id' => $r['item']->id],
                ['qty' => $r['qty'], 'uom' => $r['uom'], 'price' => $r['price'], 'subtotal' => $subtotal]
            );
        }

        // Perbarui total PO
        foreach ([$po1, $po2] as $p) {
            if (!$p) continue;
            $sum = PurchaseItem::where('purchase_id', $p->id)->sum('subtotal');
            $p->update(['total' => $sum]);
        }
    }
}
