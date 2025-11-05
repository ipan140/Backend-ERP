<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\WorkOrder;
use App\Models\Asset;

class WorkOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (Asset::count() === 0) {
            $this->command?->warn('⚠️ WorkOrderSeeder skipped: no assets found.');
            return;
        }
        if (WorkOrder::count() > 0) return;

        // helper nomor unik
        $genNumber = function (int $seq = 1): string {
            return 'WO-'.now()->format('Ymd').'-'.str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
        };

        // ambil 2 asset (boleh sama kalau kebetulan cuma ada 1)
        $a1 = Asset::inRandomOrder()->first();
        $a2 = Asset::where('id', '!=', $a1->id)->inRandomOrder()->first() ?? $a1;

        DB::transaction(function () use ($a1, $a2, $genNumber) {
            // WO #1
            WorkOrder::create([
                'number'         => $genNumber(1),
                'asset_id'       => $a1->id,
                'title'          => 'Servis rutin pompa',
                'notes'          => 'Ganti oli & cek seal',
                'scheduled_date' => now()->addDays(3),
                'status'         => 'scheduled',   // enum valid
                'priority'       => 'normal',      // optional (low|normal|high)
                'technician'     => 'Teknisi A',   // optional
                'completed_at'   => null,
            ]);

            // WO #2
            WorkOrder::create([
                'number'         => $genNumber(2),
                'asset_id'       => $a2->id,
                'title'          => 'Perbaikan conveyor belt',
                'notes'          => 'Belt selip di station 2',
                'scheduled_date' => now()->addDay(),
                'status'         => 'open',
                'priority'       => 'high',
                'technician'     => 'Teknisi B',
                'completed_at'   => null,
            ]);
        });
    }
}
