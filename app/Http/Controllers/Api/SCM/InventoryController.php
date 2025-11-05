<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function stocks(Request $r)
    {
        return response()->json([
            'ok' => true,
            'filters' => $r->all(),
            'data' => [
                // contoh dummy
                ['product_id' => 1, 'location_id' => 10, 'qty' => 120.5, 'uom' => 'kg'],
            ],
        ]);
    }

    public function receipt(Request $r)
    {
        $data = $r->validate([
            'location_id' => 'required|integer',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|integer',
            'lines.*.qty'        => 'required|numeric|min:0.0001',
            'lines.*.uom'        => 'required|string',
            'lines.*.lot'        => 'nullable|string',
            'lines.*.expiry_date'=> 'nullable|date',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Stock receipt posted (dummy)',
            'receipt' => $data,
        ], 201);
    }

    public function transfer(Request $r)
    {
        $data = $r->validate([
            'from_location_id' => 'required|integer|different:to_location_id',
            'to_location_id'   => 'required|integer',
            'lines' => 'required|array|min:1',
            'lines.*.lot_id'   => 'nullable|integer',
            'lines.*.product_id'=> 'required_without:lines.*.lot_id|integer',
            'lines.*.qty'      => 'required|numeric|min:0.0001',
            'lines.*.uom'      => 'required|string',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Internal transfer executed (dummy)',
            'transfer' => $data,
        ]);
    }

    public function adjust(Request $r)
    {
        $data = $r->validate([
            'location_id' => 'required|integer',
            'reason'      => 'required|string|in:count,waste,shrink,other',
            'lines'       => 'required|array|min:1',
            'lines.*.lot_id'    => 'nullable|integer',
            'lines.*.product_id'=> 'required_without:lines.*.lot_id|integer',
            'lines.*.qty'       => 'required|numeric', // +/-
            'lines.*.uom'       => 'required|string',
            'note'        => 'nullable|string',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Stock adjustment recorded (dummy)',
            'adjustment' => $data,
        ]);
    }

    public function lots(Request $r)
    {
        return response()->json([
            'ok' => true,
            'filters' => $r->all(),
            'data' => [
                [
                    'id' => 1001,
                    'product_id' => 1,
                    'grade' => 'A',
                    'qty' => 80,
                    'uom' => 'kg',
                    'harvest_date' => '2025-10-01',
                    'expiry_date' => '2025-11-15',
                    'status' => 'available',
                ],
            ],
        ]);
    }

    public function storeLot(Request $r)
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
            'ok' => true,
            'message' => 'Lot created (dummy)',
            'lot' => array_merge(['id' => 1002, 'status' => 'available'], $data),
        ], 201);
    }

    public function expiryAlerts(Request $r)
    {
        return response()->json([
            'ok' => true,
            'threshold_days' => (int)($r->get('days', 14)),
            'data' => [],
        ]);
    }
}
