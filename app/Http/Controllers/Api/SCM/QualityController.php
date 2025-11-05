<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QualityController extends Controller
{
    public function checkpoints()
    {
        return response()->json([
            'ok' => true,
            'data' => [
                ['id' => 1, 'name' => 'Receipt QC'],
                ['id' => 2, 'name' => 'Pre-Delivery QC'],
            ],
        ]);
    }

    public function index(Request $r)
    {
        return response()->json([
            'ok' => true,
            'checks' => [],
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'lot_id'   => 'required|integer',
            'point'    => 'required|string', // Receipt / InProcess / Delivery
            'metrics'  => 'nullable|array',
            'result'   => 'required|string|in:pass,fail',
            'note'     => 'nullable|string',
            'photo_url'=> 'nullable|url',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Quality check saved (dummy)',
            'qc' => array_merge(['id' => 1], $data),
        ], 201);
    }

    public function nonconformance(Request $r)
    {
        $data = $r->validate([
            'lot_id' => 'required|integer',
            'reason' => 'required|string',
            'action' => 'nullable|string', // rework, scrap, downgrade
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Non-conformance recorded (dummy)',
            'nc' => $data,
        ]);
    }

    public function reports(Request $r)
    {
        return response()->json([
            'ok' => true,
            'summary' => [],
        ]);
    }
}
