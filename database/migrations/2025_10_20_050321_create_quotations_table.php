<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            // Pastikan pakai InnoDB (untuk FK)
            $table->engine = 'InnoDB';

            $table->id();

            // Auto-numbering, contoh: QO-202510-0001
            $table->string('number')->unique();

            // FK customers & pricelists (aman dan konsisten tipe)
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->cascadeOnDelete();

            $table->foreignId('pricelist_id')
                  ->nullable()
                  ->constrained('pricelists')
                  ->nullOnDelete();

            // Metadata & perhitungan
            $table->date('valid_until')->nullable()->index();
            $table->enum('status', ['draft','sent','won','lost','expired'])->default('draft')->index();

            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            $table->text('notes')->nullable();
            $table->json('extra')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};