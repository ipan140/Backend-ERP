<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vendor;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'VND-001', 'name' => 'PT Sumber Makmur', 'email' => 'po@sumbermakmur.id', 'phone' => '021-5555', 'address' => 'Jakarta',  'rating' => 4.5, 'active' => true],
            ['code' => 'VND-002', 'name' => 'CV Tani Subur',    'email' => 'sales@tanisubur.id', 'phone' => '031-2222', 'address' => 'Surabaya', 'rating' => 4.2, 'active' => true],
        ];
        foreach ($rows as $r) Vendor::updateOrCreate(['code' => $r['code']], $r);
    }
}
