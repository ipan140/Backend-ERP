<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{
    WorkOrder,
    WorkOrderInput,
    WorkOrderOutput,
    Lot,
    StockMove,
    StockLevel
};

class ProcessingController extends Controller
{
    /**
     * GET /api/scm/processing/workorders
     * Kembalikan { workorders: [...] } agar cocok dengan Vue.
     */
    public function index()
    {
        $list = WorkOrder::with(['asset','inputs','outputs'])
            ->orderByDesc('id')
            ->get()
            ->map(function ($wo) {
                return [
                    'id'     => $wo->id,
                    // Komponen Vue menampilkan r.name → ambil dari title bila ada
                    'name'   => $wo->title ?? $wo->number ?? ('WO#'.$wo->id),
                    'status' => $wo->status ?? 'draft',
                    // Normalisasi input/output menjadi array sederhana
                    'input'  => $wo->inputs->map(fn ($i) => [
                        'product_id' => $i->product_id,
                        'uom'        => $i->uom,
                        'qty'        => (float)$i->qty,
                        'lot_id'     => $i->lot_id,
                    ])->values(),
                    'output' => $wo->outputs->map(fn ($o) => [
                        'product_id' => $o->product_id,
                        'uom'        => $o->uom,
                        // Tampilkan rencana bila actual belum diisi
                        'qty'        => (float)($o->qty_actual ?: $o->qty_plan),
                    ])->values(),
                ];
            });

        return response()->json(['workorders' => $list]);
    }

    /**
     * GET /api/scm/processing/workorders/{id}
     */
    public function show($id)
    {
        $wo = WorkOrder::with(['asset','inputs.lot','outputs'])->findOrFail($id);

        $payload = [
            'id'       => $wo->id,
            'name'     => $wo->title ?? $wo->number ?? ('WO#'.$wo->id),
            'status'   => $wo->status ?? 'draft',
            'asset_id' => $wo->asset_id,
            'inputs'   => $wo->inputs->map(fn ($i) => [
                'product_id' => $i->product_id,
                'uom'        => $i->uom,
                'qty'        => (float)$i->qty,
                'lot_id'     => $i->lot_id,
            ])->values(),
            'outputs'  => $wo->outputs->map(fn ($o) => [
                'product_id' => $o->product_id,
                'uom'        => $o->uom,
                'qty_plan'   => (float)$o->qty_plan,
                'qty_actual' => (float)$o->qty_actual,
            ])->values(),
        ];

        return response()->json(['ok' => true, 'workorder' => $payload]);
    }

    /**
     * POST /api/scm/processing/workorders
     * Komponen kirim { name?, input[], output[] } → kita dukung “name” (opsional).
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'asset_id'       => 'nullable|integer|exists:assets,id',
            'title'          => 'nullable|string|max:200', // dukung kalau backend lama kirim "title"
            'name'           => 'nullable|string|max:200', // dari Vue
            'notes'          => 'nullable|string',
            'scheduled_date' => 'nullable|date',
            'priority'       => 'nullable|in:low,normal,high',

            'input'               => 'required|array|min:1',
            'input.*.lot_id'      => 'nullable|integer|exists:lots,id',
            'input.*.product_id'  => 'required_without:input.*.lot_id|integer|exists:items,id',
            'input.*.qty'         => 'required|numeric|min:0.0001',
            'input.*.uom'         => 'required|string|max:50',

            'output'              => 'required|array|min:1',
            'output.*.product_id' => 'required|integer|exists:items,id',
            'output.*.qty'        => 'required|numeric|min:0.0001',
            'output.*.uom'        => 'required|string|max:50',
        ]);

        return DB::transaction(function () use ($data) {
            $wo = WorkOrder::create([
                'asset_id'       => $data['asset_id'] ?? null,
                'title'          => $data['title'] ?? ($data['name'] ?? null),
                'notes'          => $data['notes'] ?? null,
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'priority'       => $data['priority'] ?? 'normal',
                'status'         => 'draft',
                'number'         => 'WO' . now()->format('ymdHis'),
            ]);

            foreach ($data['input'] as $in) {
                // Jika pakai lot_id, turunkan product_id dari lot
                $productId = $in['product_id'] ?? optional(Lot::find($in['lot_id']))->item_id;

                WorkOrderInput::create([
                    'work_order_id' => $wo->id,
                    'product_id'    => $productId,
                    'lot_id'        => $in['lot_id'] ?? null,
                    'qty'           => $in['qty'],
                    'uom'           => $in['uom'],
                ]);
            }

            foreach ($data['output'] as $out) {
                WorkOrderOutput::create([
                    'work_order_id' => $wo->id,
                    'product_id'    => $out['product_id'],
                    'qty_plan'      => $out['qty'],
                    'qty_actual'    => 0,
                    'uom'           => $out['uom'],
                ]);
            }

            // Kembalikan bentuk yang “klik” dengan frontend
            $payload = [
                'id'     => $wo->id,
                'name'   => $wo->title ?? $wo->number ?? ('WO#'.$wo->id),
                'status' => $wo->status,
                'input'  => $wo->inputs()->get()->map(fn ($i) => [
                    'product_id' => $i->product_id,
                    'uom'        => $i->uom,
                    'qty'        => (float)$i->qty,
                    'lot_id'     => $i->lot_id,
                ])->values(),
                'output' => $wo->outputs()->get()->map(fn ($o) => [
                    'product_id' => $o->product_id,
                    'uom'        => $o->uom,
                    'qty'        => (float)$o->qty_plan,
                ])->values(),
            ];

            return response()->json(['ok' => true, 'wo' => $payload], 201);
        });
    }

    /**
     * POST /api/scm/processing/workorders/{id}/start
     * Frontend harap status → in_progress
     */
    public function start($id)
    {
        $wo = WorkOrder::findOrFail($id);

        if (!in_array($wo->status, ['draft', 'scheduled'], true)) {
            return response()->json(['ok' => false, 'message' => 'WO must be draft/scheduled'], 422);
        }

        $wo->update(['status' => 'in_progress']);

        return response()->json(['ok' => true, 'workorder' => [
            'id'     => $wo->id,
            'name'   => $wo->title ?? $wo->number ?? ('WO#'.$wo->id),
            'status' => $wo->status,
        ]]);
    }

    /**
     * POST /api/scm/processing/workorders/{id}/finish
     * Frontend kirim { actual_output:[{product_id,uom,qty}] } (opsional)
     * Status diubah ke 'finished' (samakan dgn Vue).
     */
    public function finish(Request $r, $id)
    {
        $wo = WorkOrder::with(['inputs','outputs'])->findOrFail($id);

        $payload = $r->validate([
            'actual_output'               => 'nullable|array',
            'actual_output.*.product_id'  => 'required|integer|exists:items,id',
            'actual_output.*.qty'         => 'required|numeric|min:0.0001',
            'actual_output.*.uom'         => 'required|string|max:50',
        ]);

        DB::transaction(function () use ($wo, $payload) {
            // 1) Konsumsi input → tulis StockMove OUT (jika tabel/kolom ada)
            foreach ($wo->inputs as $in) {
                StockMove::create([
                    'item_id'         => $in->product_id,
                    'from_location_id'=> $in->from_location_id ?? null, // biarkan null jika tidak pakai lokasi
                    'to_location_id'  => null,
                    'qty'             => $in->qty,
                    'uom'             => $in->uom,
                    'type'            => 'out',
                    'lot_id'          => $in->lot_id,
                    'ref'             => 'WO#'.$wo->id.'-consume',
                    'moved_at'        => now(),
                ]);

                if (!empty($in->from_location_id)) {
                    $level = StockLevel::firstOrCreate(
                        ['item_id' => $in->product_id, 'location_id' => $in->from_location_id],
                        ['qty' => 0]
                    );
                    $level->decrement('qty', $in->qty);
                }

                // Hapus update ke kolom qty lot karena skema lots kita tidak punya kolom qty
                // (Jangan decrement Lot)
            }

            // 2) Hasil produksi
            $outs = $payload['actual_output'] ?? $wo->outputs->map(fn($o) => [
                'product_id' => $o->product_id,
                'qty'        => $o->qty_plan,
                'uom'        => $o->uom,
            ])->toArray();

            foreach ($outs as $o) {
                // Update qty_actual pada baris output yg sesuai product_id
                $row = $wo->outputs->firstWhere('product_id', $o['product_id']);
                if ($row) {
                    $row->update(['qty_actual' => $o['qty']]);
                }

                StockMove::create([
                    'item_id'         => $o['product_id'],
                    'from_location_id'=> null,
                    'to_location_id'  => $row->to_location_id ?? null,
                    'qty'             => $o['qty'],
                    'uom'             => $o['uom'],
                    'type'            => 'in',
                    'ref'             => 'WO#'.$wo->id.'-produce',
                    'moved_at'        => now(),
                ]);

                if (!empty($row?->to_location_id)) {
                    $level = StockLevel::firstOrCreate(
                        ['item_id' => $o['product_id'], 'location_id' => $row->to_location_id],
                        ['qty' => 0]
                    );
                    $level->increment('qty', $o['qty']);
                }
            }

            $wo->update(['status' => 'finished', 'completed_at' => now()]);
        });

        return response()->json([
            'ok'        => true,
            'message'   => 'WO finished',
            'workorder' => [
                'id'     => $wo->id,
                'name'   => $wo->title ?? $wo->number ?? ('WO#'.$wo->id),
                'status' => 'finished',
            ],
        ]);
    }
}
