<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vendor;

class VendorController extends Controller
{
    public function index(Request $r)
    {
        $q = Vendor::query();
        if ($r->filled('search')) {
            $q->where(function($qq) use ($r) {
                $qq->where('name','like','%'.$r->search.'%')
                   ->orWhere('code','like','%'.$r->search.'%');
            });
        }
        return response()->json($q->orderBy('name')->paginate(20));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'code' => 'required|string|max:30|unique:vendors,code',
            'name' => 'required|string|max:120',
            'email'=> 'nullable|email',
            'phone'=> 'nullable|string|max:50',
            'addr' => 'nullable|string',
        ]);
        $v = Vendor::create($data);
        return response()->json(['ok'=>true,'vendor'=>$v], 201);
    }

    public function show($id)
    {
        $v = Vendor::findOrFail($id);
        return response()->json(['ok'=>true,'vendor'=>$v]);
    }

    public function update(Request $r, $id)
    {
        $v = Vendor::findOrFail($id);
        $data = $r->validate([
            'name' => 'sometimes|required|string|max:120',
            'email'=> 'nullable|email',
            'phone'=> 'nullable|string|max:50',
            'addr' => 'nullable|string',
        ]);
        $v->update($data);
        return response()->json(['ok'=>true,'vendor'=>$v]);
    }

    public function destroy($id)
    {
        $v = Vendor::findOrFail($id);
        $v->delete();
        return response()->json(['ok'=>true]);
    }

    // Contoh rating sederhana
    public function rating(Request $r)
    {
        $data = $r->validate([
            'vendor_id' => 'required|integer|exists:vendors,id',
            'score'     => 'required|integer|min:1|max:5',
            'note'      => 'nullable|string',
        ]);
        // Simpan ke tabel lain bila ada; untuk sekarang attach di metadata vendor
        $v = Vendor::findOrFail($data['vendor_id']);
        $v->rating = $data['score'];
        $v->save();

        return response()->json(['ok'=>true,'message'=>'Rating saved','vendor'=>$v]);
    }
}
