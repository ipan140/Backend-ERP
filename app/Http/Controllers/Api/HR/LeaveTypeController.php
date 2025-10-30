<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        $q = LeaveType::query()
            ->when($request->filled('search'), fn($qq) =>
                $qq->where('name', 'like', "%{$request->search}%")
                   ->orWhere('code', 'like', "%{$request->search}%")
            )
            ->when($request->filled('active'), fn($qq) =>
                $qq->where('active', (bool)$request->boolean('active'))
            );

        return response()->json($q->orderBy('name')->paginate((int)$request->get('per_page', 15)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required','string','max:20', Rule::unique('leave_types','code')],
            'name' => ['required','string','max:100'],
            'description' => ['nullable','string'],
            'default_days' => ['nullable','integer','min:0'],
            'active' => ['boolean'],
        ]);

        $row = LeaveType::create($data);
        return response()->json($row, 201);
    }

    public function show($id)
    {
        $row = LeaveType::find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = LeaveType::find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);

        $data = $request->validate([
            'code' => ['required','string','max:20', Rule::unique('leave_types','code')->ignore($row->id)],
            'name' => ['required','string','max:100'],
            'description' => ['nullable','string'],
            'default_days' => ['nullable','integer','min:0'],
            'active' => ['boolean'],
        ]);

        $row->update($data);
        return response()->json($row->refresh());
    }

    public function destroy($id)
    {
        $row = LeaveType::find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);
        $row->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
