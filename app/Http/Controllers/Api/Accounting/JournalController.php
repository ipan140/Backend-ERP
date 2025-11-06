<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountJournal;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function index(Request $r)
    {
        return AccountJournal::query()
            ->when($r->filled('company_id'), fn($q)=>$q->where('company_id',$r->company_id))
            ->when($r->filled('type'), fn($q)=>$q->where('type',$r->type))
            ->when($r->filled('active'), fn($q)=>$q->where('active',(bool)$r->active))
            ->orderBy('code')
            ->paginate((int)$r->get('per_page',15));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'company_id'      => 'required|exists:companies,id',
            'code'            => 'required|string|max:10',
            'name'            => 'required|string|max:100',
            'type'            => 'required|in:bank,cash,sale,purchase,general',
            'sequence_prefix' => 'nullable|string|max:50',
            'sequence_padding'=> 'nullable|integer|min:3|max:10',
            'active'          => 'boolean',
        ]);
        return AccountJournal::create($data);
    }

    public function show(AccountJournal $journal) { return $journal; }

    public function update(Request $r, AccountJournal $journal)
    {
        $data = $r->validate([
            'name'            => 'sometimes|string|max:100',
            'type'            => 'sometimes|in:bank,cash,sale,purchase,general',
            'sequence_prefix' => 'sometimes|string|max:50',
            'sequence_padding'=> 'sometimes|integer|min:3|max:10',
            'active'          => 'sometimes|boolean',
        ]);
        $journal->update($data);
        return $journal;
    }

    public function destroy(AccountJournal $journal)
    {
        abort_if($journal->moves()->exists(), 422, 'Journal already used.');
        $journal->delete();
        return response()->noContent();
    }
}
