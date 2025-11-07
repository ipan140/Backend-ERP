<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\Lot;
use App\Models\QualityInspection;
use App\Models\QualityInspectionItem;

class QualityInspectionItemSeeder extends Seeder
{
    public function run(): void
    {
        $urea = Item::where('sku', 'ITM-UREA')->first();
        $npk  = Item::where('sku', 'ITM-NPK')->first();

        $lotUrea = Lot::where('number', 'LIKE', 'LOT-UREA-%')->first();
        $lotNpk  = Lot::where('number', 'LIKE', 'LOT-NPK-%')->first();

        if (!$urea || !$npk || !$lotUrea || !$lotNpk) {
            $this->command?->warn('Skip QualityInspectionItemSeeder: item/lot belum tersedia.');
            return;
        }

        $qcUrea = QualityInspection::where([
            'lot_id'  => $lotUrea->id,
            'item_id' => $urea->id,
            'point'   => 'receipt',
        ])->first();

        $qcNpk = QualityInspection::where([
            'lot_id'  => $lotNpk->id,
            'item_id' => $npk->id,
            'point'   => 'receipt',
        ])->first();

        if (!$qcUrea || !$qcNpk) {
            $this->command?->warn('Skip QualityInspectionItemSeeder: QC header belum ada.');
            return;
        }

        // ---- Parameter untuk UREA
        $paramsUrea = [
            // parameter, unit, value, min, max
            ['Kelembaban', '%', 0.5, 0.0, 1.0],
            ['Kemurnian',  '%', 99.2, 98.0, 100.0],
            ['Warna',      null, null, null, null], // contoh parameter kualitatif
        ];

        foreach ($paramsUrea as [$param, $unit, $value, $min, $max]) {
            QualityInspectionItem::updateOrCreate(
                ['quality_inspection_id' => $qcUrea->id, 'parameter' => $param],
                ['unit' => $unit, 'value' => $value, 'min' => $min, 'max' => $max]
            );
        }

        // ---- Parameter untuk NPK
        $paramsNpk = [
            ['Granule Size', 'mm', 3.0, 2.0, 4.0],
            ['Moisture',     '%',  0.8, 0.0, 1.5],
        ];

        foreach ($paramsNpk as [$param, $unit, $value, $min, $max]) {
            QualityInspectionItem::updateOrCreate(
                ['quality_inspection_id' => $qcNpk->id, 'parameter' => $param],
                ['unit' => $unit, 'value' => $value, 'min' => $min, 'max' => $max]
            );
        }
    }
}
