<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProcessingOrder;
use App\Models\Product; // ✅ ganti Item -> Product
use Illuminate\Support\Facades\DB;

class ProcessingOrderSeeder extends Seeder
{
    public function run(): void
    {
        // kalau tidak ada product, tidak usah seed
        if (Product::count() === 0) return;

        // hindari duplikasi seed
        if (ProcessingOrder::count() > 0) return;

        $fg  = Product::inRandomOrder()->first();
        $num = now()->format('Ymd');

        $rows = [
            [
                'number'      => "MO-$num-0001",
                'product_id'  => $fg->id,
                'qty'         => 100,
                'status'      => 'in_progress',       // ✅ valid enum
                'date'        => now()->toDateString(),
                'started_at'  => now()->subDay(),
                'finished_at' => null,
            ],
            [
                'number'      => "MO-$num-0002",
                'product_id'  => $fg->id,
                'qty'         => 50,
                'status'      => 'draft',             // ✅ ganti planned -> draft
                'date'        => now()->toDateString(),
                'started_at'  => null,
                'finished_at' => null,
            ],
        ];

        DB::transaction(function () use ($rows) {
            foreach ($rows as $r) {
                ProcessingOrder::create($r);
            }
        });
    }
}
