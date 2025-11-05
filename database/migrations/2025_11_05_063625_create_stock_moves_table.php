<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_moves', function (Blueprint $table) {
            $table->id();

            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            $table->decimal('qty', 16, 4)->default(0);

            // draft = belum dieksekusi, done = sudah memindahkan stok, cancelled = dibatalkan
            $table->enum('status', ['draft', 'done', 'cancelled'])->default('draft');

            // referensi sumber (opsional): shipment, replenishment, purchase, dll
            $table->string('reference_type')->nullable(); // contoh: 'shipment', 'replenishment'
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->timestamp('moved_at')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });

        // Jika kamu belum punya unique index untuk stock_levels, sangat disarankan:
        if (!Schema::hasTable('stock_levels')) {
            Schema::create('stock_levels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
                $table->decimal('qty_on_hand', 16, 4)->default(0);
                $table->decimal('min_level', 16, 4)->default(0);
                $table->decimal('max_level', 16, 4)->default(0);
                $table->timestamps();

                $table->unique(['warehouse_id', 'item_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_moves');
        // jangan drop stock_levels kalau sudah ada dipakai modul lain
        // Schema::dropIfExists('stock_levels');
    }
};
