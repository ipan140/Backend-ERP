<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained('sales_orders')->restrictOnDelete();
            $t->foreignId('customer_id')->constrained('customers')->restrictOnDelete();

            $t->string('number')->unique()->nullable();       // INV number (optional auto-generator)
            $t->string('status')->default('draft');           // draft, posted, paid, partial, overdue
            $t->string('currency', 3)->default('IDR');

            $t->decimal('subtotal', 18, 2)->default(0);
            $t->decimal('discount_total', 18, 2)->default(0);
            $t->decimal('tax_total', 18, 2)->default(0);
            $t->decimal('grand_total', 18, 2)->default(0);

            $t->timestamp('posted_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $t->decimal('qty', 18, 3);
            $t->string('uom', 32)->nullable();
            $t->decimal('unit_price', 18, 2);
            $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax_rate', 5, 2)->default(0);
            $t->decimal('line_total', 18, 2)->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
