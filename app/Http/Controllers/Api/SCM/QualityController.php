<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{QualityInspection, QualityInspectionItem, Lot};

class QualityController extends Controller
{
    public function checkpoints()
    {
        return response()->json([
            'ok'=>true,
            'data'=>[
                ['id'=>1,'name'=>'Receipt QC'],
                ['id'=>2,'name'=>'In-Process QC'],
                ['id'=>3,'name'=>'Pre-Delivery QC'],
            ],
        ]);
    }

    public function index(Request $r)
    {
        $q = QualityInspection::with('items','lot.item');
        if ($r->filled('result')) $q->where('result',$r->result);
        return response()->json($q->orderByDesc('id')->paginate(15));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'lot_id'  => 'required|integer|exists:lots,id',
            'point'   => 'required|string|max:100', // e.g. Receipt / InProcess / Delivery
            'result'  => 'required|in:pass,fail',
            'note'    => 'nullable|string',
            'metrics' => 'nullable|array',
            'items'   => 'nullable|array',
            'items.*.parameter' => 'required_with:items|string|max:100',
            'items.*.value'     => 'nullable|string|max:100',
        ]);

        $qc = QualityInspection::create([
            'lot_id' => $data['lot_id'],
            'point'  => $data['point'],
            'result' => $data['result'],
            'note'   => $data['note'] ?? null,
            'metrics'=> $data['metrics'] ?? null,
        ]);

        if (!empty($data['items'])) {
            foreach ($data['items'] as $it) {
                QualityInspectionItem::create($it + ['quality_inspection_id'=>$qc->id]);
            }
        }

        return response()->json(['ok'=>true,'qc'=>$qc->load('items')], 201);
    }

    public function nonconformance(Request $r)
    {
        $data = $r->validate([
            'lot_id' => 'required|integer|exists:lots,id',
            'reason' => 'required|string',
            'action' => 'nullable|string|in:rework,scrap,downgrade,hold',
        ]);

        // kamu bisa catat di table khusus NC jika ada
        return response()->json(['ok'=>true,'message'=>'Non-conformance recorded','data'=>$data]);
    }

    public function reports()
    {
        $summary = [
            'total' => QualityInspection::count(),
            'pass'  => QualityInspection::where('result','pass')->count(),
            'fail'  => QualityInspection::where('result','fail')->count(),
        ];
        return response()->json(['ok'=>true,'summary'=>$summary]);
    }
}
