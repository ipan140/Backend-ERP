<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();

            // relasi ke shipment
            $table->foreignId('shipment_id')
                ->constrained('shipments')
                ->cascadeOnDelete();

            // relasi ke item
            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnDelete();

            // ✅ tambahkan warehouse_id biar sesuai dengan Seeder dan relasi gudang
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();

            $table->integer('qty');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
    }
};
