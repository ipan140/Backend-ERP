<?php

namespace App\Http\Controllers\Api\Sales\Quotation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationCRUDController extends Controller
{
    use QuotationHelper;

    /** GET /api/quotations?status=&customer_id= */
    public function index(Request $r)
    {
        $q = DB::table('quotations');

        if ($r->filled('status')) {
            $q->where('status', $r->input('status')); // ✅ pakai input(), bukan stringable
        }
        if ($r->filled('customer_id')) {
            $q->where('customer_id', (int) $r->customer_id);
        }

        return response()->json($q->orderByDesc('id')->paginate(20));
    }

    /** POST /api/quotations */
    public function store(Request $r)
    {
        // ✅ Normalisasi payload: izinkan qty ATAU quantity
        $payload = $r->all();
        $payload['items'] = array_map(function ($row) {
            $row['qty'] = $row['qty'] ?? ($row['quantity'] ?? null);
            return $row;
        }, $payload['items'] ?? []);

        // ✅ Validasi setelah dinormalisasi
        $data = validator($payload, [
            'customer_id'          => 'required|integer|exists:customers,id',
            'pricelist_id'         => 'nullable|integer|exists:pricelists,id',
            'valid_until'          => 'nullable|date|after_or_equal:today',
            'notes'                => 'nullable|string',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer|exists:products,id',
            'items.*.description'  => 'nullable|string',
            'items.*.qty'          => 'required|numeric|gt:0',    // ✅ sekarang aman
            'items.*.uom'          => 'nullable|string|max:32',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.discount_pct' => 'nullable|numeric|min:0|max:100',
        ])->validate();

        return DB::transaction(function () use ($data, $r) {
            $number = $this->nextNumber();

            $qid = DB::table('quotations')->insertGetId([
                'number'          => $number,
                'customer_id'     => $data['customer_id'],
                'pricelist_id'    => $data['pricelist_id'] ?? null,
                'valid_until'     => $data['valid_until'] ?? null,
                'status'          => 'draft',
                'subtotal'        => 0,
                'discount_amount' => 0,
                'tax_amount'      => 0,
                'total'           => 0,
                'notes'           => $data['notes'] ?? null,
                'extra'           => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            foreach ($data['items'] as $row) {
                // fallback default jika tidak dikirim
                $row['uom']          = $row['uom']          ?? 'pcs';
                $row['discount_pct'] = $row['discount_pct'] ?? 0;
                $this->insertItem($qid, $row); // diasumsikan expect: product_id, description, qty, uom, unit_price, discount_pct
            }

            $this->recalcTotals($qid);
            $this->logStatus($qid, null, 'draft', 'Created', $r->user()?->id);

            return response()->json([
                'quotation' => DB::table('quotations')->where('id', $qid)->first(),
                'items'     => DB::table('quotation_items')->where('quotation_id', $qid)->get(),
            ], 201);
        });
    }

    /** GET /api/quotations/{id} */
    public function show($id)
    {
        $q = DB::table('quotations')->where('id', (int)$id)->first();
        if (!$q) return response()->json(['message' => 'Quotation not found'], 404);

        $items = DB::table('quotation_items')->where('quotation_id', $id)->get();
        $logs  = DB::table('quotation_status_logs')->where('quotation_id', $id)->orderBy('id')->get();

        return response()->json(['quotation' => $q, 'items' => $items, 'logs' => $logs]);
    }

    /** PUT /api/quotations/{id} */
    public function update(Request $r, $id)
    {
        $q = DB::table('quotations')->where('id', (int)$id)->first();
        if (!$q) return response()->json(['message' => 'Quotation not found'], 404);
        if (!in_array($q->status, ['draft', 'sent'])) {
            return response()->json(['message' => 'Tidak bisa edit quotation yang sudah closed.'], 422);
        }

        // ✅ Normalisasi items (kalau dikirim)
        $payload = $r->all();
        if (!empty($payload['items']) && is_array($payload['items'])) {
            $payload['items'] = array_map(function ($row) {
                $row['qty'] = $row['qty'] ?? ($row['quantity'] ?? null);
                return $row;
            }, $payload['items']);
        }

        $data = validator($payload, [
            'pricelist_id'         => 'nullable|integer|exists:pricelists,id',
            'valid_until'          => 'nullable|date|after_or_equal:today',
            'notes'                => 'nullable|string',
            'items'                => 'nullable|array|min:1',
            'items.*.product_id'   => 'required_with:items|integer|exists:products,id',
            'items.*.description'  => 'nullable|string',
            'items.*.qty'          => 'required_with:items|numeric|gt:0',   // ✅ hasil normalisasi
            'items.*.uom'          => 'nullable|string|max:32',
            'items.*.unit_price'   => 'required_with:items|numeric|min:0',
            'items.*.discount_pct' => 'nullable|numeric|min:0|max:100',
        ])->validate();

        return DB::transaction(function () use ($id, $data) {
            DB::table('quotations')->where('id', $id)->update([
                'pricelist_id' => $data['pricelist_id'] ?? DB::raw('pricelist_id'),
                'valid_until'  => $data['valid_until']  ?? DB::raw('valid_until'),
                'notes'        => $data['notes']        ?? DB::raw('notes'),
                'updated_at'   => now(),
            ]);

            if (!empty($data['items'])) {
                DB::table('quotation_items')->where('quotation_id', $id)->delete();
                foreach ($data['items'] as $row) {
                    $row['uom']          = $row['uom']          ?? 'pcs';
                    $row['discount_pct'] = $row['discount_pct'] ?? 0;
                    $this->insertItem($id, $row);
                }
            }

            $this->recalcTotals($id);

            return response()->json([
                'quotation' => DB::table('quotations')->where('id', $id)->first(),
                'items'     => DB::table('quotation_items')->where('quotation_id', $id)->get(),
            ]);
        });
    }
}
