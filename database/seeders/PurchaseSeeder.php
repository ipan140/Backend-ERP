<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Purchase;
use App\Models\Vendor;
use Carbon\Carbon;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::now()->toDateString();
        $v1 = Vendor::where('code', 'VND-001')->first();
        $v2 = Vendor::where('code', 'VND-002')->first();

        Purchase::updateOrCreate(['number' => 'PO-'.date('Ymd').'-001'], [
            'vendor_id' => optional($v1)->id,
            'order_date'=> $today,
            'status'    => 'confirmed',
            'total'     => 0,
        ]);

        Purchase::updateOrCreate(['number' => 'PO-'.date('Ymd').'-002'], [
            'vendor_id' => optional($v2)->id,
            'order_date'=> $today,
            'status'    => 'confirmed',
            'total'     => 0,
        ]);
    }
}
