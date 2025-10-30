<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $q = Contract::query()
            ->with(['employee','structure'])
            ->when($request->filled('employee_id'), fn($qq) => $qq->where('employee_id', $request->employee_id))
            ->when($request->filled('status'),      fn($qq) => $qq->where('status', $request->status))
            ->when($request->filled('active_only'), function ($qq) {
                $qq->where('status','active')
                   ->where(function($sub){
                       $sub->whereNull('end_date')->orWhere('end_date','>=', now()->toDateString());
                   });
            });

        return response()->json($q->orderByDesc('id')->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'   => ['required','integer','exists:employees,id'],
            'contract_no'   => ['required','string','max:50','unique:contracts,contract_no'],
            'contract_type' => ['required', Rule::in(['permanent','contract','intern'])],
            'start_date'    => ['required','date'],
            'end_date'      => ['nullable','date','after_or_equal:start_date'],
            'base_salary'   => ['required','numeric','min:0'],
            'currency'      => ['required','string','max:10'], // e.g. "IDR"
            'pay_frequency' => ['required', Rule::in(['monthly','weekly'])],
            'structure_id'  => ['required','integer','exists:salary_structures,id'],
            'status'        => ['required', Rule::in(['draft','active','ended','cancelled'])],
            'note'          => ['nullable','string'],
        ]);

        $row = Contract::create($data);

        return response()->json($row->load(['employee','structure']), 201);
    }

    public function show($id)
    {
        $row = Contract::with(['employee','structure'])->find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = Contract::find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);

        $data = $request->validate([
            'employee_id'   => ['required','integer','exists:employees,id'],
            'contract_no'   => ['required','string','max:50','unique:contracts,contract_no,' . $row->id],
            'contract_type' => ['required', Rule::in(['permanent','contract','intern'])],
            'start_date'    => ['required','date'],
            'end_date'      => ['nullable','date','after_or_equal:start_date'],
            'base_salary'   => ['required','numeric','min:0'],
            'currency'      => ['required','string','max:10'],
            'pay_frequency' => ['required', Rule::in(['monthly','weekly'])],
            'structure_id'  => ['required','integer','exists:salary_structures,id'],
            'status'        => ['required', Rule::in(['draft','active','ended','cancelled'])],
            'note'          => ['nullable','string'],
        ]);

        $row->update($data);

        return response()->json($row->refresh()->load(['employee','structure']));
    }

    public function destroy($id)
    {
        $row = Contract::find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);
        $row->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
