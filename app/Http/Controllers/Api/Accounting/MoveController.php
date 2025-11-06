<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Move;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MoveController extends Controller
{
    public function index(Request $r)
    {
        $q = Move::query()->with('lines.account','journal')
            ->when($r->filled('company_id'), fn($qq)=>$qq->where('company_id',$r->company_id))
            ->when($r->filled('journal_id'), fn($qq)=>$qq->where('journal_id',$r->journal_id))
            ->when($r->filled('status'), fn($qq)=>$qq->where('status',$r->status))
            ->when($r->filled('date_from'), fn($qq)=>$qq->whereDate('date','>=',$r->date_from))
            ->when($r->filled('date_to'), fn($qq)=>$qq->whereDate('date','<=',$r->date_to))
            ->orderByDesc('date')->orderByDesc('id');

        return $q->paginate((int)$r->get('per_page',15));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'company_id' => 'required|exists:companies,id',
            'journal_id' => 'required|exists:account_journals,id',
            'date'       => 'required|date',
            'ref'        => 'nullable|string|max:255',
            'lines'      => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.label'      => 'nullable|string|max:255',
            'lines.*.debit'      => 'numeric|min:0',
            'lines.*.credit'     => 'numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {
            $this->assertBalanced($data['lines']);
            $move = Move::create(collect($data)->except('lines')->toArray());
            $move->lines()->createMany($data['lines']);
            return $move->load('lines.account','journal');
        });
    }

    public function show(Move $move) { return $move->load('lines.account','journal'); }

    public function update(Request $r, Move $move)
    {
        abort_if($move->status === 'posted', 422, 'Posted move is locked.');
        $data = $r->validate([
            'date'       => 'sometimes|date',
            'ref'        => 'sometimes|nullable|string|max:255',
            'lines'      => 'sometimes|array|min:2',
            'lines.*.account_id' => 'required_with:lines|exists:accounts,id',
            'lines.*.label'      => 'nullable|string|max:255',
            'lines.*.debit'      => 'numeric|min:0',
            'lines.*.credit'     => 'numeric|min:0',
        ]);

        return DB::transaction(function () use ($move, $data) {
            if (isset($data['lines'])) {
                $this->assertBalanced($data['lines']);
                $move->lines()->delete();
                $move->lines()->createMany($data['lines']);
            }
            $move->update(collect($data)->except('lines')->toArray());
            return $move->load('lines.account','journal');
        });
    }

    public function destroy(Move $move)
    {
        abort_if($move->status === 'posted', 422, 'Posted move cannot be deleted.');
        $move->delete();
        return response()->noContent();
    }

    private function assertBalanced(array $lines): void
    {
        $d=0; $c=0;
        foreach ($lines as $l) {
            $dd = (float)($l['debit'] ?? 0);
            $cc = (float)($l['credit'] ?? 0);
            abort_if(!(($dd > 0 && $cc == 0) || ($cc > 0 && $dd == 0)), 422, 'Each line must have either debit or credit, not both.');
            $d += $dd; $c += $cc;
        }
        abort_if(round($d,2) !== round($c,2), 422, 'Debits and credits must be equal.');
    }
}
