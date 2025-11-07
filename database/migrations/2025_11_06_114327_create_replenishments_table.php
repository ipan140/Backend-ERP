<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('replenishments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->decimal('min_qty', 18, 6)->default(0);
            $table->decimal('max_qty', 18, 6)->default(0);
            $table->decimal('reorder_qty', 18, 6)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['item_id','warehouse_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('replenishments');
    }
};
