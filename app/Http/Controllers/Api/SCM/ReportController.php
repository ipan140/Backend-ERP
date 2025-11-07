<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{StockMove, StockLevel, Purchase, Shipment, WorkOrder};

class ReportController extends Controller
{
    public function index(Request $r)
    {
        return response()->json([
            'ok'=>true,
            'available'=>[
                'stock-movements' => route('scm.reports.show','stock-movements'),
                'stock-levels'    => route('scm.reports.show','stock-levels'),
                'purchases'       => route('scm.reports.show','purchases'),
                'shipments'       => route('scm.reports.show','shipments'),
                'workorders'      => route('scm.reports.show','workorders'),
            ],
        ]);
    }

    public function show($key, Request $r)
    {
        switch ($key) {
            case 'stock-movements':
                $q = StockMove::with(['item:id,sku,name','fromLocation:id,code','toLocation:id,code'])
                     ->orderByDesc('moved_at');
                return response()->json($q->paginate(50));

            case 'stock-levels':
                $q = StockLevel::with(['item:id,sku,name,uom','location:id,code,name','location.warehouse:id,name']);
                return response()->json($q->paginate(50));

            case 'purchases':
                $q = Purchase::with('vendor:id,name')->orderByDesc('id');
                return response()->json($q->paginate(50));

            case 'shipments':
                $q = Shipment::with('vendor:id,name')->orderByDesc('id');
                return response()->json($q->paginate(50));

            case 'workorders':
                $q = WorkOrder::orderByDesc('id');
                return response()->json($q->paginate(50));
        }

        return response()->json(['ok'=>false,'message'=>'Unknown report key'], 404);
    }
}
