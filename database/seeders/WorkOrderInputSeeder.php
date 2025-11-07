<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkOrder;
use App\Models\Item;
use App\Models\Lot;
use App\Models\WorkOrderInput;

class WorkOrderInputSeeder extends Seeder
{
    public function run(): void
    {
        $wo   = WorkOrder::where('number','WO-'.date('Ymd').'-001')->first();
        $urea = Item::where('sku','ITM-UREA')->first();
        $npk  = Item::where('sku','ITM-NPK')->first();
        $lotUrea = Lot::where('number','LIKE','LOT-UREA-%')->first();
        $lotNpk  = Lot::where('number','LIKE','LOT-NPK-%')->first();

        if ($wo && $urea && $lotUrea) {
            WorkOrderInput::updateOrCreate(
                ['work_order_id' => $wo->id, 'item_id' => $urea->id, 'lot_id' => $lotUrea->id],
                ['qty' => 100, 'uom' => 'kg']
            );
        }
        if ($wo && $npk && $lotNpk) {
            WorkOrderInput::updateOrCreate(
                ['work_order_id' => $wo->id, 'item_id' => $npk->id, 'lot_id' => $lotNpk->id],
                ['qty' => 80, 'uom' => 'kg']
            );
        }
    }
}
