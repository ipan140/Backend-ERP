<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerController extends Controller
{
    /**
     * GET /api/customers?search=&active=&sort=name|code|created_at&dir=asc|desc&per_page=10
     */
    public function index(Request $request)
    {
        try {
            $q = DB::table('customers');

            // Search by name/code/email/phone
            if ($request->filled('search')) {
                $s = '%' . $request->query('search') . '%';
                $q->where(function ($w) use ($s) {
                    $w->where('name', 'like', $s)
                      ->orWhere('code', 'like', $s)
                      ->orWhere('email', 'like', $s)
                      ->orWhere('phone', 'like', $s);
                });
            }

            // Filter active = true/false (terima "true"/"false"/1/0)
            if ($request->has('active')) {
                $active = filter_var($request->query('active'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                if (!is_null($active)) {
                    $q->where('is_active', $active);
                }
            }

            // Sorting
            $allowedSort = ['id','name','code','created_at'];
            $sort = in_array($request->query('sort'), $allowedSort, true) ? $request->query('sort') : 'id';
            $dir  = $request->query('dir') === 'asc' ? 'asc' : 'desc';

            // Per page
            $perPage = (int) ($request->query('per_page', 10));
            if ($perPage < 1 || $perPage > 100) $perPage = 10;

            return response()->json(
                $q->orderBy($sort, $dir)->paginate($perPage)
            );
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * POST /api/customers
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'code'            => 'required|string|max:191|unique:customers,code',
                'name'            => 'required|string|max:191',
                'email'           => 'nullable|email|max:191',
                'phone'           => 'nullable|string|max:191',
                'address'         => 'nullable|string',
                'payment_term_id' => 'nullable|exists:payment_terms,id',
                'credit_limit'    => 'nullable|numeric|min:0',
                'is_active'       => 'nullable|boolean',
            ]);

            $now = now();
            $id = DB::table('customers')->insertGetId($data + [
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return response()->json([
                'message' => 'Customer created successfully',
                'id'      => $id,
            ], 201);
        } catch (ValidationException $ve) {
            return response()->json(['errors' => $ve->errors()], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * GET /api/customers/{id}
     */
    public function show($id)
    {
        try {
            $row = DB::table('customers')->where('id', (int) $id)->first();
            if (!$row) return response()->json(['message' => 'Customer not found'], 404);
            return response()->json($row);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * PUT /api/customers/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $exists = DB::table('customers')->where('id', (int) $id)->exists();
            if (!$exists) return response()->json(['message' => 'Customer not found'], 404);

            $data = $request->validate([
                'code'            => 'sometimes|required|string|max:191|unique:customers,code,' . (int)$id,
                'name'            => 'sometimes|required|string|max:191',
                'email'           => 'nullable|email|max:191',
                'phone'           => 'nullable|string|max:191',
                'address'         => 'nullable|string',
                'payment_term_id' => 'nullable|exists:payment_terms,id',
                'credit_limit'    => 'nullable|numeric|min:0',
                'is_active'       => 'nullable|boolean',
            ]);

            DB::table('customers')->where('id', (int)$id)->update($data + ['updated_at' => now()]);

            return response()->json(['message' => 'Customer updated successfully']);
        } catch (ValidationException $ve) {
            return response()->json(['errors' => $ve->errors()], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * DELETE /api/customers/{id}
     */
    public function destroy($id)
    {
        try {
            $row = DB::table('customers')->where('id', (int)$id)->first();
            if (!$row) return response()->json(['message' => 'Customer not found'], 404);

            DB::table('customers')->where('id', (int)$id)->delete();
            return response()->json(['message' => 'Customer deleted successfully']);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** Helper uniform error JSON */
    private function error(Throwable $e)
    {
        // Log kalau perlu: report($e);
        return response()->json([
            'message' => 'Server error',
            'error'   => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}
