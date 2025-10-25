<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\SalesOrder;
use App\Models\Product;
use App\Models\Customer;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        // kosongkan tabel dulu
        Invoice::truncate();
        InvoiceItem::truncate();

        $order    = SalesOrder::first();
        $product  = Product::first();
        $customer = Customer::first();

        if (!$order || !$product || !$customer) {
            $this->command->warn('⚠️ Pastikan SalesOrder, Product, dan Customer sudah ada sebelum seeding Invoice.');
            return;
        }

        // buat invoice dummy
        $invoice = Invoice::create([
            'order_id'        => $order->id,
            'customer_id'     => $customer->id,
            'number'          => 'INV-' . now()->format('Ymd') . '-0001',
            'status'          => 'draft',
            'currency'        => 'IDR',
            'subtotal'        => 1200000,
            'discount_total'  => 0,
            'tax_total'       => 132000,
            'grand_total'     => 1332000,
            'posted_at'       => null,
            'paid_at'         => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'qty'        => 10,
            'uom'        => 'liter',
            'unit_price' => 120000,
            'discount'   => 0,
            'tax_rate'   => 11,
            'line_total' => 1332000,
        ]);

        $this->command->info('✅ Dummy Invoice berhasil dibuat.');
    }
}
