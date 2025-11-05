<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReplenishmentController extends Controller
{
    public function index(Request $r)
    {
        return response()->json([
            'ok' => true,
            'rules' => [],
            'note' => 'List reorder rules (min/max/MTO)',
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'product_id' => 'required|integer',
            'location_id'=> 'required|integer',
            'min_qty'    => 'required|numeric|min:0',
            'max_qty'    => 'required|numeric|gt:min_qty',
            'uom'        => 'required|string',
            'policy'     => 'nullable|string|in:MTS,MTO',
            'vendor_id'  => 'nullable|integer',
            'lead_time_days' => 'nullable|integer|min:0',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Reorder rule saved (dummy)',
            'rule' => array_merge(['id' => 1], $data),
        ], 201);
    }

    public function check(Request $r)
    {
        return response()->json([
            'ok' => true,
            'result' => [
                // contoh: product butuh PO
                // ['product_id' => 1, 'suggested_qty' => 200, 'uom' => 'kg']
            ],
        ]);
    }

    public function autoGenerate(Request $r)
    {
        return response()->json([
            'ok' => true,
            'message' => 'Auto-generated PO/MO based on rules (dummy)',
            'generated' => ['po_count' => 0, 'mo_count' => 0],
        ]);
    }
}
