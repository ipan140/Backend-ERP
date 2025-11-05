<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SalesOrderController extends Controller
{
    /**
     * GET /api/sales/orders
     * FE params: search, status, per_page, page
     */
    public function index(Request $req)
    {
        $q = SalesOrder::query()->with(['customer:id,name']);

        // ===== filtering yang cocok dengan FE =====
        if ($status = trim((string) $req->input('status', ''))) {
            $statuses = array_map(fn($s)=>strtolower(trim($s)), explode(',', $status));
            $q->whereIn('status', $statuses);
        }

        if ($kw = trim((string) $req->input('search', ''))) {
            $q->where(function ($w) use ($kw) {
                $w->where('number', 'like', "%{$kw}%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$kw}%"));
            });
        }

        // (opsional) filter tanggal
        if ($df = $req->input('date_from')) $q->whereDate('created_at', '>=', $df);
        if ($dt = $req->input('date_to'))   $q->whereDate('created_at', '<=', $dt);

        $q->orderByDesc('id');

        $perPage = (int) $req->input('per_page', 10);
        return response()->json($q->paginate($perPage));
    }

    /**
     * GET /api/sales/orders/{id}
     */
    public function show(int $id)
    {
        $order = SalesOrder::with(['items.product', 'customer'])->findOrFail($id);
        return response()->json($order);
    }

    /**
     * POST /api/sales/orders
     * Body dari FE: number?, customer_id*, order_date*, currency?, status?, subtotal?, tax_amount?, total?, notes?
     */
    public function store(Request $request)
    {
        $payload = $this->validated($request);

        return DB::transaction(function () use ($payload) {
            if (empty($payload['number'])) {
                $payload['number'] = $this->generateNumber();
            }
            // hitung total bila kosong
            $payload['total'] = $payload['total']
                ?? (($payload['subtotal'] ?? 0) + ($payload['tax_amount'] ?? 0));

            $order = SalesOrder::create($payload);

            // (opsional) kalau mau auto-buat items dari payload['items'] tambahkan di sini

            return response()->json($order->fresh(['customer:id,name']), 201);
        });
    }

    /**
     * PUT/PATCH /api/sales/orders/{id}
     */
    public function update(Request $request, int $id)
    {
        $order = SalesOrder::findOrFail($id);
        $payload = $this->validated($request, $order->id);

        return DB::transaction(function () use ($order, $payload) {
            if (!array_key_exists('total', $payload)) {
                $payload['total'] =
                    ($payload['subtotal'] ?? $order->subtotal ?? 0) +
                    ($payload['tax_amount'] ?? $order->tax_amount ?? 0);
            }
            $order->fill($payload)->save();

            // (opsional) sinkronkan items di sini jika dikirimkan

            return response()->json($order->fresh(['customer:id,name']));
        });
    }

    /**
     * DELETE /api/sales/orders/{id}
     */
    public function destroy(int $id)
    {
        $order = SalesOrder::findOrFail($id);

        if (in_array($order->status, ['delivered', 'invoiced'])) {
            return response()->json(['message' => 'Tidak dapat menghapus order yang sudah diproses.'], 422);
        }

        $order->delete();
        return response()->json(['deleted' => true]);
    }

    /**
     * POST /api/sales/orders/{id}/deliver
     */
    public function deliver(int $id)
    {
        $order = SalesOrder::findOrFail($id);

        // FE kamu memperbolehkan Deliver jika status 'confirmed'
        if (strtolower($order->status) !== 'confirmed') {
            return response()->json(['message' => 'Hanya order berstatus confirmed yang bisa deliver.'], 422);
        }

        $order->update([
            'status'       => 'delivered',
            'delivered_at' => now(),
        ]);

        return response()->json($order);
    }

    /**
     * POST /api/sales/orders/{id}/invoice
     * Versi sederhana (ubah sesuai arsitektur Invoice kamu)
     */
    public function makeInvoice(int $id)
    {
        return DB::transaction(function () use ($id) {
            $order = SalesOrder::with(['items', 'customer'])->lockForUpdate()->findOrFail($id);

            if (!in_array(strtolower($order->status), ['confirmed','delivered'])) {
                return response()->json(['message' => 'Order harus confirmed/delivered untuk membuat invoice.'], 422);
            }

            // idempotent: kalau sudah ada invoice aktif, kembalikan itu
            if (method_exists($order, 'invoices')) {
                $existing = $order->invoices()->whereIn('status', ['draft','posted','paid','partial'])->first();
                if ($existing) {
                    return response()->json([
                        'message' => 'Invoice already exists (idempotent).',
                        'data'    => $existing->load('items.product','customer')
                    ]);
                }
            }

            // hitung total dari items
            [$subtotal, $discount, $tax, $grand] = $this->computeTotals(
                $order->items->map(fn($i) => [
                    'qty'        => $i->qty,
                    'unit_price' => $i->unit_price,
                    'discount'   => $i->discount,
                    'tax_rate'   => $i->tax_rate,
                ])->all()
            );

            $invoice = Invoice::create([
                'order_id'       => $order->id,
                'customer_id'    => $order->customer_id,
                'status'         => 'draft',
                'currency'       => $order->currency,
                'subtotal'       => $subtotal,
                'discount_total' => $discount,
                'tax_total'      => $tax,
                'grand_total'    => $grand,
            ]);

            foreach ($order->items as $i) {
                $gross   = (float)$i->qty * (float)$i->unit_price;
                $base    = $gross - (float)$i->discount;
                $lineTax = round($base * (float)$i->tax_rate / 100, 2);
                $lineTot = $base + $lineTax;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $i->product_id,
                    'qty'        => $i->qty,
                    'uom'        => $i->uom,
                    'unit_price' => $i->unit_price,
                    'discount'   => $i->discount,
                    'tax_rate'   => $i->tax_rate,
                    'line_total' => $lineTot,
                ]);
            }

            // update status order (opsional)
            $order->update(['status' => 'invoiced']);

            return response()->json($invoice->load('items.product','customer'), 201);
        });
    }

    /* ===================== Helpers ===================== */

    /**
     * Validasi input store/update.
     */
    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'number'       => ['nullable','string','max:50', Rule::unique('sales_orders','number')->ignore($ignoreId)],
            'customer_id'  => ['required','integer', Rule::exists('customers','id')],
            'order_date'   => ['required','date'],
            'currency'     => ['nullable','string','max:10'],
            'status'       => ['nullable','string', Rule::in(['draft','confirmed','delivered','invoiced','cancelled'])],
            'subtotal'     => ['nullable','numeric','min:0'],
            'tax_amount'   => ['nullable','numeric','min:0'],
            'total'        => ['nullable','numeric','min:0'],
            'notes'        => ['nullable','string'],
        ], [
            'customer_id.required' => 'Customer ID wajib diisi.',
            'order_date.required'  => 'Order Date wajib diisi.',
        ]);
    }

    /**
     * Generator nomor simple: SO-YYYYMM-#### 
     */
    protected function generateNumber(): string
    {
        $prefix = 'SO-' . now()->format('Ym') . '-';
        $last = SalesOrder::where('number', 'like', $prefix.'%')
            ->orderByDesc('id')->value('number');

        $seq = 1;
        if ($last && preg_match('/-(\d{4})$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }
        return $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Hitung subtotal, diskon, pajak, grand total dari array lines:
     * line: qty, unit_price, discount (nominal), tax_rate (%)
     */
    private function computeTotals(array $lines): array
    {
        $subtotal = $discount = $tax = 0.0;

        foreach ($lines as $l) {
            $gross   = (float)$l['qty'] * (float)$l['unit_price'];
            $disc    = (float)($l['discount'] ?? 0);
            $base    = $gross - $disc;
            $lineTax = round($base * (float)($l['tax_rate'] ?? 0) / 100, 2);

            $subtotal += $gross;
            $discount += $disc;
            $tax      += $lineTax;
        }

        $grand = round($subtotal - $discount + $tax, 2);
        return [$subtotal, $discount, $tax, $grand];
    }
}
