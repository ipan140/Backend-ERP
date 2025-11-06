<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $r)
    {
        $q = Account::query()
            ->when($r->filled('company_id'), fn($qq)=>$qq->where('company_id',$r->company_id))
            ->when($r->filled('search'), fn($qq)=>$qq->where(fn($w)=>
                $w->where('code','like','%'.$r->search.'%')
                  ->orWhere('name','like','%'.$r->search.'%')))
            ->when($r->filled('type'), fn($qq)=>$qq->where('type',$r->type))
            ->orderBy('code');

        return $q->paginate((int)$r->get('per_page',15));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'company_id' => 'required|exists:companies,id',
            'code'       => 'required|string|max:20',
            'name'       => 'required|string|max:120',
            'type'       => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id'  => 'nullable|exists:accounts,id',
            'active'     => 'boolean',
        ]);
        $data['level'] = $data['parent_id'] ? (Account::find($data['parent_id'])->level + 1) : 1;

        return Account::create($data);
    }

    public function show(Account $account) { return $account; }

    public function update(Request $r, Account $account)
    {
        $data = $r->validate([
            'code'       => 'sometimes|string|max:20',
            'name'       => 'sometimes|string|max:120',
            'type'       => 'sometimes|in:asset,liability,equity,revenue,expense',
            'parent_id'  => 'nullable|exists:accounts,id',
            'active'     => 'sometimes|boolean',
        ]);
        if (array_key_exists('parent_id',$data)) {
            $data['level'] = $data['parent_id'] ? (Account::find($data['parent_id'])->level + 1) : 1;
        }
        $account->update($data);
        return $account;
    }

    public function destroy(Account $account)
    {
        abort_if($account->children()->exists(), 422, 'Account has children.');
        abort_if($account->moveLines()->exists(), 422, 'Account used in journal lines.');
        $account->delete();
        return response()->noContent();
    }
}
