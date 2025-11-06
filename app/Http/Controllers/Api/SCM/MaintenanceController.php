<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function equipments()
    {
        // Dummy list agar UI ada data saat awal
        $equipments = [
            ['id' => 1, 'name' => 'Pump A', 'serial' => 'PMP-001', 'category' => 'Pump'],
            ['id' => 2, 'name' => 'Valve B', 'serial' => 'VLV-221', 'category' => 'Valve'],
        ];

        return response()->json([
            'ok'   => true,
            'data' => $equipments,
        ]);
    }

    public function storeEquipment(Request $r)
    {
        $data = $r->validate([
            'name'     => 'required|string|max:200',
            'serial'   => 'nullable|string|max:200',
            'category' => 'nullable|string|max:200',
        ]);

        // Dummy id = 999 biar kelihatan dari UI
        return response()->json([
            'ok'        => true,
            'message'   => 'Equipment saved (dummy)',
            'equipment' => array_merge(['id' => 999], $data),
        ], 201);
    }

    public function plans()
    {
        // Dummy preventive plans
        $plans = [
            [
                'id' => 11,
                'equipment_id' => 1,
                'equipment_name' => 'Pump A',
                'frequency' => 'monthly',
                'next_date' => now()->addDays(10)->toDateString(),
            ],
            [
                'id' => 12,
                'equipment_id' => 2,
                'equipment_name' => 'Valve B',
                'frequency' => 'quarterly',
                'next_date' => now()->addDays(25)->toDateString(),
            ],
        ];

        return response()->json([
            'ok'    => true,
            'plans' => $plans,
        ]);
    }

    // NEW: list maintenance requests (dummy)
    public function requests()
    {
        $requests = [
            [
                'id' => 101,
                'equipment_id' => 1,
                'equipment_name' => 'Pump A',
                'type' => 'corrective',
                'note' => 'Leak detected',
                'status' => 'open',
                'created_at' => now()->subDay()->toDateTimeString(),
            ],
            [
                'id' => 102,
                'equipment_id' => 2,
                'equipment_name' => 'Valve B',
                'type' => 'preventive',
                'note' => 'Scheduled lube',
                'status' => 'done',
                'created_at' => now()->subDays(2)->toDateTimeString(),
            ],
        ];

        return response()->json([
            'ok'       => true,
            'requests' => $requests,
        ]);
    }

    public function request(Request $r)
    {
        $data = $r->validate([
            'equipment_id' => 'required|integer',
            'type'         => 'required|string|in:corrective,preventive',
            'note'         => 'nullable|string',
        ]);

        return response()->json([
            'ok'      => true,
            'message' => 'Maintenance request created (dummy)',
            'request' => array_merge([
                'id' => 888,
                'status' => 'open',
                'created_at' => now()->toDateTimeString(),
            ], $data),
        ], 201);
    }

    public function complete($id)
    {
        return response()->json([
            'ok'      => true,
            'message' => "Maintenance request {$id} completed (dummy)",
        ]);
    }
}
