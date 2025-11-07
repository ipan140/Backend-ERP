<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipment;
use App\Models\MaintenanceRequest;

class MaintenanceRequestSeeder extends Seeder
{
    public function run(): void
    {
        $eq = Equipment::first();
        if (!$eq) return;

        MaintenanceRequest::updateOrCreate(
            ['equipment_id' => $eq->id, 'type' => 'corrective', 'note' => 'Leak detected'],
            ['status' => 'open']
        );
    }
}
