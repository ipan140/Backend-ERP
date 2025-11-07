<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $defs = ['REC-001' => 'Receiving', 'STK-001' => 'Stock', 'SHP-001' => 'Shipping'];
        foreach (Warehouse::all() as $wh) {
            foreach ($defs as $code => $name) {
                Location::updateOrCreate(
                    ['warehouse_id' => $wh->id, 'code' => $code],
                    ['name' => $name, 'type' => 'internal', 'active' => true]
                );
            }
        }
    }
}
