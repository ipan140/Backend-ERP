<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shipment;
use App\Models\Item;
use App\Models\ShipmentItem;

class ShipmentItemSeeder extends Seeder
{
    public function run(): void
    {
        $shp = Shipment::where('number','SHP-'.date('Ymd').'-001')->first();
        $urea = Item::where('sku','ITM-UREA')->first();

        if ($shp && $urea) {
            ShipmentItem::updateOrCreate(
                ['shipment_id' => $shp->id, 'item_id' => $urea->id, 'lot_id' => null],
                ['qty' => 200, 'uom' => 'kg']
            );
        }
    }
}
