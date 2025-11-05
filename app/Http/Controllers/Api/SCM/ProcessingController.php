<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProcessingController extends Controller
{
    public function index()
    {
        return response()->json([
            'ok' => true,
            'workorders' => [],
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => 'nullable|string',
            'input' => 'required|array|min:1',
            'input.*.lot_id' => 'nullable|integer',
            'input.*.product_id' => 'required_without:input.*.lot_id|integer',
            'input.*.qty' => 'required|numeric|min:0.0001',
            'input.*.uom' => 'required|string',
            'output' => 'required|array|min:1',
            'output.*.product_id' => 'required|integer',
            'output.*.qty' => 'required|numeric|min:0.0001',
            'output.*.uom' => 'required|string',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Work Order created (dummy)',
            'wo' => array_merge(['id' => 1, 'status' => 'draft'], $data),
        ], 201);
    }

    public function start($id)
    {
        return response()->json([
            'ok' => true,
            'message' => "Work Order {$id} started (dummy)",
        ]);
    }

    public function finish(Request $r, $id)
    {
        $data = $r->validate([
            'actual_output' => 'nullable|array',
        ]);

        return response()->json([
            'ok' => true,
            'message' => "Work Order {$id} finished (dummy)",
            'result' => $data,
        ]);
    }
}
