<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['sku' => 'ITM-UREA', 'name' => 'Urea Granular', 'uom' => 'kg',  'is_stockable' => true, 'std_cost' => 6500],
            ['sku' => 'ITM-NPK',  'name' => 'NPK 16-16-16',  'uom' => 'kg',  'is_stockable' => true, 'std_cost' => 8000],
            ['sku' => 'ITM-BAG',  'name' => 'Karung 25kg',   'uom' => 'pcs', 'is_stockable' => true, 'std_cost' => 2500],
        ];
        foreach ($rows as $r) Item::updateOrCreate(['sku' => $r['sku']], $r);
    }
}
