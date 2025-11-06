<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    /* =========================================================
     |  RESTful minimal (untuk apiResource('inventory')->except('show'))
     |  Anggap entity di sini adalah "inventory documents" (dummy).
     |  Nanti bisa diganti ke model sebenarnya (StockDoc, Location, dsb).
     * =======================================================*/

    // GET /api/scm/inventory
    public function index(Request $r): JsonResponse
    {
        return response()->json([
            'ok'   => true,
            'data' => [
                // contoh dummy document
                ['id' => 1, 'type' => 'receipt', 'number' => 'RCV/001', 'status' => 'posted'],
            ],
            'meta' => ['total' => 1],
        ]);
    }

    // POST /api/scm/inventory
    public function store(Request $r): JsonResponse
    {
        $data = $r->validate([
            'type'   => 'required|string|in:receipt,transfer,adjust',
            'number' => 'nullable|string|max:50',
            'note'   => 'nullable|string',
        ]);

        return response()->json([
            'ok'      => true,
            'message' => 'Inventory document created (dummy)',
            'data'    => array_merge(['id' => 2, 'status' => 'draft'], $data),
        ], 201);
    }

    // PUT/PATCH /api/scm/inventory/{id}
    public function update(Request $r, int $id): JsonResponse
    {
        $data = $r->validate([
            'note'   => 'nullable|string',
            'status' => 'nullable|string|in:draft,posted,cancelled',
        ]);

        return response()->json([
            'ok'      => true,
            'message' => "Inventory document #{$id} updated (dummy)",
            'data'    => array_merge(['id' => $id], $data),
        ]);
    }

    // DELETE /api/scm/inventory/{id}
    public function destroy(int $id): JsonResponse
    {
        return response()->json([
            'ok'      => true,
            'message' => "Inventory document #{$id} deleted (dummy)",
        ]);
    }

    /* =========================================================
     |  Endpoints KUSTOM untuk modul Inventory
     |  (dipanggil di FE: /api/scm/inventory/...)
     * =======================================================*/

    // GET /api/scm/inventory/stocks
    public function stocks(Request $r): JsonResponse
    {
        return response()->json([
            'ok'      => true,
            'filters' => $r->all(),
            'data'    => [
                // contoh dummy
                ['product_id' => 1, 'product_name' => 'Pupuk NPK', 'location_id' => 10, 'qty' => 120.5, 'uom' => 'kg'],
            ],
        ]);
    }

    // POST /api/scm/inventory/receipt
    public function receipt(Request $r): JsonResponse
    {
        $data = $r->validate([
            'location_id'          => 'required|integer',
            'lines'                => 'required|array|min:1',
            'lines.*.product_id'   => 'required|integer',
            'lines.*.qty'          => 'required|numeric|min:0.0001',
            'lines.*.uom'          => 'required|string',
            'lines.*.lot'          => 'nullable|string',
            'lines.*.expiry_date'  => 'nullable|date',
        ]);

        return response()->json([
            'ok'      => true,
            'message' => 'Stock receipt posted (dummy)',
            'receipt' => $data,
        ], 201);
    }

    // POST /api/scm/inventory/transfer
    public function transfer(Request $r): JsonResponse
    {
        $data = $r->validate([
            'from_location_id'       => 'required|integer|different:to_location_id',
            'to_location_id'         => 'required|integer',
            'lines'                  => 'required|array|min:1',
            'lines.*.lot_id'         => 'nullable|integer',
            'lines.*.product_id'     => 'required_without:lines.*.lot_id|integer',
            'lines.*.qty'            => 'required|numeric|min:0.0001',
            'lines.*.uom'            => 'required|string',
        ]);

        return response()->json([
            'ok'       => true,
            'message'  => 'Internal transfer executed (dummy)',
            'transfer' => $data,
        ]);
    }

    // POST /api/scm/inventory/adjust
    public function adjust(Request $r): JsonResponse
    {
        $data = $r->validate([
            'location_id'            => 'required|integer',
            'reason'                 => 'required|string|in:count,waste,shrink,other',
            'lines'                  => 'required|array|min:1',
            'lines.*.lot_id'         => 'nullable|integer',
            'lines.*.product_id'     => 'required_without:lines.*.lot_id|integer',
            'lines.*.qty'            => 'required|numeric', // +/- boleh minus
            'lines.*.uom'            => 'required|string',
            'note'                   => 'nullable|string',
        ]);

        return response()->json([
            'ok'         => true,
            'message'    => 'Stock adjustment recorded (dummy)',
            'adjustment' => $data,
        ]);
    }

    // GET /api/scm/inventory/lots
    public function lots(Request $r): JsonResponse
    {
        return response()->json([
            'ok'      => true,
            'filters' => $r->all(),
            'data'    => [
                [
                    'id'          => 1001,
                    'product_id'  => 1,
                    'product_name'=> 'Pupuk NPK',
                    'grade'       => 'A',
                    'qty'         => 80,
                    'uom'         => 'kg',
                    'harvest_date'=> '2025-10-01',
                    'expiry_date' => '2025-11-15',
                    'status'      => 'available',
                ],
            ],
        ]);
    }

    // POST /api/scm/inventory/lots
    public function storeLot(Request $r): JsonResponse
    {
        $data = $r->validate([
            'product_id'   => 'required|integer',
            'grade'        => 'nullable|string|max:10',
            'qty'          => 'required|numeric|min:0.0001',
            'uom'          => 'required|string',
            'harvest_date' => 'nullable|date',
            'expiry_date'  => 'nullable|date|after:harvest_date',
            'origin_field' => 'nullable|string',
        ]);

        return response()->json([
            'ok'      => true,
            'message' => 'Lot created (dummy)',
            'lot'     => array_merge(['id' => 1002, 'status' => 'available'], $data),
        ], 201);
    }

    // GET /api/scm/inventory/expiry-alerts?days=14
    public function expiryAlerts(Request $r): JsonResponse
    {
        return response()->json([
            'ok'             => true,
            'threshold_days' => (int) $r->get('days', 14),
            'data'           => [], // isi nanti dengan lot yang mendekati kadaluarsa
        ]);
    }
}
