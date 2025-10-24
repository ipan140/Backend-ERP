<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pricelist_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->foreignId('pricelist_id')->constrained('pricelists')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // null = pakai base_price dari products
            $table->decimal('price', 18, 2)->nullable();
            $table->decimal('min_qty', 12, 2)->default(1);

            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();

            // 1 rule unik per (pricelist, product, min_qty)
            $table->unique(['pricelist_id', 'product_id', 'min_qty']);
            $table->index(['active']);
            $table->index(['date_start', 'date_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricelist_items');
    }
};
