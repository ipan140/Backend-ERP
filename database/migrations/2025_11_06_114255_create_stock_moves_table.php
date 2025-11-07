<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock_moves', function (Blueprint $table) {
            $table->id();

            // Relasi utama
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('lots')->nullOnDelete();

            // Data kuantitas
            $table->decimal('qty', 18, 6);
            $table->string('uom', 20)->default('pcs');

            // Kolom tambahan
            $table->enum('type', ['receipt', 'transfer', 'adjust'])->default('receipt');
            $table->timestamp('moved_at')->nullable(); // waktu perpindahan stok
            $table->string('ref')->nullable();         // nomor referensi (purchase/shipment/wo)
            $table->enum('state', ['draft', 'done', 'cancelled'])->default('done');

            $table->timestamps();

            // Index
            $table->index(['item_id', 'from_location_id', 'to_location_id', 'lot_id'], 'idx_stock_moves_refs');
        });
    }

    public function down(): void {
        Schema::dropIfExists('stock_moves');
    }
};
