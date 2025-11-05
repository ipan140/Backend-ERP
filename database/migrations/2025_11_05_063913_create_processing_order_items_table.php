<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('processing_order_items', function (Blueprint $table) {
            $table->id();

            // Header
            $table->foreignId('processing_order_id')
                ->constrained('processing_orders')
                ->cascadeOnDelete();

            // Item yang dipakai/dihasilkan
            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnDelete();

            // Lokasi (opsional): di mana input diambil / output ditempatkan
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();

            // Kuantitas & UOM
            $table->unsignedInteger('qty');
            $table->string('uom', 32)->nullable();

            // Peran baris MO: input (bahan baku) atau output (hasil)
            $table->enum('role', ['input','output'])->default('input');

            // Catatan
            $table->text('notes')->nullable();

            $table->timestamps();

            // Index yang sering dipakai
            $table->index(['processing_order_id', 'role']);
            $table->index('item_id');
            $table->index('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processing_order_items');
    }
};
