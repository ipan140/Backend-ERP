<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    /**
     * GET /api/scm/purchases
     * Query:
     *   - search: string (mencari di number)
     *   - status: draft|confirmed|received|cancelled
     *   - per_page: int
     */
    public function index(Request $r)
    {
        $perPage = (int) $r->integer('per_page', 15);

        $q = DB::table('purchases as p')
            ->select('p.*')
            ->when($r->filled('search'), function ($qq) use ($r) {
                $s = trim($r->string('search'));
                $qq->where(function ($w) use ($s) {
                    $w->where('p.number', 'like', "%{$s}%");
                });
            })
            ->when($r->filled('status'), fn($qq) => $qq->where('p.status', $r->string('status')))
            ->orderByDesc('p.id');

        // ambil data
        $paginator = $q->paginate($perPage);

        // hitung items_count per PO (sekalian bentuk payload ramah frontend)
        $rows = collect($paginator->items())->map(function ($row) {
            $items = DB::table('purchase_items')->where('purchase_id', $row->id)->get();
            $row->items = $items;
            $row->items_count = $items->count();
            return $row;
        });

        return response()->json([
            'ok'       => true,
            'filters'  => $r->all(),
            'data'     => $rows,
            'meta'     => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * POST /api/scm/purchases
     * Body:
     *  vendor_id, expected_date?, items[{product_id,qty,uom,price}]
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'vendor_id'               => ['required','integer'],
            'expected_date'           => ['nullable','date'],
            'items'                   => ['required','array','min:1'],
            'items.*.product_id'      => ['required','integer'],
            'items.*.qty'             => ['required','numeric','min:0.0001'],
            'items.*.uom'             => ['required','string','max:20'],
            'items.*.price'           => ['required','numeric','min:0'],
        ]);

        $po = DB::transaction(function () use ($data) {
            // nomor sederhana
            $prefix = 'PO-'.date('Ym').'-';
            $seq    = str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
            $number = $prefix.$seq;

            $poId = DB::table('purchases')->insertGetId([
                'number'        => $number,
                'vendor_id'     => $data['vendor_id'],
                'expected_date' => $data['expected_date'] ?? null,
                'status'        => 'draft',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            foreach ($data['items'] as $it) {
                DB::table('purchase_items')->insert([
                    'purchase_id' => $poId,
                    'product_id'  => $it['product_id'],
                    'qty'         => $it['qty'],
                    'uom'         => $it['uom'],
                    'price'       => $it['price'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            $items = DB::table('purchase_items')->where('purchase_id', $poId)->get();

            return [
                'id'            => $poId,
                'number'        => $number,
                'vendor_id'     => $data['vendor_id'],
                'expected_date' => $data['expected_date'] ?? null,
                'status'        => 'draft',
                'items'         => $items,
            ];
        });

        return response()->json([
            'ok'      => true,
            'message' => 'PO created',
            'po'      => $po,
        ], 201);
    }

    /**
     * GET /api/scm/purchases/{id}
     */
    public function show(int $id)
    {
        $po = DB::table('purchases')->where('id', $id)->first();

        if (!$po) {
            return response()->json(['ok' => false, 'message' => 'PO not found'], 404);
        }

        $items = DB::table('purchase_items')->where('purchase_id', $id)->get();

        $po->items        = $items;
        $po->items_count  = $items->count();

        return response()->json([
            'ok' => true,
            'po' => $po,
        ]);
    }

    /**
     * PUT/PATCH /api/scm/purchases/{id}
     * Boleh kirim header saja atau sekalian items (replace).
     */
    public function update(Request $r, int $id)
    {
        $po = DB::table('purchases')->where('id', $id)->first();
        if (!$po) return response()->json(['ok' => false, 'message' => 'PO not found'], 404);

        $data = $r->validate([
            'vendor_id'               => ['sometimes','integer'],
            'expected_date'           => ['sometimes','nullable','date'],
            'status'                  => ['sometimes', Rule::in(['draft','confirmed','received','cancelled'])],
            'items'                   => ['sometimes','array','min:1'],
            'items.*.product_id'      => ['required_with:items','integer'],
            'items.*.qty'             => ['required_with:items','numeric','min:0.0001'],
            'items.*.uom'             => ['required_with:items','string','max:20'],
            'items.*.price'           => ['required_with:items','numeric','min:0'],
        ]);

        DB::transaction(function () use ($id, $data) {
            // update header
            $header = collect($data)->only(['vendor_id','expected_date','status'])->toArray();
            if ($header) {
                $header['updated_at'] = now();
                DB::table('purchases')->where('id', $id)->update($header);
            }

            // replace items bila dikirim
            if (isset($data['items'])) {
                DB::table('purchase_items')->where('purchase_id', $id)->delete();
                foreach ($data['items'] as $it) {
                    DB::table('purchase_items')->insert([
                        'purchase_id' => $id,
                        'product_id'  => $it['product_id'],
                        'qty'         => $it['qty'],
                        'uom'         => $it['uom'],
                        'price'       => $it['price'],
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }
        });

        return $this->show($id);
    }

    /**
     * DELETE /api/scm/purchases/{id}
     */
    public function destroy(int $id)
    {
        $exists = DB::table('purchases')->where('id', $id)->exists();
        if (!$exists) return response()->json(['ok' => false, 'message' => 'PO not found'], 404);

        DB::transaction(function () use ($id) {
            DB::table('purchase_items')->where('purchase_id', $id)->delete();
            DB::table('purchases')->where('id', $id)->delete();
        });

        return response()->json([
            'ok'      => true,
            'message' => "PO {$id} deleted",
        ]);
    }

    /**
     * POST /api/scm/purchases/{id}/confirm
     */
    public function confirm(int $id)
    {
        $po = DB::table('purchases')->where('id', $id)->first();
        if (!$po) return response()->json(['ok' => false, 'message' => 'PO not found'], 404);

        if ($po->status !== 'draft') {
            return response()->json(['ok' => false, 'message' => 'Only draft can be confirmed'], 422);
        }

        DB::table('purchases')->where('id', $id)->update([
            'status'     => 'confirmed',
            'updated_at' => now(),
        ]);

        return response()->json([
            'ok'      => true,
            'message' => "PO {$id} confirmed",
        ]);
    }

    /**
     * POST /api/scm/purchases/{id}/receive
     * Body:
     *  location_id, lines[{po_item_id, qty, lot?, expiry_date?}]
     * (Untuk saat ini: dummy. Nantinya bisa panggil service inventory untuk buat stock_moves.)
     */
    public function receive(Request $r, int $id)
    {
        $po = DB::table('purchases')->where('id', $id)->first();
        if (!$po) return response()->json(['ok' => false, 'message' => 'PO not found'], 404);

        $payload = $r->validate([
            'location_id'       => ['required','integer'],
            'lines'             => ['required','array','min:1'],
            'lines.*.po_item_id'=> ['required','integer'],
            'lines.*.qty'       => ['required','numeric','min:0.0001'],
            'lines.*.lot'       => ['nullable','string','max:100'],
            'lines.*.expiry_date'=>['nullable','date'],
        ]);

        // — Dummy: tandai received (opsional)
        DB::table('purchases')->where('id', $id)->update([
            'status'     => 'received',
            'updated_at' => now(),
        ]);

        // NOTE: Integrasi ke inventory:
        // - Buat stock_moves IN ke lokasi payload['location_id']
        // - Update stock_levels
        // - (opsional) buat lots dari 'lot' + expiry_date

        return response()->json([
            'ok'      => true,
            'message' => "PO {$id} received (dummy)",
            'receipt' => $payload,
        ]);
    }
}
