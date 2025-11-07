<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('qty', 18, 6);
            $table->string('uom', 20)->default('pcs');
            $table->decimal('price', 18, 6)->default(0);
            $table->decimal('subtotal', 18, 6)->default(0);
            $table->timestamps();

            $table->index(['purchase_id','item_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('purchase_items');
    }
};
