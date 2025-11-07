<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lot;
use Carbon\Carbon;

class LotSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // ⚙️ Seed contoh (tanpa FK, pastikan item_id sesuai dengan data ItemSeeder)
        $rows = [
            [
                'item_id'     => 1, // Urea
                'number'      => 'LOT-UREA-' . $now->format('ymd'),
                'mfg_date'    => $now->copy()->subDays(7),
                'expiry_date' => $now->copy()->addMonths(12),
            ],
            [
                'item_id'     => 2, // NPK
                'number'      => 'LOT-NPK-' . $now->format('ymd'),
                'mfg_date'    => $now->copy()->subDays(10),
                'expiry_date' => $now->copy()->addMonths(10),
            ],
        ];

        foreach ($rows as $r) {
            Lot::updateOrCreate(
                [
                    'item_id' => $r['item_id'],
                    'number'  => $r['number'],
                ],
                [
                    'mfg_date'    => $r['mfg_date']->toDateString(),
                    'expiry_date' => $r['expiry_date']->toDateString(),
                ]
            );
        }

        $this->command?->info('✅ LotSeeder: sample lot data inserted successfully (no FK mode).');
    }
}
