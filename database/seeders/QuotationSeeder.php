<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuotationSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('quotations')->insertOrIgnore([
            [
                'id' => 1,
                'number' => 'QO-202510-0001',
                'customer_id' => 1,
                'pricelist_id' => null,
                'valid_until' => '2025-12-31',
                'status' => 'draft',
                'subtotal' => 212500,
                'discount_amount' => 0,
                'tax_amount' => 23375,
                'total' => 235875,
                'notes' => 'Penawaran produk pertanian dari Seeder',
                'extra' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
