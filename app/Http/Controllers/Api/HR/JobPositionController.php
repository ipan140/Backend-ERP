<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\JobPosition;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobPositionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        $q = JobPosition::query()
            ->with('department')
            ->search($request->get('search'))
            ->department($request->get('department_id'))
            ->when($request->filled('active'), fn($qq) => $qq->where('active', $request->boolean('active')))
            ->orderBy('name');

        return response()->json($q->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => [
                'required','string','max:20',
                Rule::unique('job_positions','code')->whereNull('deleted_at'),
            ],
            'name' => ['required','string','max:100'],
            'department_id' => ['required','integer','exists:departments,id'],
            'description' => ['nullable','string'],
            'active' => ['nullable','boolean'],
        ]);

        $row = JobPosition::create([
            ...$data,
            'active' => array_key_exists('active', $data) ? (bool)$data['active'] : true,
        ]);

        return response()->json($row->load('department'), 201);
    }

    public function show($id)
    {
        $row = JobPosition::with('department')->find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = JobPosition::find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);

        $data = $request->validate([
            'code' => [
                'required','string','max:20',
                Rule::unique('job_positions','code')
                    ->ignore($row->id)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required','string','max:100'],
            'department_id' => ['required','integer','exists:departments,id'],
            'description' => ['nullable','string'],
            'active' => ['nullable','boolean'],
        ]);

        $row->update($data);

        return response()->json($row->refresh()->load('department'));
    }

    public function destroy($id)
    {
        $row = JobPosition::find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);

        // Opsional: cegah hapus jika masih dipakai employee
        // if ($row->employees()->exists()) {
        //     return response()->json(['message' => 'Tidak bisa dihapus: masih digunakan oleh karyawan.'], 422);
        // }

        $row->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
