<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QualityInspection;
use App\Models\Shipment;

class QualityInspectionSeeder extends Seeder
{
    public function run(): void
    {
        if (QualityInspection::count() > 0) return;

        $ref = Shipment::inRandomOrder()->first();
        if (!$ref) return;

        $rows = [
            [
                'number'         => 'QI-'.now()->format('Ymd').'-0001',
                'reference_type' => 'shipment',
                'reference_id'   => $ref->id,
                'status'         => 'passed',
                'inspected_at'   => now()->subDay(),
            ],
            [
                'number'         => 'QI-'.now()->format('Ymd').'-0002',
                'reference_type' => 'shipment',
                'reference_id'   => $ref->id,
                'status'         => 'failed',
                'inspected_at'   => now(),
            ],
        ];

        foreach ($rows as $r) QualityInspection::create($r);
    }
}
