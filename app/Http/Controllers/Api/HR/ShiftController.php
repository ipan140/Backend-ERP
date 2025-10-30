<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        $q = Shift::query()
            ->search($request->get('search'))
            ->when($request->filled('active'), fn($qq) => $qq->where('active', $request->boolean('active')))
            ->orderBy('time_start');

        return response()->json($q->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'          => ['required','string','max:20', Rule::unique('shifts','code')->whereNull('deleted_at')],
            'name'          => ['required','string','max:100'],
            'time_start'    => ['required','date_format:H:i'],
            'time_end'      => ['required','date_format:H:i'],
            'break_minutes' => ['nullable','integer','min:0','max:600'],
            'is_night'      => ['nullable','boolean'],
            'description'   => ['nullable','string'],
            'active'        => ['nullable','boolean'],
        ]);

        $row = Shift::create([
            ...$data,
            'is_night' => (bool)($data['is_night'] ?? false),
            'active'   => (bool)($data['active'] ?? true),
        ]);

        return response()->json($row, 201);
    }

    public function show($id)
    {
        $row = Shift::find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = Shift::find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);

        $data = $request->validate([
            'code'          => ['required','string','max:20', Rule::unique('shifts','code')->ignore($row->id)->whereNull('deleted_at')],
            'name'          => ['required','string','max:100'],
            'time_start'    => ['required','date_format:H:i'],
            'time_end'      => ['required','date_format:H:i'],
            'break_minutes' => ['nullable','integer','min:0','max:600'],
            'is_night'      => ['nullable','boolean'],
            'description'   => ['nullable','string'],
            'active'        => ['nullable','boolean'],
        ]);

        $row->update($data);
        return response()->json($row->refresh());
    }

    public function destroy($id)
    {
        $row = Shift::find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);
        $row->delete();
        return response()->json(['message'=>'Deleted']);
    }
}
