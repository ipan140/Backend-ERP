<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Shipment;
use App\Models\Vendor;

class ShipmentSeeder extends Seeder
{
    public function run(): void
    {
        // minimal vendor
        if (Vendor::count() === 0) {
            Vendor::create(['code'=>'SUP-001','name'=>'PT Agro Jaya']);
        }

        if (Shipment::count() > 0) return;

        $vendorId = Vendor::inRandomOrder()->value('id');
        $today = now()->format('Ymd');

        $rows = [
            ['number'=>"SHP-$today-0001",'vendor_id'=>$vendorId,'date'=>now()->subDays(4),'status'=>'in_transit'],
            ['number'=>"SHP-$today-0002",'vendor_id'=>$vendorId,'date'=>now()->subDays(2),'status'=>'delivered'],
            ['number'=>"SHP-$today-0003",'vendor_id'=>$vendorId,'date'=>now(),'status'=>'draft'],
        ];

        foreach ($rows as $r) Shipment::create($r);
    }
}
