<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountJournal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class JournalController extends Controller
{
    /**
     * GET /api/accounting/journals
     * Params: search, company_id, type, active, per_page, page, sort[code|name|type|active], dir[asc|desc]
     */
    public function index(Request $r)
    {
        $perPage = (int) $r->get('per_page', 15);
        if ($perPage < 1 || $perPage > 200) $perPage = 15;

        $allowedSort = ['code', 'name', 'type', 'active', 'id'];
        $sort = $r->get('sort', 'code');
        if (!in_array($sort, $allowedSort, true)) $sort = 'code';
        $dir = $r->get('dir') === 'desc' ? 'desc' : 'asc';

        $search = trim((string) $r->get('search', ''));

        return AccountJournal::query()
            ->when($r->filled('company_id'), fn ($q) => $q->where('company_id', $r->company_id))
            ->when($r->filled('type'),       fn ($q) => $q->where('type', $r->type))
            ->when($r->has('active') && $r->active !== '', fn ($q) => $q->where('active', (bool) $r->active))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    /**
     * POST /api/accounting/journals
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'company_id'       => ['required','exists:companies,id'],
            'code'             => [
                'required','string','max:10',
                // unik per company
                Rule::unique('account_journals', 'code')->where(fn ($q) => $q->where('company_id', $r->company_id)),
            ],
            'name'             => ['required','string','max:100'],
            'type'             => ['required','in:bank,cash,sale,purchase,general'],
            'sequence_prefix'  => ['nullable','string','max:50'],
            'sequence_padding' => ['nullable','integer','min:3','max:10'],
            'active'           => ['nullable','boolean'],
        ]);

        // normalisasi kecil
        $data['code'] = strtoupper(trim($data['code']));
        $data['sequence_padding'] = $data['sequence_padding'] ?? 6;
        if (!array_key_exists('active', $data)) $data['active'] = true;

        $journal = AccountJournal::create($data);

        return response()->json($journal, 201);
    }

    /**
     * GET /api/accounting/journals/{journal}
     */
    public function show(AccountJournal $journal)
    {
        return $journal;
    }

    /**
     * PUT/PATCH /api/accounting/journals/{journal}
     */
    public function update(Request $r, AccountJournal $journal)
    {
        $data = $r->validate([
            'company_id'       => ['sometimes','required','exists:companies,id'],
            'code'             => [
                'sometimes','string','max:10',
                Rule::unique('account_journals', 'code')
                    ->ignore($journal->id)
                    ->where(function ($q) use ($r, $journal) {
                        $companyId = $r->get('company_id', $journal->company_id);
                        return $q->where('company_id', $companyId);
                    }),
            ],
            'name'             => ['sometimes','string','max:100'],
            'type'             => ['sometimes','in:bank,cash,sale,purchase,general'],
            'sequence_prefix'  => ['sometimes','nullable','string','max:50'],
            'sequence_padding' => ['sometimes','nullable','integer','min:3','max:10'],
            'active'           => ['sometimes','boolean'],
        ]);

        if (array_key_exists('code', $data)) {
            $data['code'] = strtoupper(trim($data['code']));
        }

        $journal->update($data);

        return $journal;
    }

    /**
     * DELETE /api/accounting/journals/{journal}
     */
    public function destroy(AccountJournal $journal)
    {
        abort_if(method_exists($journal, 'moves') && $journal->moves()->exists(), 422, 'Journal already used.');
        $journal->delete();

        return response()->noContent();
    }

    /**
     * POST /api/accounting/journals/{journal}/toggle-active
     */
    public function toggleActive(AccountJournal $journal)
    {
        $journal->active = !$journal->active;
        $journal->save();

        return response()->json(['active' => $journal->active]);
    }

    /**
     * POST /api/accounting/journals/bulk-delete
     * Body: { "ids": [1,2,3] }
     */
    public function bulkDelete(Request $r)
    {
        $ids = (array) $r->input('ids', []);
        if (!$ids) return response()->json(['deleted' => 0]);

        $deleted = 0;

        DB::transaction(function () use (&$deleted, $ids) {
            $rows = AccountJournal::whereIn('id', $ids)->get();

            foreach ($rows as $j) {
                if (method_exists($j, 'moves') && $j->moves()->exists()) {
                    // lewati yang sudah dipakai
                    continue;
                }
                $j->delete();
                $deleted++;
            }
        });

        return response()->json(['deleted' => $deleted]);
    }

    /**
     * GET /api/accounting/journals/{journal}/next-number
     * Menghasilkan nomor dokumen berdasarkan prefix & padding.
     * Token yang didukung pada prefix: %Y (tahun 4), %y (tahun 2), %m (bulan 2)
     */
    public function nextNumber(AccountJournal $journal)
    {
        $prefix = $journal->sequence_prefix ?? '';
        $pad    = max(3, min(10, (int) $journal->sequence_padding ?: 6));

        $prefix = str_replace(
            ['%Y','%y','%m'],
            [now()->format('Y'), now()->format('y'), now()->format('m')],
            $prefix
        );

        // cari nomor terakhir yang memakai prefix sama
        $last = null;
        if (method_exists($journal, 'moves')) {
            $last = $journal->moves()
                ->where('number', 'like', $prefix.'%')
                ->orderByDesc('id')
                ->value('number');
        }

        $nextSeq = 1;
        if ($last && preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $last, $m)) {
            $nextSeq = ((int) $m[1]) + 1;
        }

        $number = $prefix . str_pad((string) $nextSeq, $pad, '0', STR_PAD_LEFT);

        return response()->json([
            'prefix'   => $prefix,
            'padding'  => $pad,
            'sequence' => $nextSeq,
            'number'   => $number,
        ]);
    }
}
