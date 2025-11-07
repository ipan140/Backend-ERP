<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asset;
use App\Models\WorkOrder;
use Carbon\Carbon;

class WorkOrderSeeder extends Seeder
{
    public function run(): void
    {
        $asset = Asset::where('code','AST-PMP-01')->first();

        WorkOrder::updateOrCreate(['number' => 'WO-'.date('Ymd').'-001'], [
            'asset_id' => optional($asset)->id,
            'title' => 'Mix Urea + NPK',
            'notes' => 'Campur batch urea dan NPK untuk formulasi',
            'scheduled_date' => Carbon::now()->addDay(),
            'technician' => 'Andi',
            'status' => 'open',
            'priority' => 'normal',
        ]);
    }
}
