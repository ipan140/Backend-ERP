<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use App\Models\SCM\ProcessingWorkOrder;
use App\Models\SCM\ProcessingInput;
use App\Models\SCM\ProcessingOutput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProcessingController extends Controller
{
    /**
     * GET /api/scm/processing/workorders
     * Query params: search, status (draft|in_progress|finished), date_from, date_to, per_page
     */
    public function index(Request $r)
    {
        $perPage = (int) $r->integer('per_page', 15);

        $q = ProcessingWorkOrder::query()
            ->with([
                'inputs:id,work_order_id,product_id,lot_id,qty,uom',
                'outputs:id,work_order_id,product_id,qty_plan,qty_actual,uom',
            ])
            ->when($r->filled('search'), fn ($qq) =>
                $qq->where(function ($w) use ($r) {
                    $term = trim($r->get('search'));
                    $w->where('name', 'like', "%{$term}%")
                      ->orWhere('id', $term);
                })
            )
            ->when($r->filled('status'), fn ($qq) =>
                $qq->where('status', $r->get('status'))
            )
            ->when($r->filled('date_from'), fn ($qq) =>
                $qq->whereDate('created_at', '>=', $r->date('date_from')->format('Y-m-d'))
            )
            ->when($r->filled('date_to'), fn ($qq) =>
                $qq->whereDate('created_at', '<=', $r->date('date_to')->format('Y-m-d'))
            )
            ->orderByDesc('id');

        return response()->json($q->paginate($perPage));
    }

    /**
     * GET /api/scm/processing/workorders/{id}
     */
    public function show($id)
    {
        $wo = ProcessingWorkOrder::with([
            'inputs:id,work_order_id,product_id,lot_id,qty,uom',
            'outputs:id,work_order_id,product_id,qty_plan,qty_actual,uom',
        ])->findOrFail($id);

        return response()->json([
            'ok' => true,
            'workorder' => $wo,
        ]);
    }

    /**
     * POST /api/scm/processing/workorders
     * Body:
     * {
     *   "name": "optional",
     *   "input": [{ "product_id": 1|null, "lot_id": 10|null, "qty": 2.5, "uom": "kg" }, ...],
     *   "output": [{ "product_id": 99, "qty": 1.2, "uom": "kg" }, ...]
     * }
     */
    public function store(Request $r)
    {
        // Validasi dasar
        $v = Validator::make($r->all(), [
            'name' => ['nullable', 'string', 'max:200'],

            'input' => ['required', 'array', 'min:1'],
            'input.*.product_id' => ['nullable', 'integer', 'min:1'],
            'input.*.lot_id'     => ['nullable', 'integer', 'min:1'],
            'input.*.qty'        => ['required', 'numeric', 'min:0.0001'],
            'input.*.uom'        => ['required', 'string', 'max:50'],

            'output' => ['required', 'array', 'min:1'],
            'output.*.product_id' => ['required', 'integer', 'min:1'],
            'output.*.qty'        => ['required', 'numeric', 'min:0.0001'],
            'output.*.uom'        => ['required', 'string', 'max:50'],
        ]);

        // Post-validation: setiap input WAJIB punya minimal product_id ATAU lot_id
        $v->after(function ($validator) use ($r) {
            $inputs = $r->input('input', []);
            foreach ($inputs as $idx => $row) {
                $hasProduct = !empty($row['product_id']);
                $hasLot     = !empty($row['lot_id']);
                if (!$hasProduct && !$hasLot) {
                    $validator->errors()->add("input.$idx.product_id", "Wajib isi minimal product_id atau lot_id.");
                    $validator->errors()->add("input.$idx.lot_id", "Wajib isi minimal product_id atau lot_id.");
                }
            }
        });

        $v->validate();

        // Mapping bersih
        $name   = trim((string) $r->input('name', ''));
        $inputs = collect($r->input('input'))->map(function ($row) {
            return [
                'product_id' => $row['product_id'] ?? null,
                'lot_id'     => $row['lot_id'] ?? null,
                'qty'        => (float) $row['qty'],
                'uom'        => trim((string) $row['uom']),
            ];
        });

        $outputs = collect($r->input('output'))->map(function ($row) {
            return [
                'product_id' => (int) $row['product_id'],
                'qty_plan'   => (float) $row['qty'],
                'uom'        => trim((string) $row['uom']),
            ];
        });

        // Simpan dalam transaksi
        $wo = DB::transaction(function () use ($name, $inputs, $outputs) {
            /** @var \App\Models\SCM\ProcessingWorkOrder $wo */
            $wo = ProcessingWorkOrder::create([
                'name'   => $name ?: null,
                'status' => 'draft',
            ]);

            // Inputs
            foreach ($inputs as $row) {
                $row['work_order_id'] = $wo->id;
                ProcessingInput::create($row);
            }

            // Outputs (plan)
            foreach ($outputs as $row) {
                $row['work_order_id'] = $wo->id;
                // qty_actual default null
                ProcessingOutput::create($row);
            }

            return $wo->load([
                'inputs:id,work_order_id,product_id,lot_id,qty,uom',
                'outputs:id,work_order_id,product_id,qty_plan,qty_actual,uom',
            ]);
        });

        return response()->json([
            'ok' => true,
            'message' => 'Work Order created',
            'workorder' => $wo,
        ], 201);
    }

    /**
     * POST /api/scm/processing/workorders/{id}/start
     * Status: draft -> in_progress
     */
    public function start($id)
    {
        $wo = ProcessingWorkOrder::findOrFail($id);

        if ($wo->status !== 'draft') {
            return response()->json([
                'ok' => false,
                'message' => 'Hanya WO berstatus draft yang bisa di-start.',
            ], 422);
        }

        $wo->status = 'in_progress';
        $wo->started_at = now();
        $wo->save();

        return response()->json([
            'ok' => true,
            'message' => "Work Order {$wo->id} started",
            'workorder' => $wo->fresh(['inputs', 'outputs']),
        ]);
    }

    /**
     * POST /api/scm/processing/workorders/{id}/finish
     * Body:
     * { "actual_output": [{ "product_id": 99, "qty": 1.0, "uom": "kg" }, ...] }  // opsional
     * - Jika tidak dikirim, qty_actual akan diisi sama dengan qty_plan
     * Status: in_progress -> finished
     */
    public function finish(Request $r, $id)
    {
        $wo = ProcessingWorkOrder::with('outputs')->findOrFail($id);

        if ($wo->status !== 'in_progress') {
            return response()->json([
                'ok' => false,
                'message' => 'Hanya WO berstatus in_progress yang bisa di-finish.',
            ], 422);
        }

        $data = $r->validate([
            'actual_output' => ['nullable', 'array'],
            'actual_output.*.product_id' => ['required_with:actual_output', 'integer', 'min:1'],
            'actual_output.*.qty'        => ['required_with:actual_output', 'numeric', 'min:0.0001'],
            'actual_output.*.uom'        => ['required_with:actual_output', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($wo, $data) {
            // Jika ada actual_output → map ke outputs (by product_id + uom)
            if (!empty($data['actual_output'])) {
                $byKey = [];
                foreach ($wo->outputs as $out) {
                    $key = $out->product_id . '|' . $out->uom;
                    $byKey[$key] = $out;
                }

                foreach ($data['actual_output'] as $row) {
                    $key = ((int) $row['product_id']) . '|' . trim((string) $row['uom']);
                    if (isset($byKey[$key])) {
                        $out = $byKey[$key];
                        $out->qty_actual = (float) $row['qty'];
                        $out->save();
                    } else {
                        // Jika pasangan product_id + uom belum ada di plan, bisa pilih:
                        // 1) buat baris baru sebagai actual-only, atau
                        // 2) abaikan.
                        // Di sini kita pilih: buat baris baru dengan qty_plan = 0
                        ProcessingOutput::create([
                            'work_order_id' => $wo->id,
                            'product_id'    => (int) $row['product_id'],
                            'uom'           => trim((string) $row['uom']),
                            'qty_plan'      => 0,
                            'qty_actual'    => (float) $row['qty'],
                        ]);
                    }
                }
            } else {
                // Jika tak ada actual_output → samakan actual = plan
                foreach ($wo->outputs as $out) {
                    $out->qty_actual = $out->qty_plan;
                    $out->save();
                }
            }

            $wo->status = 'finished';
            $wo->finished_at = now();
            $wo->save();
        });

        $wo = $wo->fresh(['inputs', 'outputs']);

        return response()->json([
            'ok' => true,
            'message' => "Work Order {$wo->id} finished",
            'workorder' => $wo,
        ]);
    }
}
