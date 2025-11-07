<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('lots')->nullOnDelete();
            $table->decimal('qty', 18, 6);
            $table->string('uom', 20)->default('pcs');
            $table->timestamps();

            $table->index(['shipment_id','item_id','lot_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('shipment_items');
    }
};
