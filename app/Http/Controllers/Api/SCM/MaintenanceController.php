<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Equipment, MaintenanceRequest};

class MaintenanceController extends Controller
{
    /* =========================
     * EQUIPMENTS
     * ========================= */

    // List equipments (support search & paginate ringan)
    public function equipments(Request $r)
    {
        $q = Equipment::query()
            ->when($r->filled('search'), function ($qq) use ($r) {
                $s = "%{$r->search}%";
                $qq->where(function ($w) use ($s) {
                    $w->where('name', 'like', $s)
                      ->orWhere('code', 'like', $s)
                      ->orWhere('serial', 'like', $s)
                      ->orWhere('category', 'like', $s);
                });
            })
            ->orderBy('name');

        $perPage = (int) $r->get('per_page', 20);
        $rows    = $q->paginate($perPage);

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    // Create equipment
    public function storeEquipment(Request $r)
    {
        $data = $r->validate([
            'name'     => 'required|string|max:200',
            'code'     => 'required|string|max:100|unique:equipments,code',
            'serial'   => 'nullable|string|max:200',
            'category' => 'nullable|string|max:200',
            'asset_id' => 'nullable|integer|exists:assets,id',
            'active'   => 'nullable|boolean',
        ]);

        $eq = Equipment::create($data + ['active' => $data['active'] ?? true]);

        return response()->json(['ok' => true, 'equipment' => $eq], 201);
    }

    /* =========================
     * PREVENTIVE PLANS (optional)
     * ========================= */
    public function plans()
    {
        // Bila belum ada tabel/Model MaintenancePlan, kirim array kosong agar FE aman.
        // return response()->json(['ok'=>true,'plans'=>MaintenancePlan::with('equipment')->latest()->paginate(20)]);
        return response()->json(['ok' => true, 'plans' => []]);
    }

    /* =========================
     * MAINTENANCE REQUESTS
     * ========================= */

    // List requests
    public function index(Request $r)
    {
        $q = MaintenanceRequest::with('equipment')
            ->when($r->filled('status'), fn($qq) => $qq->where('status', $r->status))
            ->when($r->filled('type'),   fn($qq) => $qq->where('type', $r->type))
            ->orderByDesc('id');

        $perPage = (int) $r->get('per_page', 15);

        return response()->json([
            'ok'       => true,
            'requests' => $q->paginate($perPage),
        ]);
    }

    // Create request (corrective / preventive)
    public function request(Request $r)
    {
        $data = $r->validate([
            'equipment_id' => 'required|integer|exists:equipments,id',
            'type'         => 'required|string|in:corrective,preventive',
            'note'         => 'nullable|string',
            'priority'     => 'nullable|in:low,normal,high',
        ]);

        $mr = MaintenanceRequest::create($data + [
            'status'       => 'open',
        ]);

        return response()->json(['ok' => true, 'request' => $mr->load('equipment')], 201);
    }

    // Complete a request
    public function complete($id)
    {
        $mr = MaintenanceRequest::findOrFail($id);

        if ($mr->status === 'done') {
            return response()->json(['ok' => false, 'message' => 'Request already completed'], 422);
        }

        $mr->update(['status' => 'done', 'completed_at' => now()]);

        return response()->json(['ok' => true, 'message' => 'Maintenance request completed']);
    }
}
