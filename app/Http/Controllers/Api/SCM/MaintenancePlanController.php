<?php

namespace App\Http\Controllers;

use App\Models\MaintenancePlan;

class MaintenancePlanController extends Controller
{
    public function index()
    {
        return response()->json([
            'ok' => true,
            'plans' => MaintenancePlan::with('equipment')->orderBy('id','desc')->paginate(20)
        ]);
    }
}
