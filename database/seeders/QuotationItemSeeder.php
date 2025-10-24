<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuotationItemSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('quotation_items')->insertOrIgnore([
            [
                'quotation_id' => 1,
                'product_id' => 1,
                'description' => 'Pupuk Cair Organik 1L',
                'qty' => 2,
                'uom' => 'pcs',
                'unit_price' => 55000,
                'discount_pct' => 0,
                'line_total' => 110000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'quotation_id' => 1,
                'product_id' => 2,
                'description' => 'Benih Cabai Rawit Premium',
                'qty' => 5,
                'uom' => 'pack',
                'unit_price' => 15000,
                'discount_pct' => 10,
                'line_total' => 67500,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'quotation_id' => 1,
                'product_id' => 3,
                'description' => 'Media Tanam Cocopeat 5kg',
                'qty' => 1,
                'uom' => 'bag',
                'unit_price' => 35000,
                'discount_pct' => 0,
                'line_total' => 35000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
