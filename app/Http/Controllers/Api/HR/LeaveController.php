<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        $q = Leave::query()
            ->with(['employee','leaveType','approver'])
            ->when($request->filled('status'), fn($qq) => $qq->where('status', $request->status))
            ->when($request->filled('employee_id'), fn($qq) => $qq->where('employee_id', $request->employee_id))
            ->when($request->filled('leave_type_id'), fn($qq) => $qq->where('leave_type_id', $request->leave_type_id))
            ->when($request->filled('date_from') && $request->filled('date_to'), function ($qq) use ($request) {
                $qq->whereBetween('date_start', [$request->date_from, $request->date_to])
                   ->whereBetween('date_end',   [$request->date_from, $request->date_to]);
            }, function ($qq) use ($request) {
                if ($from = $request->get('date_from')) $qq->whereDate('date_start', '>=', $from);
                if ($to   = $request->get('date_to'))   $qq->whereDate('date_end',   '<=', $to);
            })
            ->orderByDesc('id');

        return response()->json($q->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'    => ['required','integer','exists:employees,id'],
            'leave_type_id'  => ['required','integer','exists:leave_types,id'],
            'date_start'     => ['required','date'],
            'date_end'       => ['required','date','after_or_equal:date_start'],
            // days dihitung otomatis
            'reason'         => ['nullable','string'],
            'attachment_path'=> ['nullable','string'],
            'status'         => ['nullable', Rule::in(['draft','submitted','approved','rejected','cancelled'])],
            'approver_id'    => ['nullable','integer','exists:employees,id'],
            'approved_at'    => ['nullable','date'],
        ]);

        // Hitung days secara server-side
        $data['days'] = $this->calcDays($data['date_start'], $data['date_end']);

        // Default status: draft jika tidak dikirim
        if (!array_key_exists('status', $data) || !$data['status']) {
            $data['status'] = 'draft';
        }

        // Cegah approver sama dengan employee
        if (!empty($data['approver_id']) && (int)$data['approver_id'] === (int)$data['employee_id']) {
            return response()->json(['message' => 'approver_id tidak boleh sama dengan employee_id'], 422);
        }

        // approved_at hanya valid jika status approved
        if (($data['status'] ?? null) !== 'approved') {
            $data['approved_at'] = null;
        } else {
            $data['approved_at'] = $data['approved_at'] ?? now();
        }

        $row = Leave::create($data);

        return response()->json($row->load(['employee','leaveType','approver']), 201);
    }

    public function show($id)
    {
        $row = Leave::with(['employee','leaveType','approver'])->find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = Leave::find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);

        $data = $request->validate([
            'employee_id'    => ['required','integer','exists:employees,id'],
            'leave_type_id'  => ['required','integer','exists:leave_types,id'],
            'date_start'     => ['required','date'],
            'date_end'       => ['required','date','after_or_equal:date_start'],
            // days tetap dihitung otomatis
            'reason'         => ['nullable','string'],
            'attachment_path'=> ['nullable','string'],
            'status'         => ['nullable', Rule::in(['draft','submitted','approved','rejected','cancelled'])],
            'approver_id'    => ['nullable','integer','exists:employees,id'],
            'approved_at'    => ['nullable','date'],
        ]);

        $data['days'] = $this->calcDays($data['date_start'], $data['date_end']);

        if (!empty($data['approver_id']) && (int)$data['approver_id'] === (int)$data['employee_id']) {
            return response()->json(['message' => 'approver_id tidak boleh sama dengan employee_id'], 422);
        }

        // Konsistensi approved_at <-> status
        if (($data['status'] ?? $row->status) !== 'approved') {
            $data['approved_at'] = null;
        } else {
            $data['approved_at'] = $data['approved_at'] ?? now();
        }

        $row->update($data);

        return response()->json($row->refresh()->load(['employee','leaveType','approver']));
    }

    public function destroy($id)
    {
        $row = Leave::find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);
        $row->delete();
        return response()->json(['message'=>'Deleted']);
    }

    public function approve(Request $request, $id)
    {
        $row = Leave::find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);

        $data = $request->validate([
            'approver_id' => ['required','integer','exists:employees,id'],
        ]);

        if (in_array($row->status, ['approved','cancelled'], true)) {
            return response()->json(['message' => 'Leave tidak dapat di-approve pada status saat ini.'], 422);
        }

        if ((int)$data['approver_id'] === (int)$row->employee_id) {
            return response()->json(['message' => 'approver_id tidak boleh sama dengan employee_id'], 422);
        }

        $row->update([
            'status'      => 'approved',
            'approver_id' => $data['approver_id'],
            'approved_at' => now(),
        ]);

        return response()->json($row->fresh()->load(['employee','leaveType','approver']));
    }

    /* ======================
     | Helpers
     |======================*/
    protected function calcDays(string $start, string $end): int
    {
        $s = Carbon::parse($start)->startOfDay();
        $e = Carbon::parse($end)->endOfDay();
        return $s->diffInDays($e) + 1; // inklusif
    }
}
