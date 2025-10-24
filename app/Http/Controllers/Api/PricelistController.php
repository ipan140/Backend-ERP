<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PricelistController extends Controller
{
    /**
     * GET /api/pricelists?search=&active=&type=&per_page=10&sort=name&dir=asc
     */
    public function index(Request $r)
    {
        try {
            $q = DB::table('pricelists');

            if ($r->filled('search')) {
                $s = '%'.$r->query('search').'%';
                $q->where('name', 'like', $s);
            }

            if ($r->has('active')) {
                $active = filter_var($r->query('active'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                if (!is_null($active)) $q->where('active', $active);
            }

            if ($r->filled('type')) {
                $q->where('type', $r->query('type')); // 'sale' / 'purchase'
            }

            $allowedSort = ['id','name','created_at','valid_until'];
            $sort = in_array($r->query('sort'), $allowedSort, true) ? $r->query('sort') : 'id';
            $dir  = $r->query('dir') === 'asc' ? 'asc' : 'desc';

            $perPage = (int) $r->query('per_page', 10);
            if ($perPage < 1 || $perPage > 100) $perPage = 10;

            return response()->json(
                $q->orderBy($sort, $dir)->paginate($perPage)
            );
        } catch (Throwable $e) {
            return $this->err($e);
        }
    }

    /**
     * POST /api/pricelists
     */
    public function store(Request $r)
    {
        try {
            $data = $r->validate([
                'name'        => 'required|string|max:100|unique:pricelists,name',
                'currency'    => 'nullable|string|max:8',
                'type'        => 'nullable|in:sale,purchase',
                'description' => 'nullable|string',
                'valid_from'  => 'nullable|date',
                'valid_until' => 'nullable|date|after_or_equal:valid_from',
                'active'      => 'nullable|boolean',
            ]);

            $now = now();
            $id = DB::table('pricelists')->insertGetId([
                'name'        => $data['name'],
                'currency'    => $data['currency'] ?? 'IDR',
                'type'        => $data['type'] ?? 'sale',
                'description' => $data['description'] ?? null,
                'valid_from'  => $data['valid_from'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'active'      => $data['active'] ?? true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            return response()->json(['id' => $id, 'message' => 'Pricelist created'], 201);
        } catch (ValidationException $ve) {
            return response()->json(['errors' => $ve->errors()], 422);
        } catch (Throwable $e) {
            return $this->err($e);
        }
    }

    /**
     * GET /api/pricelists/{id}
     */
    public function show($id)
    {
        try {
            $row = DB::table('pricelists')->where('id', (int)$id)->first();
            return $row ? response()->json($row) : response()->json(['message'=>'Not found'], 404);
        } catch (Throwable $e) {
            return $this->err($e);
        }
    }

    /**
     * PUT /api/pricelists/{id}
     */
    public function update(Request $r, $id)
    {
        try {
            $exists = DB::table('pricelists')->where('id', (int)$id)->exists();
            if (!$exists) return response()->json(['message'=>'Not found'], 404);

            $data = $r->validate([
                'name'        => 'sometimes|required|string|max:100|unique:pricelists,name,'.(int)$id,
                'currency'    => 'nullable|string|max:8',
                'type'        => 'nullable|in:sale,purchase',
                'description' => 'nullable|string',
                'valid_from'  => 'nullable|date',
                'valid_until' => 'nullable|date|after_or_equal:valid_from',
                'active'      => 'nullable|boolean',
            ]);

            DB::table('pricelists')->where('id', (int)$id)->update($data + ['updated_at' => now()]);
            return response()->json(['message' => 'Pricelist updated']);
        } catch (ValidationException $ve) {
            return response()->json(['errors' => $ve->errors()], 422);
        } catch (Throwable $e) {
            return $this->err($e);
        }
    }

    /**
     * DELETE /api/pricelists/{id}
     */
    public function destroy($id)
    {
        try {
            $deleted = DB::table('pricelists')->where('id', (int)$id)->delete();
            return $deleted ? response()->json(['message'=>'Pricelist deleted'])
                            : response()->json(['message'=>'Not found'], 404);
        } catch (Throwable $e) {
            return $this->err($e);
        }
    }

    private function err(Throwable $e)
    {
        return response()->json([
            'message' => 'Server error',
            'error'   => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}
