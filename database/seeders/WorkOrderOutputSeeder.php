<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkOrder;
use App\Models\Item;
use App\Models\WorkOrderOutput;

class WorkOrderOutputSeeder extends Seeder
{
    public function run(): void
    {
        $wo  = WorkOrder::where('number','WO-'.date('Ymd').'-001')->first();
        $npk = Item::where('sku','ITM-NPK')->first();

        if ($wo && $npk) {
            WorkOrderOutput::updateOrCreate(
                ['work_order_id' => $wo->id, 'item_id' => $npk->id, 'lot_id' => null],
                ['qty_plan' => 175, 'qty_actual' => 0, 'uom' => 'kg']
            );
        }
    }
}
