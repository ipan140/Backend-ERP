<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $q = Attendance::query()
            ->with(['employee','shift'])
            ->when($request->filled('employee_id'), fn($qq) => $qq->where('employee_id', $request->employee_id))
            ->when($request->filled('shift_id'),    fn($qq) => $qq->where('shift_id', $request->shift_id))
            ->when($request->filled('date_from'),   fn($qq) => $qq->whereDate('check_in', '>=', $request->date_from))
            ->when($request->filled('date_to'),     fn($qq) => $qq->whereDate('check_in', '<=', $request->date_to));

        return response()->json(
            $q->orderByDesc('id')->paginate($perPage)
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required','integer','exists:employees,id'],
            'check_in'    => ['nullable','date'],
            'check_out'   => ['nullable','date','after:check_in'],
            'work_duration_minutes' => ['nullable','integer','min:0'],
            'source'      => ['required', Rule::in(['kiosk','mobile','manual'])],
            'shift_id'    => ['nullable','integer','exists:shifts,id'],
            'note'        => ['nullable','string'],
        ]);

        $row = Attendance::create($data);
        $row->recomputeDuration()->save();

        return response()->json($row->load(['employee','shift']), 201);
    }

    public function show($id)
    {
        $row = Attendance::with(['employee','shift'])->find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = Attendance::find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);

        $data = $request->validate([
            'employee_id' => ['required','integer','exists:employees,id'],
            'check_in'    => ['nullable','date'],
            'check_out'   => ['nullable','date','after:check_in'],
            'work_duration_minutes' => ['nullable','integer','min:0'],
            'source'      => ['required', Rule::in(['kiosk','mobile','manual'])],
            'shift_id'    => ['nullable','integer','exists:shifts,id'],
            'note'        => ['nullable','string'],
        ]);

        $row->fill($data)->recomputeDuration()->save();

        return response()->json($row->refresh()->load(['employee','shift']));
    }

    public function destroy($id)
    {
        $row = Attendance::find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);
        $row->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /** KIOSK / MOBILE: buka sesi kehadiran */
    public function checkIn(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required','integer','exists:employees,id'],
            'shift_id'    => ['nullable','integer','exists:shifts,id'],
            'source'      => ['required', Rule::in(['kiosk','mobile','manual'])],
            'note'        => ['nullable','string'],
        ]);

        // larang double session (belum checkout)
        $open = Attendance::where('employee_id', $data['employee_id'])
            ->whereNull('check_out')->orderByDesc('id')->first();

        if ($open) {
            return response()->json(['message' => 'Open attendance exists, checkout first'], 422);
        }

        $row = Attendance::create([
            'employee_id' => $data['employee_id'],
            'shift_id'    => $data['shift_id'] ?? null,
            'source'      => $data['source'],
            'note'        => $data['note'] ?? null,
            'check_in'    => now(),
        ]);

        return response()->json($row->load(['employee','shift']), 201);
    }

    /** KIOSK / MOBILE: tutup sesi kehadiran */
    public function checkOut(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required','integer','exists:employees,id'],
            'note'        => ['nullable','string'],
        ]);

        $row = Attendance::where('employee_id', $data['employee_id'])
            ->whereNull('check_out')->orderByDesc('id')->first();

        if (!$row) {
            return response()->json(['message' => 'No open attendance'], 422);
        }

        $row->check_out = now();
        if (!empty($data['note'])) {
            $row->note = trim(($row->note ? $row->note.' | ' : '').$data['note']);
        }

        $row->recomputeDuration()->save();

        return response()->json($row->load(['employee','shift']));
    }
}
