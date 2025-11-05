<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{SalesOrder, SalesOrderItem, Quotation, Product, Customer};

class SalesSeeder extends Seeder
{
    public function run(): void
    {
        // ====== Bersihkan data secara aman (tanpa TRUNCATE) ======
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // Hapus tabel anak dulu → baru induk. Sertakan invoices jika ada FKs ke sales_orders.
        if (DB::getSchemaBuilder()->hasTable('invoice_items')) {
            DB::table('invoice_items')->delete();
            try { DB::unprepared('ALTER TABLE invoice_items AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}
        }
        if (DB::getSchemaBuilder()->hasTable('invoices')) {
            DB::table('invoices')->delete();
            try { DB::unprepared('ALTER TABLE invoices AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}
        }

        DB::table('sales_order_items')->delete();
        try { DB::unprepared('ALTER TABLE sales_order_items AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}

        DB::table('sales_orders')->delete();
        try { DB::unprepared('ALTER TABLE sales_orders AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}

        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        // ====== Ambil referensi wajib ======
        $quotation = Quotation::first();
        $product   = Product::first();
        $customer  = Customer::first();

        if (!$quotation || !$product || !$customer) {
            $this->command?->warn('⚠️ SalesSeeder: Pastikan Quotation, Product, dan Customer sudah ada sebelum seeding Sales.');
            return;
        }

        // ====== Hitung angka agar konsisten ======
        $qty         = 10;
        $uom         = 'liter';
        $unitPrice   = 120000;      // IDR
        $discount    = 0;           // nominal
        $taxRate     = 11;          // PPN 11%
        $subTotal    = $qty * $unitPrice;                      // 1,200,000
        $discountTot = 0 + $discount;                          // 0
        $taxBase     = $subTotal - $discountTot;               // 1,200,000
        $taxTotal    = (int) round($taxBase * ($taxRate/100)); // 132,000
        $grandTotal  = $taxBase + $taxTotal;                   // 1,332,000

        // ====== Buat Sales Order ======
        $order = SalesOrder::create([
            'quotation_id'   => $quotation->id,
            'customer_id'    => $customer->id,
            'number'         => 'SO-' . now()->format('Ymd') . '-0001',
            'status'         => 'sale',     // atau 'draft' sesuai workflow-mu
            'currency'       => 'IDR',
            'subtotal'       => $subTotal,
            'discount_total' => $discountTot,
            'tax_total'      => $taxTotal,
            'grand_total'    => $grandTotal,
        ]);

        // ====== Buat 1 item ======
        SalesOrderItem::create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'qty'        => $qty,
            'uom'        => $uom,
            'unit_price' => $unitPrice,
            'discount'   => $discount,     // nominal
            'tax_rate'   => $taxRate,
            'line_total' => $grandTotal,   // subtotal - discount + tax
        ]);

        $this->command?->info('✅ SalesSeeder: 1 Sales Order + item berhasil dibuat (SO total: ' . number_format($grandTotal, 0, ',', '.') . ').');
    }
}
