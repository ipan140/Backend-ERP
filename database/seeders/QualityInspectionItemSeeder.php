<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QualityInspection;
use App\Models\QualityInspectionItem;
use App\Models\Item;

class QualityInspectionItemSeeder extends Seeder
{
    public function run(): void
    {
        $inspections = QualityInspection::query()->get();
        if ($inspections->isEmpty()) {
            $this->command?->warn('⚠️ Skip QualityInspectionItemSeeder: tidak ada quality_inspections.');
            return;
        }

        if (Item::count() === 0) {
            $this->command?->warn('⚠️ Skip QualityInspectionItemSeeder: tidak ada items.');
            return;
        }

        foreach ($inspections as $qi) {
            // ambil 2 item acak per inspection
            $picked = Item::inRandomOrder()->take(2)->get();

            foreach ($picked as $it) {
                // generate angka QC yang konsisten
                $qtyChecked = rand(5, 25);
                // asumsi 80% pass
                $qtyFailed  = rand(0, (int) round($qtyChecked * 0.2));
                $qtyPassed  = $qtyChecked - $qtyFailed;

                $result = $qtyFailed > 0 ? 'defect' : 'ok';

                QualityInspectionItem::create([
                    'quality_inspection_id' => $qi->id,
                    'item_id'               => $it->id,
                    'qty_checked'           => $qtyChecked,
                    'qty_passed'            => $qtyPassed,
                    'qty_failed'            => $qtyFailed,
                    'result'                => $result,     // 'ok' | 'defect'
                    'defect_code'           => $qtyFailed > 0 ? 'MINOR' : null,
                    'remarks'               => $qtyFailed > 0 ? 'Baret halus pada kemasan' : null,
                ]);
            }
        }
    }
}
