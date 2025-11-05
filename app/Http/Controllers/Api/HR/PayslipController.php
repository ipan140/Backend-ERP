<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use App\Models\PayslipLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class PayslipController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        $q = Payslip::query()
            ->with(['employee','approver'])
            ->when($request->filled('employee_id'), fn($qq) => $qq->where('employee_id', $request->employee_id))
            ->when($request->filled('status'), fn($qq) => $qq->where('status', $request->status))
            ->when($request->filled('date_from') && $request->filled('date_to'), function($qq) use ($request) {
                $qq->where(function($w) use ($request) {
                    $w->whereBetween('period_start', [$request->date_from, $request->date_to])
                      ->orWhereBetween('period_end',   [$request->date_from, $request->date_to]);
                });
            }, function($qq) use ($request) {
                if ($from = $request->get('date_from')) $qq->whereDate('period_start', '>=', $from);
                if ($to   = $request->get('date_to'))   $qq->whereDate('period_end',   '<=', $to);
            })
            ->orderByDesc('id');

        return response()->json($q->paginate($perPage));
    }

    public function show($id)
    {
        $row = Payslip::with(['employee','approver','lines'])->find($id);
        if (!$row) return response()->json(['message'=>'Not found'], 404);
        return response()->json($row);
    }

    public function store(Request $request)
    {
        $data = $this->validateHeader($request, false);
        $lines = $this->validateLines($request);

        // Hitung amount line bila ada qty & rate
        $lines = $this->normalizeLines($lines);

        return DB::transaction(function () use ($data, $lines) {
            /** @var Payslip $p */
            $p = Payslip::create($data);

            // inject payslip_id + default seq
            $seq = 1;
            foreach ($lines as &$ln) {
                $ln['payslip_id'] = $p->id;
                $ln['seq'] = $ln['seq'] ?? $seq++;
            }
            PayslipLine::insert($lines);

            // reload lines lalu hitung total di model helper
            $p->load('lines');
            $p->recalcTotals();
            $p->save();

            return response()->json($p->load(['employee','approver','lines']), 201);
        });
    }

    public function update(Request $request, $id)
    {
        $p = Payslip::with('lines')->find($id);
        if (!$p) return response()->json(['message'=>'Not found'], 404);

        $data = $this->validateHeader($request, true, $p);
        $lines = $this->validateLines($request);
        $lines = $this->normalizeLines($lines);

        // Batasi update jika sudah paid
        if ($p->status === 'paid') {
            return response()->json(['message' => 'Payslip berstatus paid tidak bisa diubah.'], 422);
        }

        return DB::transaction(function () use ($p, $data, $lines) {
            $p->update($data);

            // replace semua lines (sederhana & aman)
            PayslipLine::where('payslip_id', $p->id)->delete();
            $seq = 1;
            foreach ($lines as &$ln) {
                $ln['payslip_id'] = $p->id;
                $ln['seq'] = $ln['seq'] ?? $seq++;
            }
            PayslipLine::insert($lines);

            $p->load('lines');
            $p->recalcTotals();
            $p->save();

            return response()->json($p->refresh()->load(['employee','approver','lines']));
        });
    }

    public function destroy($id)
    {
        $p = Payslip::find($id);
        if (!$p) return response()->json(['message'=>'Not found'], 404);

        if (in_array($p->status, ['approved','paid'], true)) {
            return response()->json(['message' => 'Payslip approved/paid tidak bisa dihapus.'], 422);
        }

        $p->delete();
        return response()->json(['message'=>'Deleted']);
    }

    /* ========= Workflow ringkas ========= */
    public function submit($id)
    {
        $p = Payslip::with('lines')->find($id);
        if (!$p) return response()->json(['message'=>'Not found'], 404);
        if ($p->status !== 'draft') return response()->json(['message'=>'Hanya draft yang bisa di-submit'], 422);

        if ($p->lines()->count() === 0) {
            return response()->json(['message'=>'Tidak bisa submit tanpa detail lines'], 422);
        }

        $p->status = 'submitted';
        $p->save();

        return response()->json($p->refresh());
    }

    public function approve(Request $request, $id)
    {
        $p = Payslip::find($id);
        if (!$p) return response()->json(['message'=>'Not found'], 404);

        $data = $request->validate([
            'approved_by' => ['required','integer','exists:employees,id'],
        ]);

        if (!in_array($p->status, ['submitted','draft'], true)) {
            return response()->json(['message' => 'Hanya draft/submitted yang bisa di-approve'], 422);
        }

        $p->status = 'approved';
        $p->approved_by = $data['approved_by'];
        $p->approved_at = now();
        $p->save();

        return response()->json($p->refresh());
    }

    public function pay($id)
    {
        $p = Payslip::find($id);
        if (!$p) return response()->json(['message'=>'Not found'], 404);
        if ($p->status !== 'approved') {
            return response()->json(['message'=>'Hanya approved yang bisa dibayar (paid)'], 422);
        }
        $p->status = 'paid';
        $p->posted_at = now();
        $p->save();

        return response()->json($p->refresh());
    }

    /* ========= Validators & Helpers ========= */
    protected function validateHeader(Request $request, bool $updating = false, ?Payslip $current = null): array
    {
        return $request->validate([
            'employee_id'   => ['required','integer','exists:employees,id'],
            'period_start'  => ['required','date'],
            'period_end'    => ['required','date','after_or_equal:period_start'],
            'status'        => ['nullable', Rule::in(['draft','submitted','approved','paid','cancelled'])],
            'basic_salary'  => ['nullable','numeric','min:0'],
            'notes'         => ['nullable','string'],
            // approved & posted diatur oleh flow, tapi tetap valid kalau dikirim saat update tertentu
            'approved_by'   => ['nullable','integer','exists:employees,id'],
            'approved_at'   => ['nullable','date'],
            'posted_at'     => ['nullable','date'],
        ]);
    }

    protected function validateLines(Request $request): array
    {
        $data = $request->validate([
            'lines'                    => ['nullable','array'],
            'lines.*.id'               => ['nullable','integer'], // tidak digunakan (kita replace semua)
            'lines.*.seq'              => ['nullable','integer','min:1'],
            'lines.*.code'             => ['nullable','string','max:50'],
            'lines.*.name'             => ['required_with:lines','string','max:150'],
            'lines.*.type'             => ['required_with:lines', Rule::in(['earning','deduction'])],
            'lines.*.qty'              => ['nullable','numeric','min:0'],
            'lines.*.rate'             => ['nullable','numeric','min:0'],
            'lines.*.amount'           => ['nullable','numeric'], // dihitung jika qty/rate ada
            'lines.*.notes'            => ['nullable','string'],
        ]);

        return $data['lines'] ?? [];
    }

    protected function normalizeLines(array $lines): array
    {
        foreach ($lines as &$ln) {
            $qty  = isset($ln['qty'])  ? (float)$ln['qty']  : null;
            $rate = isset($ln['rate']) ? (float)$ln['rate'] : null;

            if (($qty !== null) && ($rate !== null) && (!isset($ln['amount']) || $ln['amount'] === null)) {
                $ln['amount'] = round($qty * $rate, 2);
            }

            // pastikan amount tidak negatif (untuk deduction gunakan type, bukan minus)
            if (isset($ln['amount'])) {
                $ln['amount'] = round(max(0, (float)$ln['amount']), 2);
            }
        }
        return $lines;
    }
}
