<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\SalaryRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalaryRuleController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        $q = SalaryRule::query()
            ->search($request->get('search'))
            ->when($request->filled('type'), fn($qq) => $qq->where('type', $request->type))
            ->when($request->filled('active'), fn($qq) => $qq->where('active', $request->boolean('active')))
            ->orderBy('name');

        return response()->json($q->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'         => ['required','string','max:30', Rule::unique('salary_rules','code')->whereNull('deleted_at')],
            'name'         => ['required','string','max:150'],
            'type'         => ['required', Rule::in(['earning','deduction'])],
            'amount_type'  => ['required', Rule::in(['fixed','percent'])],
            'fixed_amount' => ['nullable','numeric','min:0'],
            'percent'      => ['nullable','numeric','min:0','max:100'],
            'percent_base' => ['nullable', Rule::in(['basic','gross'])],
            'active'       => ['nullable','boolean'],
            'description'  => ['nullable','string'],
        ]);

        // Konsistensi field berdasarkan amount_type
        if ($data['amount_type'] === 'fixed') {
            $data['percent'] = null;
            $data['percent_base'] = null;
            $data['fixed_amount'] = $data['fixed_amount'] ?? 0;
        } else {
            $data['fixed_amount'] = null;
            $data['percent'] = $data['percent'] ?? 0;
            $data['percent_base'] = $data['percent_base'] ?? 'basic';
        }

        $data['active'] = (bool)($data['active'] ?? true);

        $row = SalaryRule::create($data);
        return response()->json($row, 201);
    }

    public function show($id)
    {
        $row = SalaryRule::find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = SalaryRule::find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);

        $data = $request->validate([
            'code'         => ['required','string','max:30', Rule::unique('salary_rules','code')->ignore($row->id)->whereNull('deleted_at')],
            'name'         => ['required','string','max:150'],
            'type'         => ['required', Rule::in(['earning','deduction'])],
            'amount_type'  => ['required', Rule::in(['fixed','percent'])],
            'fixed_amount' => ['nullable','numeric','min:0'],
            'percent'      => ['nullable','numeric','min:0','max:100'],
            'percent_base' => ['nullable', Rule::in(['basic','gross'])],
            'active'       => ['nullable','boolean'],
            'description'  => ['nullable','string'],
        ]);

        if ($data['amount_type'] === 'fixed') {
            $data['percent'] = null;
            $data['percent_base'] = null;
            $data['fixed_amount'] = $data['fixed_amount'] ?? 0;
        } else {
            $data['fixed_amount'] = null;
            $data['percent'] = $data['percent'] ?? 0;
            $data['percent_base'] = $data['percent_base'] ?? 'basic';
        }

        $row->update($data);
        return response()->json($row->refresh());
    }

    public function destroy($id)
    {
        $row = SalaryRule::find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);
        $row->delete();
        return response()->json(['message'=>'Deleted']);
    }
}
