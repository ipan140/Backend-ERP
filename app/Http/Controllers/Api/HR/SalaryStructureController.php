<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\SalaryStructure;
use App\Models\SalaryRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SalaryStructureController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        $q = SalaryStructure::query()
            ->withCount('rules')
            ->search($request->get('search'))
            ->when($request->filled('active'), fn($qq) => $qq->where('active', $request->boolean('active')))
            ->orderBy('name');

        return response()->json($q->paginate($perPage));
    }

    public function show($id)
    {
        $row = SalaryStructure::with(['rules'])->find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);
        return response()->json($row);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'        => ['required','string','max:30', Rule::unique('salary_structures','code')->whereNull('deleted_at')],
            'name'        => ['required','string','max:150'],
            'base_basic'  => ['nullable','numeric','min:0'],
            'active'      => ['nullable','boolean'],
            'description' => ['nullable','string'],

            // optional: attach rules
            'rules'            => ['nullable','array'],
            'rules.*.rule_id'  => ['required_with:rules','integer','exists:salary_rules,id'],
            'rules.*.seq'      => ['nullable','integer','min:1'],
        ]);

        return DB::transaction(function () use ($data) {
            $rules = $data['rules'] ?? [];
            unset($data['rules']);

            $row = SalaryStructure::create([
                ...$data,
                'active' => (bool)($data['active'] ?? true),
            ]);

            if (!empty($rules)) {
                $attach = [];
                $seq = 1;
                foreach ($rules as $r) {
                    $attach[$r['rule_id']] = ['seq' => $r['seq'] ?? $seq++];
                }
                $row->rules()->attach($attach);
            }

            return response()->json($row->load('rules'), 201);
        });
    }

    public function update(Request $request, $id)
    {
        $row = SalaryStructure::with('rules')->find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);

        $data = $request->validate([
            'code'        => ['required','string','max:30', Rule::unique('salary_structures','code')->ignore($row->id)->whereNull('deleted_at')],
            'name'        => ['required','string','max:150'],
            'base_basic'  => ['nullable','numeric','min:0'],
            'active'      => ['nullable','boolean'],
            'description' => ['nullable','string'],

            'rules'            => ['nullable','array'],
            'rules.*.rule_id'  => ['required_with:rules','integer','exists:salary_rules,id'],
            'rules.*.seq'      => ['nullable','integer','min:1'],
        ]);

        return DB::transaction(function () use ($row, $data) {
            $rules = $data['rules'] ?? [];
            unset($data['rules']);

            $row->update($data);

            if (!empty($rules)) {
                // replace pivot
                $sync = [];
                $seq = 1;
                foreach ($rules as $r) {
                    $sync[$r['rule_id']] = ['seq' => $r['seq'] ?? $seq++];
                }
                $row->rules()->sync($sync);
            }

            return response()->json($row->refresh()->load('rules'));
        });
    }

    public function destroy($id)
    {
        $row = SalaryStructure::find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);

        // opsional: cegah delete jika terhubung ke employee (kalau ada relasi)
        $row->delete();
        return response()->json(['message'=>'Deleted']);
    }
}
