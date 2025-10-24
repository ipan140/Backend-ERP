<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * GET /api/products
     * List produk (search & filter active) + pagination
     */
    public function index(Request $request)
    {
        $q = DB::table('products');

        if ($request->filled('search')) {
            $s = '%'.$request->search.'%';
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', $s)
                  ->orWhere('sku', 'like', $s);
            });
        }

        if ($request->filled('active')) {
            $q->where('active', (bool)$request->active);
        }

        return response()->json($q->orderByDesc('id')->paginate(10));
    }

    /**
     * POST /api/products
     * Buat produk baru
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'sku'        => 'required|string|unique:products,sku',
            'name'       => 'required|string',
            'uom'        => 'nullable|string|max:32',
            'base_price' => 'nullable|numeric|min:0',
            'active'     => 'nullable|boolean',
        ]);

        $now = now();
        $id = DB::table('products')->insertGetId($data + [
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json(['id' => $id, 'message' => 'Product created'], 201);
    }

    /**
     * GET /api/products/{id}
     * Detail produk
     */
    public function show($id)
    {
        $p = DB::table('products')->where('id', $id)->first();
        return $p ? response()->json($p)
                  : response()->json(['message' => 'Product not found'], 404);
    }

    /**
     * PUT /api/products/{id}
     * Update produk
     */
    public function update(Request $request, $id)
    {
        $exists = DB::table('products')->where('id', $id)->exists();
        if (!$exists) return response()->json(['message' => 'Product not found'], 404);

        $data = $request->validate([
            'sku'        => 'sometimes|string|unique:products,sku,' . $id,
            'name'       => 'sometimes|required|string',
            'uom'        => 'nullable|string|max:32',
            'base_price' => 'nullable|numeric|min:0',
            'active'     => 'nullable|boolean',
        ]);

        DB::table('products')->where('id', $id)->update($data + ['updated_at' => now()]);

        return response()->json(['message' => 'Product updated']);
    }

    /**
     * DELETE /api/products/{id}
     * Hapus produk
     */
    public function destroy($id)
    {
        $deleted = DB::table('products')->where('id', $id)->delete();
        return $deleted ? response()->json(['message' => 'Product deleted'])
                        : response()->json(['message' => 'Product not found'], 404);
    }
}
