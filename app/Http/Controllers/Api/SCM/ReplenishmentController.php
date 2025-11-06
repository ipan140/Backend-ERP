<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReplenishmentController extends Controller
{
    /**
     * GET /api/scm/replenishments
     * Support: search, page, per_page (dummy list for now)
     */
    public function index(Request $r)
    {
        $search   = trim((string) $r->get('search', ''));
        $page     = max(1, (int) $r->get('page', 1));
        $perPage  = max(1, min(100, (int) $r->get('per_page', 15)));

        // Dummy data (nanti ganti ke query DB)
        $all = [
            [
                'id' => 1, 'product_id' => 1, 'location_id' => 1,
                'min_qty' => 10, 'max_qty' => 100, 'uom' => 'kg',
                'policy' => 'MTS', 'vendor_id' => null, 'lead_time_days' => 3,
                'is_active' => true,
            ],
            [
                'id' => 2, 'product_id' => 2, 'location_id' => 1,
                'min_qty' => 5, 'max_qty' => 50, 'uom' => 'kg',
                'policy' => 'MTO', 'vendor_id' => 10, 'lead_time_days' => 7,
                'is_active' => true,
            ],
        ];

        // Filter sederhana by search (product/location text contains)
        if ($search !== '') {
            $all = array_values(array_filter($all, function ($row) use ($search) {
                return str_contains((string) $row['product_id'], $search)
                    || str_contains((string) $row['location_id'], $search)
                    || str_contains((string) $row['uom'], $search)
                    || str_contains((string) ($row['policy'] ?? ''), $search);
            }));
        }

        $total  = count($all);
        $offset = ($page - 1) * $perPage;
        $slice  = array_slice($all, $offset, $perPage);

        return response()->json([
            'ok'   => true,
            'data' => $slice,
            'meta' => [
                'page'      => $page,
                'per_page'  => $perPage,
                'total'     => $total,
                'total_page'=> (int) ceil($total / $perPage),
            ],
            'note' => 'List reorder rules (dummy). Ganti ke paginate(DB) bila tabel siap.',
        ]);
    }

    /**
     * POST /api/scm/replenishments
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'product_id'      => 'required|integer',
            'location_id'     => 'required|integer',
            'min_qty'         => 'required|numeric|min:0',
            'max_qty'         => 'required|numeric|gt:min_qty',
            'uom'             => 'required|string',
            'policy'          => 'nullable|string|in:MTS,MTO',
            'vendor_id'       => 'nullable|integer',
            'lead_time_days'  => 'nullable|integer|min:0',
            'is_active'       => 'nullable|boolean',
        ]);

        $rule = array_merge([
            'id'            => 1,                // dummy id
            'is_active'     => $data['is_active'] ?? true,
        ], $data);

        return response()->json([
            'ok'      => true,
            'message' => 'Reorder rule saved (dummy)',
            'rule'    => $rule,
        ], 201);
    }

    /**
     * GET /api/scm/replenishments/{id}
     */
    public function show(int $id)
    {
        // Dummy detail
        return response()->json([
            'ok'   => true,
            'rule' => [
                'id'             => $id,
                'product_id'     => 1,
                'location_id'    => 1,
                'min_qty'        => 10,
                'max_qty'        => 100,
                'uom'            => 'kg',
                'policy'         => 'MTS',
                'vendor_id'      => null,
                'lead_time_days' => 3,
                'is_active'      => true,
            ],
        ]);
    }

    /**
     * PUT/PATCH /api/scm/replenishments/{id}
     */
    public function update(Request $r, int $id)
    {
        $data = $r->validate([
            'product_id'      => 'sometimes|required|integer',
            'location_id'     => 'sometimes|required|integer',
            'min_qty'         => 'sometimes|required|numeric|min:0',
            'max_qty'         => 'sometimes|required|numeric|gt:min_qty',
            'uom'             => 'sometimes|required|string',
            'policy'          => 'nullable|string|in:MTS,MTO',
            'vendor_id'       => 'nullable|integer',
            'lead_time_days'  => 'nullable|integer|min:0',
            'is_active'       => 'nullable|boolean',
        ]);

        return response()->json([
            'ok'      => true,
            'message' => "Rule {$id} updated (dummy)",
            'rule'    => array_merge(['id' => $id], $data),
        ]);
    }

    /**
     * DELETE /api/scm/replenishments/{id}
     */
    public function destroy(int $id)
    {
        return response()->json([
            'ok'      => true,
            'message' => "Rule {$id} deleted (dummy)",
        ]);
    }

    /**
     * POST /api/scm/replenishments/check
     * Optional: days_ahead (int >= 0)
     */
    public function check(Request $r)
    {
        $r->validate([
            'days_ahead' => 'nullable|integer|min:0',
        ]);

        // Contoh hasil rekomendasi
        $result = [
            // ['product_id' => 1, 'location_id' => 1, 'suggested_qty' => 200, 'uom' => 'kg', 'reason' => 'below min'],
        ];

        return response()->json([
            'ok'     => true,
            'result' => $result,
        ]);
    }

    /**
     * POST /api/scm/replenishments/auto-generate
     * Optional: mode (PO|MO|BOTH)
     */
    public function autoGenerate(Request $r)
    {
        $r->validate([
            'mode' => 'nullable|in:PO,MO,BOTH',
        ]);

        // Dummy outcome
        return response()->json([
            'ok'        => true,
            'message'   => 'Auto-generated PO/MO based on rules (dummy)',
            'generated' => ['po_count' => 0, 'mo_count' => 0],
        ]);
    }
}
