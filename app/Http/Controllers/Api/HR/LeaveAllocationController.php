<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveAllocation;
use Illuminate\Http\Request;

class LeaveAllocationController extends Controller
{
    public function index(Request $request)
    {
        $q = LeaveAllocation::with(['employee','leaveType'])
            ->when($request->filled('employee_id'), fn($qq) => $qq->where('employee_id', $request->employee_id))
            ->when($request->filled('leave_type_id'), fn($qq) => $qq->where('leave_type_id', $request->leave_type_id))
            ->when($request->filled('year'), fn($qq) => $qq->where('year', $request->year));

        return response()->json($q->paginate((int)$request->get('per_page', 15)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'year' => 'required|integer|min:2000|max:2100',
            'allocated_days' => 'required|integer|min:0',
            'used_days' => 'nullable|integer|min:0',
        ]);

        $row = LeaveAllocation::create($data);
        return response()->json($row->load(['employee','leaveType']), 201);
    }

    public function show($id)
    {
        $row = LeaveAllocation::with(['employee','leaveType'])->find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = LeaveAllocation::find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'year' => 'required|integer|min:2000|max:2100',
            'allocated_days' => 'required|integer|min:0',
            'used_days' => 'nullable|integer|min:0',
        ]);

        $row->update($data);
        return response()->json($row->refresh()->load(['employee','leaveType']));
    }

    public function destroy($id)
    {
        $row = LeaveAllocation::find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);
        $row->delete();
        return response()->json(['message'=>'Deleted']);
    }
}
