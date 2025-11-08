<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\MoveLine;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * GET /api/accounting/reports
     * Daftar report yang tersedia.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'available' => [
                'trial-balance'   => 'Trial Balance',
                'general-ledger'  => 'General Ledger',
                // sisanya opsional: implement kapan-kapan
                'income-statement'=> 'Income Statement (coming soon)',
                'balance-sheet'   => 'Balance Sheet (coming soon)',
                'cash-flow'       => 'Cash Flow (coming soon)',
                'aged-receivable' => 'Aged Receivable (coming soon)',
            ],
        ]);
    }

    /**
     * GET /api/accounting/reports/{type}
     * Router kecil yang meneruskan ke method masing-masing report.
     */
    public function show(string $type, Request $r): JsonResponse
    {
        $type = strtolower($type);

        return match ($type) {
            'trial-balance'  => $this->trialBalance($r),
            'general-ledger' => $this->generalLedger($r),

            // --------- placeholder untuk report lain ----------
            'income-statement' => response()->json([
                'message' => 'Income Statement belum diimplementasi.'
            ], 501),
            'balance-sheet' => response()->json([
                'message' => 'Balance Sheet belum diimplementasi.'
            ], 501),
            'cash-flow' => response()->json([
                'message' => 'Cash Flow belum diimplementasi.'
            ], 501),
            'aged-receivable' => response()->json([
                'message' => 'Aged Receivable belum diimplementasi.'
            ], 501),
            // --------------------------------------------------

            default => response()->json(['message' => 'Unknown report type.'], 404),
        };
    }

    /**
     * GET /api/accounting/reports/trial-balance?company_id=&date_from=&date_to=
     */
    public function trialBalance(Request $r): JsonResponse
    {
        $r->validate([
            'company_id' => 'nullable|exists:companies,id',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date',
        ]);

        $companyId = $r->get('company_id');
        $from = $r->get('date_from');
        $to   = $r->get('date_to');

        $rows = MoveLine::query()
            ->whereHas('move', function ($q) use ($companyId, $from, $to) {
                $q->where('status', 'posted')
                  ->when($companyId, fn($qq) => $qq->where('company_id', $companyId))
                  ->when($from,      fn($qq) => $qq->whereDate('date', '>=', $from))
                  ->when($to,        fn($qq) => $qq->whereDate('date', '<=', $to));
            })
            ->selectRaw('account_id, SUM(debit) as debit, SUM(credit) as credit')
            ->groupBy('account_id')
            ->with(['account:id,code,name,type'])
            ->get()
            ->map(function ($l) {
                $debit   = (float) $l->debit;
                $credit  = (float) $l->credit;
                $balance = $debit - $credit;

                return [
                    'account_id' => $l->account_id,
                    'code'       => $l->account->code ?? null,
                    'name'       => $l->account->name ?? null,
                    'type'       => $l->account->type ?? null,
                    'debit'      => $debit,
                    'credit'     => $credit,
                    'balance'    => $balance,
                ];
            })
            ->sortBy('code')
            ->values();

        return response()->json($rows);
    }

    /**
     * GET /api/accounting/reports/general-ledger?account_id=&company_id=&date_from=&date_to=&include_opening=1&per_page=50&page=1
     */
    public function generalLedger(Request $r): JsonResponse
    {
        $r->validate([
            'account_id'       => 'required|exists:accounts,id',
            'company_id'       => 'nullable|exists:companies,id',
            'date_from'        => 'nullable|date',
            'date_to'          => 'nullable|date',
            'include_opening'  => 'nullable|boolean',
            'per_page'         => 'nullable|integer|min:10|max:500',
            'page'             => 'nullable|integer|min:1',
        ]);

        $account   = Account::select('id','code','name','company_id')->findOrFail($r->account_id);
        $companyId = $r->get('company_id');
        $from      = $r->get('date_from');
        $to        = $r->get('date_to');
        $withOb    = (bool) $r->boolean('include_opening', false);
        $perPage   = (int) ($r->get('per_page', 0)); // 0 = non-paginated

        if ($companyId && (int)$account->company_id !== (int)$companyId) {
            return response()->json([
                'message' => 'Account does not belong to the specified company.'
            ], 422);
        }

        // opening balance (sebelum date_from)
        $opening = 0.0;
        if ($withOb && $from) {
            $ob = MoveLine::query()
                ->where('account_id', $account->id)
                ->whereHas('move', function ($q) use ($companyId, $from) {
                    $q->where('status', 'posted')
                      ->when($companyId, fn($qq) => $qq->where('company_id', $companyId))
                      ->whereDate('date', '<', $from);
                })
                ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
                ->first();

            $opening = (float) ($ob->d ?? 0) - (float) ($ob->c ?? 0);
        }

        // query dasar GL
        $base = MoveLine::query()
            ->where('account_id', $account->id)
            ->whereHas('move', function ($q) use ($companyId, $from, $to) {
                $q->where('status', 'posted')
                  ->when($companyId, fn($qq) => $qq->where('company_id', $companyId))
                  ->when($from,      fn($qq) => $qq->whereDate('date', '>=', $from))
                  ->when($to,        fn($qq) => $qq->whereDate('date', '<=', $to));
            })
            ->with(['move:id,number,date,ref'])
            ->orderByRaw('move_id asc, id asc');

        if ($perPage > 0) {
            $p = $base->paginate($perPage)->through(function ($l) {
                return [
                    'date'   => optional($l->move?->date)->toDateString(),
                    'number' => $l->move->number ?? null,
                    'ref'    => $l->move->ref ?? null,
                    'label'  => $l->label,
                    'debit'  => (float) $l->debit,
                    'credit' => (float) $l->credit,
                ];
            });

            $totals = [
                'debit'   => (float) $p->getCollection()->sum('debit'),
                'credit'  => (float) $p->getCollection()->sum('credit'),
                'opening' => $opening,
                'balance' => $opening + (float)$p->getCollection()->sum('debit') - (float)$p->getCollection()->sum('credit'),
            ];

            return response()->json([
                'account' => ['id'=>$account->id,'code'=>$account->code,'name'=>$account->name],
                'opening' => $opening,
                'rows'    => $p->items(),
                'totals'  => $totals,
                'meta'    => [
                    'current_page' => $p->currentPage(),
                    'per_page'     => $p->perPage(),
                    'total'        => $p->total(),
                    'last_page'    => $p->lastPage(),
                ],
            ]);
        }

        // non-paginated
        $rows = $base->get()->map(function ($l) {
            return [
                'date'   => optional($l->move?->date)->toDateString(),
                'number' => $l->move->number ?? null,
                'ref'    => $l->move->ref ?? null,
                'label'  => $l->label,
                'debit'  => (float) $l->debit,
                'credit' => (float) $l->credit,
            ];
        });

        $totals = [
            'debit'   => (float) $rows->sum('debit'),
            'credit'  => (float) $rows->sum('credit'),
            'opening' => $opening,
            'balance' => $opening + (float)$rows->sum('debit') - (float)$rows->sum('credit'),
        ];

        return response()->json([
            'account' => ['id'=>$account->id,'code'=>$account->code,'name'=>$account->name],
            'opening' => $opening,
            'rows'    => $rows,
            'totals'  => $totals,
        ]);
    }
}
