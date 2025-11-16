<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Equipment, MaintenanceRequest, MaintenancePlan};

class MaintenanceController extends Controller
{
    /* =========================
     * EQUIPMENTS
     * ========================= */

    public function equipments(Request $r)
    {
        $q = Equipment::query()
            ->when($r->filled('search'), function ($qq) use ($r) {
                $s = "%{$r->search}%";
                $qq->where(function ($w) use ($s) {
                    $w->where('name', 'like', $s)
                        ->orWhere('serial', 'like', $s)
                        ->orWhere('category', 'like', $s);
                });
            })
            ->orderBy('name');

        return response()->json([
            'ok'   => true,
            'data' => $q->paginate($r->get('per_page', 20))
        ]);
    }

    public function storeEquipment(Request $r)
    {
        $data = $r->validate([
            'name'     => 'required|string|max:200',
            'serial'   => 'nullable|string|max:200',
            'category' => 'nullable|string|max:200',
            'asset_id' => 'nullable|integer|exists:assets,id',
            'active'   => 'nullable|boolean',
        ]);

        $eq = Equipment::create($data + ['active' => $data['active'] ?? true]);

        return response()->json(['ok' => true, 'equipment' => $eq], 201);
    }

    public function showEquipment($id)
    {
        return response()->json([
            'ok' => true,
            'equipment' => Equipment::findOrFail($id)
        ]);
    }

    public function updateEquipment(Request $r, $id)
    {
        $eq = Equipment::findOrFail($id);

        $data = $r->validate([
            'name'     => 'required|string|max:200',
            'serial'   => 'nullable|string|max:200',
            'category' => 'nullable|string|max:200',
            'asset_id' => 'nullable|integer|exists:assets,id',
            'active'   => 'nullable|boolean',
        ]);

        $eq->update($data);

        return response()->json(['ok' => true, 'equipment' => $eq]);
    }

    public function destroyEquipment($id)
    {
        Equipment::findOrFail($id)->delete();
        return response()->json(['ok' => true, 'message' => 'Equipment deleted']);
    }


    /* =========================
     * PREVENTIVE PLANS
     * ========================= */

    public function plans()
    {
        return response()->json([
            'ok'    => true,
            'plans' => MaintenancePlan::with('equipment')
                ->latest()
                ->paginate(20)
        ]);
    }

    public function storePlan(Request $r)
    {
        $data = $r->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'frequency'    => 'required|string',
            'next_date'    => 'required|date',
            'procedure'    => 'nullable|string',
        ]);

        $plan = MaintenancePlan::create($data);

        return response()->json([
            'ok'   => true,
            'plan' => $plan->load('equipment')
        ], 201);
    }

    public function showPlan($id)
    {
        return response()->json([
            'ok'   => true,
            'plan' => MaintenancePlan::with('equipment')->findOrFail($id)
        ]);
    }

    public function updatePlan(Request $r, $id)
    {
        $plan = MaintenancePlan::findOrFail($id);

        $data = $r->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'frequency'    => 'required|string',
            'next_date'    => 'required|date',
            'procedure'    => 'nullable|string'
        ]);

        $plan->update($data);

        return response()->json(['ok' => true, 'plan' => $plan]);
    }

    public function destroyPlan($id)
    {
        MaintenancePlan::findOrFail($id)->delete();
        return response()->json(['ok' => true, 'message' => 'Plan deleted']);
    }


    /* =========================
     * MAINTENANCE REQUESTS
     * ========================= */

    public function index(Request $r)
    {
        $q = MaintenanceRequest::with('equipment')
            ->when($r->filled('status'), fn($qq) => $qq->where('status', $r->status))
            ->when($r->filled('type'),   fn($qq) => $qq->where('type', $r->type))
            ->latest();

        return response()->json([
            'ok'       => true,
            'requests' => $q->paginate($r->get('per_page', 15)),
        ]);
    }

    public function storeRequest(Request $r)
    {
        $data = $r->validate([
            'equipment_id' => 'required|integer|exists:equipment,id',
            'type'         => 'required|in:corrective,preventive',
            'note'         => 'nullable|string',
            'priority'     => 'nullable|in:low,normal,high',
        ]);

        $mr = MaintenanceRequest::create($data + ['status' => 'open']);

        return response()->json(['ok' => true, 'request' => $mr->load('equipment')], 201);
    }

    public function showRequest($id)
    {
        return response()->json([
            'ok'      => true,
            'request' => MaintenanceRequest::with('equipment')->findOrFail($id)
        ]);
    }

    public function updateRequest(Request $r, $id)
    {
        $mr = MaintenanceRequest::findOrFail($id);

        $data = $r->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'type'         => 'required|in:corrective,preventive',
            'note'         => 'nullable|string',
            'priority'     => 'nullable|in:low,normal,high',
            'status'       => 'nullable|in:open,progress,done',
        ]);

        $mr->update($data);

        return response()->json(['ok' => true, 'request' => $mr]);
    }

    public function destroyRequest($id)
    {
        MaintenanceRequest::findOrFail($id)->delete();
        return response()->json(['ok' => true, 'message' => 'Request deleted']);
    }

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
