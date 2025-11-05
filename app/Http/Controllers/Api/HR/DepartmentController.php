<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        $q = Department::query()
            ->with(['manager', 'parent'])
            ->when($request->filled('active'), fn($qq) => $qq->where('active', (bool)$request->boolean('active')))
            ->when($request->filled('parent_id'), fn($qq) => $qq->where('parent_id', $request->parent_id))
            ->when($request->filled('manager_employee_id'), fn($qq) => $qq->where('manager_employee_id', $request->manager_employee_id))
            ->search($request->get('search'))
            ->orderBy('name');

        return response()->json($q->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => [
                'required','string','max:20',
                Rule::unique('departments','code'),
            ],
            'name' => 'required|string|max:100',
            'parent_id' => ['nullable','integer','exists:departments,id'],
            'manager_employee_id' => ['nullable','integer','exists:employees,id'],
            'active' => ['nullable','boolean'],
        ]);

        // pastikan parent_id != (akan dibuat), tidak bisa dicek circular di sini secara penuh
        // tapi minimal cegah self-reference di update; untuk store aman.

        $row = Department::create([
            ...$data,
            'active' => array_key_exists('active', $data) ? (bool)$data['active'] : true,
        ]);

        return response()->json($row->load(['manager','parent']), 201);
    }

    public function show($id)
    {
        $row = Department::with(['manager','parent'])->find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = Department::find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);

        $data = $request->validate([
            'code' => [
                'required','string','max:20',
                Rule::unique('departments','code')->ignore($row->id),
            ],
            'name' => 'required|string|max:100',
            'parent_id' => [
                'nullable','integer','exists:departments,id',
                // cegah self-parent
                function ($attr, $value, $fail) use ($row) {
                    if ($value && (int)$value === (int)$row->id) {
                        $fail('parent_id tidak boleh menunjuk dirinya sendiri.');
                    }
                },
            ],
            'manager_employee_id' => ['nullable','integer','exists:employees,id'],
            'active' => ['nullable','boolean'],
        ]);

        $row->update($data);

        return response()->json(
            $row->refresh()->load(['manager','parent'])
        );
    }

    public function destroy($id)
    {
        $row = Department::find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);

        // opsional: jika ingin cegah hapus saat masih punya child
        // if ($row->children()->exists()) {
        //     return response()->json(['message' => 'Department masih memiliki sub-department.'], 422);
        // }

        $row->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
