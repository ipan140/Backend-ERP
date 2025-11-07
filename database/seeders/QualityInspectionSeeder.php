<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\Lot;
use App\Models\QualityInspection;

class QualityInspectionSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil contoh item & lot yang sudah kamu seed sebelumnya
        $urea = Item::where('sku', 'ITM-UREA')->first();
        $npk  = Item::where('sku', 'ITM-NPK')->first();

        $lotUrea = Lot::where('number', 'LIKE', 'LOT-UREA-%')->first();
        $lotNpk  = Lot::where('number', 'LIKE', 'LOT-NPK-%')->first();

        // Safety guard kalau belum ada datanya
        if (!$urea || !$npk || !$lotUrea || !$lotNpk) {
            $this->command?->warn('Skip QualityInspectionSeeder: item/lot belum tersedia.');
            return;
        }

        // QC di titik "receipt" (penerimaan)
        QualityInspection::updateOrCreate(
            ['lot_id' => $lotUrea->id, 'item_id' => $urea->id, 'point' => 'receipt'],
            [
                'result' => 'pass',
                'note'   => 'Inbound OK (kelembaban rendah, kemurnian bagus)',
            ]
        );

        QualityInspection::updateOrCreate(
            ['lot_id' => $lotNpk->id, 'item_id' => $npk->id, 'point' => 'receipt'],
            [
                'result' => 'pass',
                'note'   => 'Inbound OK (ukuran granule konsisten)',
            ]
        );
    }
}
