<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogisticsController extends Controller
{
    /**
     * GET /api/scm/logistics
     * Support: ?status=draft|confirmed|done|cancelled&search=...
     */
    public function index(Request $r)
    {
        // ====== DUMMY ROWS ======
        $rows = [
            [
                'id'        => 1,
                'number'    => 'DLV-0001',
                'ship_date' => now()->toDateString(),
                'carrier'   => 'Internal Truck',
                'route'     => 'WH → SBY',
                'so_id'     => 101,
                'status'    => 'draft',
            ],
            [
                'id'        => 2,
                'number'    => 'DLV-0002',
                'ship_date' => now()->copy()->addDay()->toDateString(),
                'carrier'   => 'JNE',
                'route'     => 'SBY → SDA',
                'so_id'     => 102,
                'status'    => 'confirmed',
            ],
            [
                'id'        => 3,
                'number'    => 'DLV-0003',
                'ship_date' => now()->copy()->subDay()->toDateString(),
                'carrier'   => 'SiCepat',
                'route'     => 'SBY → GRESIK',
                'so_id'     => 103,
                'status'    => 'done',
            ],
        ];

        $status = $r->query('status');
        $search = trim((string) $r->query('search'));

        $filtered = collect($rows)
            ->when($status !== null && $status !== '', fn ($c) => $c->where('status', strtolower($status)))
            ->when($search !== '', function ($c) use ($search) {
                $q = mb_strtolower($search);
                return $c->filter(function ($row) use ($q) {
                    return str_contains(mb_strtolower($row['number']), $q)
                        || str_contains(mb_strtolower($row['carrier']), $q)
                        || str_contains(mb_strtolower($row['route']), $q)
                        || str_contains((string) $row['so_id'], $q);
                });
            })
            ->values()
            ->all();

        return response()->json([
            'ok'         => true,
            'deliveries' => $filtered,
            'total'      => count($filtered),
        ]);
    }

    /**
     * POST /api/scm/logistics
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'so_id'               => 'nullable|integer',
            'ship_date'           => 'required|date',
            'carrier'             => 'nullable|string',
            'route'               => 'nullable|string',
            'items'               => 'required|array|min:1',
            'items.*.lot_id'      => 'nullable|integer',
            'items.*.product_id'  => 'required_without:items.*.lot_id|integer',
            'items.*.qty'         => 'required|numeric|min:0.0001',
            'items.*.uom'         => 'required|string',
        ]);

        // Dummy create
        return response()->json([
            'ok'       => true,
            'message'  => 'Delivery created (dummy)',
            'delivery' => array_merge([
                'id' => 999,
                'number' => 'DLV-0999',
                'status' => 'draft',
            ], $data),
        ], 201);
    }

    /**
     * GET /api/scm/logistics/{id}
     */
    public function show($id)
    {
        return response()->json([
            'ok'       => true,
            'delivery' => [
                'id'        => (int) $id,
                'number'    => 'DLV-' . str_pad($id, 4, '0', STR_PAD_LEFT),
                'status'    => 'draft',
                'items'     => [],
            ],
        ]);
    }

    /**
     * PATCH/PUT /api/scm/logistics/{id}  (dummy)
     */
    public function update(Request $r, $id)
    {
        $r->validate([
            'status' => 'nullable|in:draft,confirmed,done,cancelled',
        ]);

        return response()->json([
            'ok'      => true,
            'message' => "Delivery {$id} updated (dummy)",
        ]);
    }

    /**
     * DELETE /api/scm/logistics/{id}  (dummy)
     */
    public function destroy($id)
    {
        return response()->json([
            'ok'      => true,
            'message' => "Delivery {$id} deleted (dummy)",
        ]);
    }

    /**
     * POST /api/scm/logistics/{id}/confirm
     */
    public function confirm($id)
    {
        return response()->json([
            'ok'      => true,
            'message' => "Delivery {$id} confirmed (dummy)",
        ]);
    }

    /**
     * POST /api/scm/logistics/{id}/pod
     */
    public function proofOfDelivery(Request $r, $id)
    {
        $data = $r->validate([
            'signed_by' => 'nullable|string',
            'photo_url' => 'nullable|url',
            'note'      => 'nullable|string',
        ]);

        return response()->json([
            'ok'      => true,
            'message' => "POD for Delivery {$id} stored (dummy)",
            'pod'     => $data,
        ]);
    }
}
