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
     * GET /api/pricelists?search=&active=&type=&currency=&per_page=10&sort=name&dir=asc
     */
    public function index(Request $r)
    {
        try {
            $q = DB::table('pricelists');

            // Search by name/description
            $search = trim((string) $r->query('search', ''));
            if ($search !== '') {
                $q->where(function ($w) use ($search) {
                    $w->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter active: apply only if '0' or '1'
            if ($r->has('active')) {
                $val = (string) $r->query('active');
                if ($val === '0' || $val === '1') {
                    $q->where('active', (int) $val);
                }
            }

            // Filter type: only 'sale' or 'purchase'
            $type = (string) $r->query('type', '');
            if (in_array($type, ['sale', 'purchase'], true)) {
                $q->where('type', $type);
            }

            // Filter currency: ignore if empty/All-like
            $cur = strtoupper(trim((string) $r->query('currency', '')));
            if ($cur !== '' && $cur !== 'ALL') {
                $q->where('currency', $cur);
            }

            // Safe sorting
            $allowedSort = ['id', 'name', 'created_at', 'valid_from', 'valid_until'];
            $sort = in_array($r->query('sort'), $allowedSort, true) ? $r->query('sort') : 'id';
            $dir  = $r->query('dir') === 'asc' ? 'asc' : 'desc';

            // Pagination guard
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
                'currency'    => strtoupper($data['currency'] ?? 'IDR'),
                'type'        => $data['type'] ?? 'sale',
                'description' => $data['description'] ?? null,
                'valid_from'  => $data['valid_from'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'active'      => array_key_exists('active', $data) ? (bool) $data['active'] : true,
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
            $row = DB::table('pricelists')->where('id', (int) $id)->first();
            return $row ? response()->json($row)
                        : response()->json(['message' => 'Not found'], 404);
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
            $id = (int) $id;
            if (!DB::table('pricelists')->where('id', $id)->exists()) {
                return response()->json(['message' => 'Not found'], 404);
            }

            $data = $r->validate([
                'name'        => "sometimes|required|string|max:100|unique:pricelists,name,{$id}",
                'currency'    => 'nullable|string|max:8',
                'type'        => 'nullable|in:sale,purchase',
                'description' => 'nullable|string',
                'valid_from'  => 'nullable|date',
                'valid_until' => 'nullable|date|after_or_equal:valid_from',
                'active'      => 'nullable|boolean',
            ]);

            // Normalisasi kecil
            if (array_key_exists('currency', $data) && $data['currency'] !== null) {
                $data['currency'] = strtoupper($data['currency']);
            }
            if (array_key_exists('active', $data)) {
                $data['active'] = (bool) $data['active'];
            }

            DB::table('pricelists')->where('id', $id)->update($data + ['updated_at' => now()]);

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
            $deleted = DB::table('pricelists')->where('id', (int) $id)->delete();
            return $deleted ? response()->json(['message' => 'Pricelist deleted'])
                            : response()->json(['message' => 'Not found'], 404);
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
