<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /* =========================================================================
     | Helpers
     |========================================================================= */

    /**
     * Ambil employee_id:
     * - Jika request membawa employee_id -> validasi & pakai itu (mode admin/kiosk).
     * - Jika tidak, coba mapping dari user login:
     *   1) kolom users.employee_id (jika ada),
     *   2) relasi $user->employee,
     *   3) kolom employees.user_id (HANYA jika kolomnya ada).
     */
    protected function resolveEmployeeId(Request $request): int
    {
        // 1) Dari payload
        if ($request->filled('employee_id')) {
            $empId = (int) $request->integer('employee_id');
            if (!Employee::whereKey($empId)->exists()) {
                abort(422, 'Invalid employee_id.');
            }
            return $empId;
        }

        // 2) Dari user login
        $user = $request->user();
        if (!$user) abort(401, 'Unauthenticated.');

        // 2a) users.employee_id (kalau ada kolom/properti ini)
        if (property_exists($user, 'employee_id') || isset($user->employee_id)) {
            $empId = (int) ($user->employee_id ?? 0);
            if ($empId && Employee::whereKey($empId)->exists()) {
                return $empId;
            }
        }

        // 2b) relasi $user->employee (jika diset di model User)
        if (method_exists($user, 'employee')) {
            $emp = $user->relationLoaded('employee') ? $user->getRelation('employee') : $user->employee()->first();
            if ($emp) {
                return (int) $emp->id;
            }
        }

        // 2c) employees.user_id — cek hanya jika kolomnya memang ada
        try {
            if (DB::getSchemaBuilder()->hasColumn('employees', 'user_id')) {
                $empId = Employee::where('user_id', $user->id)->value('id');
                if ($empId) return (int) $empId;
            }
        } catch (\Throwable $e) {
            // abaikan jika driver tidak mendukung pengecekan kolom
        }

        // 3) Tidak ada mapping
        abort(422, 'No employee is mapped to the current user. Please pass employee_id or link this user to an employee.');
    }

    /* =========================================================================
     | CRUD (Admin/HR)
     |========================================================================= */

    public function index(Request $request)
    {
        $perPage  = (int) $request->integer('per_page', 15);
        $search   = trim((string) $request->get('search', ''));
        $monthStr = trim((string) $request->get('month', '')); // YYYY-MM

        $q = Attendance::query()
            ->with([
                // gunakan kolom yang benar dari tabel employees
                'employee:id,emp_no,full_name,first_name,last_name',
                'shift:id,name',
            ])
            ->when($request->filled('employee_id'), fn ($qq) => $qq->where('employee_id', (int) $request->integer('employee_id')))
            ->when($request->filled('shift_id'),    fn ($qq) => $qq->where('shift_id', (int) $request->integer('shift_id')))
            ->when($request->filled('date_from'),   fn ($qq) => $qq->whereDate('check_in', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'),     fn ($qq) => $qq->whereDate('check_in', '<=', $request->input('date_to')));

        if ($monthStr !== '') {
            try {
                $start = Carbon::createFromFormat('Y-m', $monthStr)->startOfMonth();
                $end   = (clone $start)->endOfMonth();
                $q->where(function ($qq) use ($start, $end) {
                    $qq->whereBetween(DB::raw('DATE(check_in)'),  [$start->toDateString(), $end->toDateString()])
                       ->orWhereBetween(DB::raw('DATE(check_out)'), [$start->toDateString(), $end->toDateString()]);
                });
            } catch (\Throwable $e) {
                // format month tidak valid -> abaikan
            }
        }

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('note', 'like', "%{$search}%")
                   ->orWhereDate('check_in',  $search)
                   ->orWhereDate('check_out', $search);
            });
        }

        return response()->json(
            $q->orderByDesc('id')->paginate($perPage)
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'           => ['required','integer','exists:employees,id'],
            'check_in'              => ['nullable','date'],
            'check_out'             => ['nullable','date','after_or_equal:check_in'],
            'work_duration_minutes' => ['nullable','integer','min:0'],
            'source'                => ['required', Rule::in(['kiosk','mobile','manual'])],
            'shift_id'              => ['nullable','integer','exists:shifts,id'],
            'note'                  => ['nullable','string'],
        ]);

        $row = Attendance::create($data);
        $row->recomputeDuration()->save();

        return response()->json($row->load(['employee','shift']), 201);
    }

    public function show(int $id)
    {
        $row = Attendance::with(['employee','shift'])->find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);
        return response()->json($row);
    }

    public function update(Request $request, int $id)
    {
        $row = Attendance::find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);

        $data = $request->validate([
            'employee_id'           => ['sometimes','integer','exists:employees,id'],
            'check_in'              => ['sometimes','nullable','date'],
            'check_out'             => ['sometimes','nullable','date','after_or_equal:check_in'],
            'work_duration_minutes' => ['sometimes','nullable','integer','min:0'],
            'source'                => ['sometimes','required', Rule::in(['kiosk','mobile','manual'])],
            'shift_id'              => ['sometimes','nullable','integer','exists:shifts,id'],
            'note'                  => ['sometimes','nullable','string'],
        ]);

        $row->fill($data)->recomputeDuration()->save();

        return response()->json($row->refresh()->load(['employee','shift']));
    }

    public function destroy(int $id)
    {
        $row = Attendance::find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);
        $row->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /* =========================================================================
     | Check-in / Check-out
     |========================================================================= */

    /** Buka sesi kehadiran */
    public function checkIn(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['sometimes','integer','exists:employees,id'],
            'shift_id'    => ['sometimes','nullable','integer','exists:shifts,id'],
            'source'      => ['sometimes','required', Rule::in(['kiosk','mobile','manual'])],
            'note'        => ['sometimes','nullable','string'],
        ]);

        $employeeId = $this->resolveEmployeeId($request);
        $source     = $data['source'] ?? 'mobile'; // default wajar untuk self-service

        // larang double session (belum checkout)
        $open = Attendance::where('employee_id', $employeeId)
            ->whereNull('check_out')
            ->orderByDesc('id')
            ->first();

        if ($open) {
            return response()->json(['message' => 'Open attendance exists, checkout first'], 422);
        }

        $row = Attendance::create([
            'employee_id' => $employeeId,
            'shift_id'    => $data['shift_id'] ?? null,
            'source'      => $source,
            'note'        => $data['note'] ?? null,
            'check_in'    => now(),
        ]);

        return response()->json($row->load(['employee','shift']), 201);
    }

    /** Tutup sesi kehadiran */
    public function checkOut(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['sometimes','integer','exists:employees,id'],
            'note'        => ['sometimes','nullable','string'],
        ]);

        $employeeId = $this->resolveEmployeeId($request);

        $row = Attendance::where('employee_id', $employeeId)
            ->whereNull('check_out')
            ->orderByDesc('id')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'No open attendance'], 422);
        }

        $row->check_out = now();
        if (array_key_exists('note', $data) && $data['note'] !== null && $data['note'] !== '') {
            $row->note = trim(($row->note ? $row->note.' | ' : '').$data['note']);
        }

        $row->recomputeDuration()->save();

        return response()->json($row->load(['employee','shift']));
    }

    /* =========================================================================
     | My Attendance (untuk komponen MyAttendance.vue)
     |========================================================================= */

    /** GET /api/my-attendance/today */
    public function myToday(Request $request)
    {
        $employeeId = $this->resolveEmployeeId($request);

        $today = Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereDate('check_in', now()->toDateString())
            ->orderByDesc('id')
            ->first();

        if (!$today) {
            $today = Attendance::query()
                ->where('employee_id', $employeeId)
                ->whereDate('check_out', now()->toDateString())
                ->orderByDesc('id')
                ->first();
        }

        return response()->json($today ? [
            'check_in'  => optional($today->check_in)->toISOString(),
            'check_out' => optional($today->check_out)->toISOString(),
            'note'      => $today->note,
        ] : null);
    }

    /** GET /api/my-attendance?month=YYYY-MM&search=&page=&per_page= */
    public function myIndex(Request $request)
    {
        $employeeId = $this->resolveEmployeeId($request);
        $perPage  = (int) $request->integer('per_page', 10);
        $search   = trim((string) $request->get('search', ''));
        $monthStr = trim((string) $request->get('month', ''));

        $q = Attendance::query()->where('employee_id', $employeeId);

        if ($monthStr !== '') {
            try {
                $start = Carbon::createFromFormat('Y-m', $monthStr)->startOfMonth();
                $end   = (clone $start)->endOfMonth();
                $q->where(function ($qq) use ($start, $end) {
                    $qq->whereBetween(DB::raw('DATE(check_in)'),  [$start->toDateString(), $end->toDateString()])
                       ->orWhereBetween(DB::raw('DATE(check_out)'), [$start->toDateString(), $end->toDateString()]);
                });
            } catch (\Throwable $e) { /* abaikan */ }
        }

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('note', 'like', "%{$search}%")
                   ->orWhereDate('check_in',  $search)
                   ->orWhereDate('check_out', $search);
            });
        }

        $q->orderByDesc('id');

        $paginator = $q->paginate($perPage);

        $paginator->getCollection()->transform(function (Attendance $r) {
            $dateStr = $r->check_in?->toDateString() ?? $r->check_out?->toDateString();
            return [
                'id'        => $r->id,
                'date'      => $dateStr,
                'check_in'  => $r->check_in?->toISOString(),
                'check_out' => $r->check_out?->toISOString(),
                'note'      => $r->note,
            ];
        });

        return response()->json($paginator);
    }

    /* =========================================================================
     | Tambahan: status sesi open & ringkasan bulanan
     |========================================================================= */

    /** Admin/HR: cek sesi open (opsional)
     *  GET /api/hr/attendances/open?employee_id=...
     */
    public function open(Request $request)
    {
        $q = Attendance::query()->whereNull('check_out');

        if ($request->filled('employee_id')) {
            $q->where('employee_id', (int) $request->integer('employee_id'));
        }

        $rows = $q->with(['employee:id,emp_no,full_name,first_name,last_name', 'shift:id,name'])
                  ->orderByDesc('id')
                  ->get();

        return response()->json($rows);
    }

    /** Self-service: cek sesi open milik user login (opsional)
     *  GET /api/my-attendance/open
     */
    public function myOpen(Request $request)
    {
        $employeeId = $this->resolveEmployeeId($request);

        $row = Attendance::where('employee_id', $employeeId)
            ->whereNull('check_out')
            ->orderByDesc('id')
            ->first();

        return response()->json($row ? [
            'id'        => $row->id,
            'check_in'  => optional($row->check_in)->toISOString(),
            'note'      => $row->note,
            'shift'     => $row->shift?->only(['id','name']),
        ] : null);
    }

    /** Ringkasan menit per hari dalam sebulan (opsional)
     *  GET /api/my-attendance/summary?month=YYYY-MM
     */
    public function myMonthlySummary(Request $request)
    {
        $employeeId = $this->resolveEmployeeId($request);
        $monthStr   = trim((string) $request->get('month', now()->format('Y-m')));

        try {
            $start = Carbon::createFromFormat('Y-m', $monthStr)->startOfMonth();
            $end   = (clone $start)->endOfMonth();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid month format, expected YYYY-MM'], 422);
        }

        $rows = Attendance::where('employee_id', $employeeId)
            ->where(function ($qq) use ($start, $end) {
                $qq->whereBetween(DB::raw('DATE(check_in)'),  [$start->toDateString(), $end->toDateString()])
                   ->orWhereBetween(DB::raw('DATE(check_out)'), [$start->toDateString(), $end->toDateString()]);
            })
            ->orderBy('check_in')
            ->get(['id','check_in','check_out','work_duration_minutes','note']);

        $sum = [];
        foreach ($rows as $r) {
            $day = optional($r->check_in ?? $r->check_out)->toDateString();
            if (!$day) continue;

            $mins = is_numeric($r->work_duration_minutes) ? (int) $r->work_duration_minutes : null;
            if ($mins === null && $r->check_in && $r->check_out) {
                $mins = max(0, (int) round($r->check_out->diffInMinutes($r->check_in)));
            }
            $mins = $mins ?? 0;

            if (!isset($sum[$day])) $sum[$day] = 0;
            $sum[$day] += $mins;
        }

        $out = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $d = $cursor->toDateString();
            $mins = $sum[$d] ?? 0;
            $out[] = [
                'date'   => $d,
                'minutes'=> $mins,
                'hhmm'   => sprintf('%02d:%02d', intdiv($mins,60), $mins%60),
            ];
            $cursor->addDay();
        }

        return response()->json([
            'month' => $monthStr,
            'days'  => $out,
            'total_minutes' => array_sum(array_column($out, 'minutes')),
        ]);
    }
}
