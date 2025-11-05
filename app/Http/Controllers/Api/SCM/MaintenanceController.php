<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function equipments()
    {
        return response()->json([
            'ok' => true,
            'data' => [],
        ]);
    }

    public function storeEquipment(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string',
            'serial' => 'nullable|string',
            'category' => 'nullable|string',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Equipment saved (dummy)',
            'equipment' => array_merge(['id' => 1], $data),
        ], 201);
    }

    public function plans()
    {
        return response()->json([
            'ok' => true,
            'plans' => [],
        ]);
    }

    public function request(Request $r)
    {
        $data = $r->validate([
            'equipment_id' => 'required|integer',
            'type' => 'required|string|in:corrective,preventive',
            'note' => 'nullable|string',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Maintenance request created (dummy)',
            'request' => array_merge(['id' => 1, 'status' => 'open'], $data),
        ], 201);
    }

    public function complete($id)
    {
        return response()->json([
            'ok' => true,
            'message' => "Maintenance request {$id} completed (dummy)",
        ]);
    }
}
