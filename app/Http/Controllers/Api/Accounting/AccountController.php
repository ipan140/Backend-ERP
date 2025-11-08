<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index(Request $r)
    {
        $q = Account::query()
            ->with('parent:id,code,name')
            ->when($r->filled('company_id'), fn($qq) => $qq->where('company_id', $r->company_id))
            ->when($r->filled('search'), function ($qq) use ($r) {
                $s = trim((string)$r->search);
                $qq->where(function ($w) use ($s) {
                    $w->where('code','like',"%{$s}%")
                      ->orWhere('name','like',"%{$s}%");
                });
            })
            ->when($r->filled('type'), fn($qq) => $qq->where('type', $r->type))
            ->when($r->filled('active'), fn($qq) => $qq->where('active', (bool)$r->active))
            ->orderBy('code');

        $perPage = (int) $r->get('per_page', 15);
        return $q->paginate($perPage);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'company_id' => ['required','exists:companies,id'],
            'code'       => [
                'required','string','max:20',
                Rule::unique('accounts')->where(fn($q)=>$q->where('company_id',$r->company_id)),
            ],
            'name'       => ['required','string','max:150'],
            'type'       => ['required', Rule::in(['asset','liability','equity','revenue','expense'])],
            'parent_id'  => ['nullable','exists:accounts,id'],
            'active'     => ['boolean'],
            'reconcile'  => ['boolean'],
        ]);

        $acc = Account::create($data);
        return $acc->load('parent:id,code,name');
    }

    public function show(Account $account)
    {
        return $account->load('parent:id,code,name');
    }

    public function update(Request $r, Account $account)
    {
        $data = $r->validate([
            'company_id' => ['sometimes','required','exists:companies,id'],
            'code'       => [
                'sometimes','required','string','max:20',
                Rule::unique('accounts')
                    ->ignore($account->id)
                    ->where(fn($q)=>$q->where('company_id', $r->get('company_id', $account->company_id))),
            ],
            'name'       => ['sometimes','required','string','max:150'],
            'type'       => ['sometimes', Rule::in(['asset','liability','equity','revenue','expense'])],
            'parent_id'  => ['nullable','different:id','exists:accounts,id'],
            'active'     => ['sometimes','boolean'],
            'reconcile'  => ['sometimes','boolean'],
        ]);

        $account->update($data);
        return $account->load('parent:id,code,name');
    }

    public function destroy(Account $account)
    {
        // optional guard: tolak hapus jika sudah dipakai di move lines
        if ($account->lines()->exists()) {
            return response()->json(['message' => 'Account already used.'], 422);
        }
        $account->delete();
        return response()->noContent();
    }
}
