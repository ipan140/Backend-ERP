<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Warehouse;
use App\Models\Location;
use App\Models\Item;
use App\Models\Lot;
use App\Models\StockMove;
use App\Models\StockLevel;
use App\Models\Purchase;

class StockMoveSeeder extends Seeder
{
    public function run(): void
    {
        $wh = Warehouse::where('code','WH-MAIN')->first();
        if (!$wh) return;

        $rec = Location::where('warehouse_id',$wh->id)->where('code','REC-001')->first();
        $stk = Location::where('warehouse_id',$wh->id)->where('code','STK-001')->first();
        if (!$rec || !$stk) return;

        $urea = Item::where('sku','ITM-UREA')->first();
        $npk  = Item::where('sku','ITM-NPK')->first();
        $lotUrea = Lot::where('number','LIKE','LOT-UREA-%')->first();
        $lotNpk  = Lot::where('number','LIKE','LOT-NPK-%')->first();
        $po1 = Purchase::where('number','PO-'.date('Ymd').'-001')->first();
        $po2 = Purchase::where('number','PO-'.date('Ymd').'-002')->first();

        // Inbound ke Receiving
        if ($urea && $lotUrea && $rec) {
            StockMove::firstOrCreate([
                'item_id' => $urea->id, 'from_location_id' => null, 'to_location_id' => $rec->id, 'lot_id' => $lotUrea->id,
                'qty' => 1000, 'uom' => 'kg', 'state' => 'done', 'ref' => optional($po1)->number ?? 'PO-IN',
            ]);
        }
        if ($npk && $lotNpk && $rec) {
            StockMove::firstOrCreate([
                'item_id' => $npk->id, 'from_location_id' => null, 'to_location_id' => $rec->id, 'lot_id' => $lotNpk->id,
                'qty' => 800, 'uom' => 'kg', 'state' => 'done', 'ref' => optional($po2)->number ?? 'PO-IN',
            ]);
        }

        // Transfer Receiving → Stock + update StockLevel
        $moves = [
            [$urea, $lotUrea, 1000, 'kg'],
            [$npk,  $lotNpk,   800, 'kg'],
        ];

        foreach ($moves as [$item, $lot, $qty, $uom]) {
            if (!$item || !$lot) continue;

            StockMove::firstOrCreate([
                'item_id' => $item->id,
                'from_location_id' => $rec->id,
                'to_location_id'   => $stk->id,
                'lot_id' => $lot->id,
                'qty' => $qty,
                'uom' => $uom,
                'state' => 'done',
                'ref' => 'RCV-'.Str::upper(Str::random(6)),
            ]);

            $sl = StockLevel::firstOrCreate(
                ['item_id' => $item->id, 'location_id' => $stk->id],
                ['qty' => 0]
            );
            $sl->increment('qty', $qty);
        }
    }
}
