<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class QuotationItemSeeder extends Seeder
{
    public function run(): void
    {
        // 🔒 Daftar quotation yang ingin di-seed (aman dijalankan berulang)
        $quotationIds = [1];

        // 1) Bersihkan data existing untuk quotation yang sama
        DB::table('quotation_items')->whereIn('quotation_id', $quotationIds)->delete();

        // 2) Definisikan item mentah (tanpa line_total, akan dihitung otomatis)
        $rows = [
            [
                'quotation_id' => 1,
                'product_id'   => 1,
                'description'  => 'Pupuk Cair Organik 1L',
                'qty'          => 2,
                'uom'          => 'pcs',
                'unit_price'   => 55000,
                'discount_pct' => 0,
            ],
            [
                'quotation_id' => 1,
                'product_id'   => 2,
                'description'  => 'Benih Cabai Rawit Premium',
                'qty'          => 5,
                'uom'          => 'pack',
                'unit_price'   => 15000,
                'discount_pct' => 10,
            ],
            [
                'quotation_id' => 1,
                'product_id'   => 3,
                'description'  => 'Media Tanam Cocopeat 5kg',
                'qty'          => 1,
                'uom'          => 'bag',
                'unit_price'   => 35000,
                'discount_pct' => 0,
            ],
        ];

        // 3) Hitung line_total + set timestamps
        $now = Carbon::now();
        $payload = array_map(function ($r) use ($now) {
            $qty          = (float) ($r['qty'] ?? 0);
            $unit         = (float) ($r['unit_price'] ?? 0);
            $discPct      = (float) ($r['discount_pct'] ?? 0);
            $lineTotal    = (int) round($qty * $unit * (1 - ($discPct / 100)));

            return array_merge($r, [
                'line_total' => $lineTotal,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $rows);

        // 4) Insert
        DB::table('quotation_items')->insert($payload);
    }
}
