<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\{Pricelist, PricelistItem, Product};

class PricelistSeeder extends \Illuminate\Database\Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1) Default (sale)
            $default = Pricelist::updateOrCreate(
                ['name' => 'Default Price List'],
                [
                    'currency'    => 'IDR',
                    'type'        => 'sale',
                    'description' => 'Daftar harga dasar default',
                    'valid_from'  => Carbon::now()->subYear()->toDateString(),
                    'valid_until' => null,
                    'active'      => true,
                ]
            );

            // 2) Retail 2025 (sale, dengan periode)
            $retail = Pricelist::updateOrCreate(
                ['name' => 'Retail Price List 2025'],
                [
                    'currency'    => 'IDR',
                    'type'        => 'sale',
                    'description' => 'Harga eceran 2025',
                    'valid_from'  => '2025-01-01',
                    'valid_until' => '2025-12-31',
                    'active'      => true,
                ]
            );

            // 3) Ambil beberapa produk yang sudah ada
            $products = Product::orderBy('id')->take(3)->get();

            // 4) Buat override harga untuk retail (contoh: +10% dari base_price)
            foreach ($products as $p) {
                PricelistItem::updateOrCreate(
                    [
                        'pricelist_id' => $retail->id,
                        'product_id'   => $p->id,
                        'min_qty'      => 1,
                    ],
                    [
                        'price'      => round($p->base_price * 1.10, 2),
                        'active'     => true,
                        'date_start' => '2025-01-01',
                        'date_end'   => '2025-12-31',
                    ]
                );
            }
        });
    }
}
