<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        $q = Employee::query()
            ->with(['department','jobPosition','manager'])
            ->search($request->get('search'))
            ->department($request->get('department_id'))
            ->jobPosition($request->get('job_position_id'))
            ->manager($request->get('manager_id'))
            ->status($request->get('status'))
            ->employmentType($request->get('employment_type'))
            ->gender($request->get('gender'))
            ->orderByDesc('id');

        return response()->json($q->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'emp_no' => [
                'required','string','max:30',
                Rule::unique('employees','emp_no')->whereNull('deleted_at'),
            ],
            'first_name' => ['required','string','max:100'],
            'last_name'  => ['nullable','string','max:100'],
            'full_name'  => ['nullable','string','max:200'], // auto-terisi dari first+last jika kosong
            'email'      => [
                'required','email','max:191',
                Rule::unique('employees','email')->whereNull('deleted_at'),
            ],
            'phone'      => ['nullable','string','max:30'],
            'department_id'  => ['required','integer','exists:departments,id'],
            'job_position_id'=> ['required','integer','exists:job_positions,id'],
            'manager_id'     => ['nullable','integer','exists:employees,id'],
            'hire_date'      => ['required','date'],
            'employment_type'=> ['required', Rule::in(['permanent','contract','intern'])],
            'status'         => ['required', Rule::in(['active','inactive'])],
            'gender'         => ['nullable', Rule::in(['male','female','other'])],
            'dob'            => ['nullable','date'],
            'address'        => ['nullable','string'],
            'city'           => ['nullable','string'],
            'province'       => ['nullable','string'],
            'country'        => ['nullable','string'],
            'zip'            => ['nullable','string','max:10'],
            'avatar_path'    => ['nullable','string'],
        ]);

        // Cegah self manager pada create: tidak relevan karena belum punya id;
        // tapi jika client mengirim manager_id null ya aman.

        $row = Employee::create($data);

        return response()->json(
            $row->load(['department','jobPosition','manager']),
            201
        );
    }

    public function show($id)
    {
        $row = Employee::with(['department','jobPosition','manager'])->find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = Employee::find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);

        $data = $request->validate([
            'emp_no' => [
                'required','string','max:30',
                Rule::unique('employees','emp_no')
                    ->ignore($row->id)
                    ->whereNull('deleted_at'),
            ],
            'first_name' => ['required','string','max:100'],
            'last_name'  => ['nullable','string','max:100'],
            'full_name'  => ['nullable','string','max:200'],
            'email'      => [
                'required','email','max:191',
                Rule::unique('employees','email')
                    ->ignore($row->id)
                    ->whereNull('deleted_at'),
            ],
            'phone'      => ['nullable','string','max:30'],
            'department_id'  => ['required','integer','exists:departments,id'],
            'job_position_id'=> ['required','integer','exists:job_positions,id'],
            'manager_id'     => [
                'nullable','integer','exists:employees,id',
                function ($attr, $value, $fail) use ($row) {
                    // cegah self manager
                    if ($value && (int)$value === (int)$row->id) {
                        $fail('manager_id tidak boleh menunjuk dirinya sendiri.');
                    }
                },
            ],
            'hire_date'      => ['required','date'],
            'employment_type'=> ['required', Rule::in(['permanent','contract','intern'])],
            'status'         => ['required', Rule::in(['active','inactive'])],
            'gender'         => ['nullable', Rule::in(['male','female','other'])],
            'dob'            => ['nullable','date'],
            'address'        => ['nullable','string'],
            'city'           => ['nullable','string'],
            'province'       => ['nullable','string'],
            'country'        => ['nullable','string'],
            'zip'            => ['nullable','string','max:10'],
            'avatar_path'    => ['nullable','string'],
        ]);

        $row->update($data);

        return response()->json(
            $row->refresh()->load(['department','jobPosition','manager'])
        );
    }

    public function destroy($id)
    {
        $row = Employee::find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);

        // Optional: cegah hapus jika masih menjadi manager orang lain
        // if ($row->subordinates()->exists()) {
        //     return response()->json(['message' => 'Karyawan ini masih menjadi atasan karyawan lain.'], 422);
        // }

        $row->delete();
        return response()->json(['message'=>'Deleted']);
    }
}
