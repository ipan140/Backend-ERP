<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipment;
use App\Models\MaintenancePlan;
use Carbon\Carbon;

class MaintenancePlanSeeder extends Seeder
{
    public function run(): void
    {
        $eq = Equipment::first();
        if (!$eq) return;

        MaintenancePlan::updateOrCreate(
            ['equipment_id' => $eq->id, 'frequency' => 'monthly'],
            ['next_date' => Carbon::now()->addDays(10)->toDateString(), 'procedure' => 'Lube + check seal']
        );
    }
}
