<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index(Request $r)
    {
        return response()->json([
            'ok' => true,
            'filters' => $r->all(),
            'data' => [],
            'note' => 'GET list Purchase Orders (use pagination later)',
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'vendor_id'      => 'required|integer',
            'expected_date'  => 'nullable|date',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.qty'        => 'required|numeric|min:0.0001',
            'items.*.uom'        => 'required|string',
            'items.*.price'      => 'required|numeric|min:0',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'PO created (dummy)',
            'po' => array_merge(['id' => 1, 'status' => 'draft'], $data),
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            'ok' => true,
            'po' => ['id' => (int)$id, 'status' => 'draft', 'items' => []],
        ]);
    }

    public function update(Request $r, $id)
    {
        return response()->json([
            'ok' => true,
            'message' => "PO {$id} updated (dummy)",
            'changes' => $r->all(),
        ]);
    }

    public function destroy($id)
    {
        return response()->json([
            'ok' => true,
            'message' => "PO {$id} deleted (dummy)",
        ]);
    }

    public function confirm($id)
    {
        return response()->json([
            'ok' => true,
            'message' => "PO {$id} confirmed (status: confirmed)",
        ]);
    }

    public function receive(Request $r, $id)
    {
        $payload = $r->validate([
            'location_id' => 'required|integer',
            'lines'       => 'required|array|min:1',
            'lines.*.po_item_id' => 'required|integer',
            'lines.*.qty'        => 'required|numeric|min:0.0001',
            'lines.*.lot'        => 'nullable|string',
            'lines.*.expiry_date'=> 'nullable|date',
        ]);

        return response()->json([
            'ok' => true,
            'message' => "PO {$id} received into stock (dummy)",
            'receipt' => $payload,
        ]);
    }
}
