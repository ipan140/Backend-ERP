<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Quotation;
use App\Models\Product;
use App\Models\Customer;

class SalesSeeder extends Seeder
{
    public function run(): void
    {
        SalesOrder::truncate();
        SalesOrderItem::truncate();

        $quotation = Quotation::first();
        $product   = Product::first();
        $customer  = Customer::first();

        if (!$quotation || !$product || !$customer) {
            $this->command->warn('⚠️ Pastikan Quotation, Product, dan Customer sudah ada sebelum seeding Sales.');
            return;
        }

        $order = SalesOrder::create([
            'quotation_id'   => $quotation->id,
            'customer_id'    => $customer->id,
            'number'         => 'SO-' . now()->format('Ymd') . '-0001',
            'status'         => 'sale',
            'currency'       => 'IDR',
            'subtotal'       => 1200000,
            'discount_total' => 0,
            'tax_total'      => 132000,
            'grand_total'    => 1332000,
        ]);

        SalesOrderItem::create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'qty'        => 10,
            'uom'        => 'liter',
            'unit_price' => 120000,
            'discount'   => 0,
            'tax_rate'   => 11,
            'line_total' => 1332000,
        ]);

        $this->command->info('✅ Dummy Sales Order berhasil dibuat.');
    }
}
