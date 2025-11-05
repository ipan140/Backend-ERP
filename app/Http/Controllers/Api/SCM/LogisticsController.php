<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogisticsController extends Controller
{
    public function index(Request $r)
    {
        return response()->json([
            'ok' => true,
            'deliveries' => [],
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'so_id'      => 'nullable|integer',
            'ship_date'  => 'required|date',
            'carrier'    => 'nullable|string',
            'route'      => 'nullable|string',
            'items'      => 'required|array|min:1',
            'items.*.lot_id'    => 'nullable|integer',
            'items.*.product_id'=> 'required_without:items.*.lot_id|integer',
            'items.*.qty'       => 'required|numeric|min:0.0001',
            'items.*.uom'       => 'required|string',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Delivery created (dummy)',
            'delivery' => array_merge(['id' => 1, 'status' => 'draft'], $data),
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            'ok' => true,
            'delivery' => ['id' => (int)$id, 'status' => 'draft', 'items' => []],
        ]);
    }

    public function confirm($id)
    {
        return response()->json([
            'ok' => true,
            'message' => "Delivery {$id} confirmed (dummy)",
        ]);
    }

    public function proofOfDelivery(Request $r, $id)
    {
        $data = $r->validate([
            'signed_by' => 'nullable|string',
            'photo_url' => 'nullable|url',
            'note'      => 'nullable|string',
        ]);

        return response()->json([
            'ok' => true,
            'message' => "POD for Delivery {$id} stored (dummy)",
            'pod' => $data,
        ]);
    }
}
