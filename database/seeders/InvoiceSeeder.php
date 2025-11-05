<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{Invoice, InvoiceItem, SalesOrder, Product, Customer};
use App\Models\SalesOrderItem; // jika model ini ada

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        // ====== Bersihkan data secara aman (tanpa TRUNCATE) ======
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // Hapus child dulu → baru parent
        DB::table('invoice_items')->delete();
        try { DB::unprepared('ALTER TABLE invoice_items AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}

        DB::table('invoices')->delete();
        try { DB::unprepared('ALTER TABLE invoices AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}

        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        // ====== Ambil referensi wajib ======
        $order    = SalesOrder::first();
        $product  = Product::first();
        $customer = Customer::first();

        if (!$order || !$product || !$customer) {
            $this->command?->warn('⚠️ InvoiceSeeder: Pastikan SalesOrder, Product, dan Customer sudah ada sebelum seeding Invoice.');
            return;
        }

        // ====== Ambil item dari SalesOrder bila ada (lebih konsisten) ======
        $soItem = SalesOrderItem::where('order_id', $order->id)->first();

        if ($soItem) {
            $qty        = (int) ($soItem->qty ?? 1);
            $uom        = $soItem->uom ?? 'unit';
            $unitPrice  = (int) ($soItem->unit_price ?? 0);
            $discount   = (int) ($soItem->discount ?? 0);     // asumsi nominal
            $taxRate    = (int) ($soItem->tax_rate ?? 0);     // persen

            $subTotal    = $qty * $unitPrice;
            $discountTot = $discount;
            $taxBase     = $subTotal - $discountTot;
            $taxTotal    = (int) round($taxBase * ($taxRate / 100));
            $grandTotal  = $taxBase + $taxTotal;
        } else {
            // Fallback angka default (jika SO tidak punya item terkait)
            $qty        = 10;
            $uom        = 'liter';
            $unitPrice  = 120000;
            $discount   = 0;
            $taxRate    = 11;

            $subTotal    = $qty * $unitPrice;                 // 1,200,000
            $discountTot = 0;
            $taxBase     = $subTotal - $discountTot;          // 1,200,000
            $taxTotal    = (int) round($taxBase * 0.11);      // 132,000
            $grandTotal  = $taxBase + $taxTotal;              // 1,332,000
        }

        // ====== Buat invoice header ======
        $invoice = Invoice::create([
            'order_id'        => $order->id,
            'customer_id'     => $customer->id,
            'number'          => 'INV-' . now()->format('Ymd') . '-0001',
            'status'          => 'draft',     // atau 'posted' sesuai workflow
            'currency'        => 'IDR',
            'subtotal'        => $subTotal,
            'discount_total'  => $discountTot,
            'tax_total'       => $taxTotal,
            'grand_total'     => $grandTotal,
            'posted_at'       => null,
            'paid_at'         => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // ====== Buat 1 invoice item ======
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'qty'        => $qty,
            'uom'        => $uom,
            'unit_price' => $unitPrice,
            'discount'   => $discount,   // nominal
            'tax_rate'   => $taxRate,    // %
            'line_total' => $grandTotal, // subtotal - discount + tax (baris tunggal)
        ]);

        $this->command?->info('✅ InvoiceSeeder: 1 invoice dibuat (total: ' . number_format($grandTotal, 0, ',', '.') . ').');
    }
}
