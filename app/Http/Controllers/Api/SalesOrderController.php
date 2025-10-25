<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    public function index(Request $req)
    {
        $q = SalesOrder::query()->with('customer');

        // filtering
        if ($status = $req->string('status')->toString()) {
            $q->whereIn('status', array_map('trim', explode(',', $status)));
        }
        if ($kw = $req->string('q')->toString()) {
            $q->where(function ($w) use ($kw) {
                $w->where('number', 'like', "%$kw%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%$kw%"));
            });
        }
        if ($df = $req->date('date_from')) $q->whereDate('created_at', '>=', $df);
        if ($dt = $req->date('date_to'))   $q->whereDate('created_at', '<=', $dt);

        // sorting
        if ($sort = $req->string('sort')->toString()) {
            foreach (explode(',', $sort) as $srt) {
                $dir = str_starts_with($srt, '-') ? 'desc' : 'asc';
                $col = ltrim($srt, '-');
                if (in_array($col, ['created_at', 'grand_total', 'number'])) {
                    $q->orderBy($col, $dir);
                }
            }
        } else {
            $q->orderByDesc('id');
        }

        $perPage = (int)($req->input('per_page', 15));
        return response()->json($q->paginate($perPage));
    }

    public function show(int $id)
    {
        $order = SalesOrder::with(['items.product', 'customer'])->findOrFail($id);
        return response()->json($order);
    }

    public function makeInvoice(int $id)
    {
        return DB::transaction(function () use ($id) {
            $order = SalesOrder::with(['items', 'customer'])
                ->lockForUpdate()->findOrFail($id);

            // Idempotensi: jika sudah ada invoice (draft/posted/paid/partial), kembalikan yang ada
            $existing = $order->invoices()->whereIn('status', ['draft','posted','paid','partial'])->first();
            if ($existing) {
                return response()->json([
                    'message' => 'Invoice already exists (idempotent).',
                    'data'    => $existing->load('items.product', 'customer')
                ]);
            }

            // Hitung total server-side dari item order
            [$subtotal, $discount, $tax, $grand] = $this->computeTotals(
                $order->items->map(fn($i) => [
                    'qty' => $i->qty,
                    'unit_price' => $i->unit_price,
                    'discount' => $i->discount,
                    'tax_rate' => $i->tax_rate,
                ])->all()
            );

            $invoice = Invoice::create([
                'order_id'   => $order->id,
                'customer_id'=> $order->customer_id,
                'status'     => 'draft',
                'currency'   => $order->currency,
                'subtotal'   => $subtotal,
                'discount_total' => $discount,
                'tax_total'  => $tax,
                'grand_total'=> $grand,
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

            return response()->json($invoice->load('items.product','customer'), 201);
        });
    }

    public function deliver(int $id)
    {
        // Stub: untuk integrasi ke modul Inventory (stock move)
        $order = SalesOrder::findOrFail($id);
        if ($order->status !== 'sale') {
            return response()->json(['message' => 'Order must be in "sale" to deliver.'], 422);
        }
        $order->update(['status' => 'delivered', 'delivered_at' => now()]);
        return response()->json($order);
    }

    /**
     * Hitung subtotal, diskon, pajak, grand total dari array lines
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
