<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use Illuminate\Support\Carbon;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $rows = [
            [
                // 'id' => 1, // opsional, biarkan autoincrement saja
                'sku'        => 'PRD001',
                'name'       => 'Pupuk Cair Organik 1L',
                'uom'        => 'botol',
                'base_price' => 55000,
                'active'     => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku'        => 'PRD002',
                'name'       => 'Benih Cabai Rawit Premium',
                'uom'        => 'paket',
                'base_price' => 15000,
                'active'     => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku'        => 'PRD003',
                'name'       => 'Media Tanam Cocopeat 5kg',
                'uom'        => 'karung',
                'base_price' => 35000,
                'active'     => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // upsert by unique key 'sku'
        Product::upsert($rows, ['sku'], ['name','uom','base_price','active','updated_at']);
    }
}
