<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\Warehouse;
use App\Models\Shipment;

class ShipmentSeeder extends Seeder
{
    public function run(): void
    {
        $wh = Warehouse::where('code','WH-MAIN')->first();
        if (!$wh) return;

        Shipment::updateOrCreate(['number' => 'SHP-'.date('Ymd').'-001'], [
            'direction' => 'outbound',
            'warehouse_id' => $wh->id,
            'partner_id' => null,
            'partner_type' => 'customer',
            'status' => 'draft',
            'scheduled_date' => Carbon::now()->addDays(2)->toDateString(),
        ]);
    }
}
