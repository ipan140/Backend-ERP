<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('replenishments', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke barang & gudang
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();

            // Metode restock
            $table->enum('method', ['minmax', 'jit', 'periodic'])->default('minmax');

            // Parameter stok minimum & maksimum
            $table->integer('min_qty')->default(0);
            $table->integer('max_qty')->default(0);

            // Jumlah pesanan saat reorder
            $table->integer('reorder_qty')->default(0);

            // Status aktif/tidak
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replenishments');
    }
};
