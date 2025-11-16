<?php

namespace App\Http\Controllers\SCM;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehousesController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::select('id', 'name')->orderBy('name')->get();

        return response()->json([
            'data' => $warehouses
        ]);
    }
}
