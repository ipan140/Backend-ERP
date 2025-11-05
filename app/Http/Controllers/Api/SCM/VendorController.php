<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $r)
    {
        return response()->json([
            'ok' => true,
            'data' => [],
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'  => 'required|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'payment_term_days' => 'nullable|integer|min:0',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Vendor created (dummy)',
            'vendor' => array_merge(['id' => 1], $data),
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            'ok' => true,
            'vendor' => ['id' => (int)$id, 'name' => 'Dummy Vendor'],
        ]);
    }

    public function update(Request $r, $id)
    {
        return response()->json([
            'ok' => true,
            'message' => "Vendor {$id} updated (dummy)",
            'changes' => $r->all(),
        ]);
    }

    public function destroy($id)
    {
        return response()->json([
            'ok' => true,
            'message' => "Vendor {$id} deleted (dummy)",
        ]);
    }

    public function rating(Request $r)
    {
        return response()->json([
            'ok' => true,
            'scorecard' => [],
        ]);
    }
}
