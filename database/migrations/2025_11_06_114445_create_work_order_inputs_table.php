<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('work_order_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('lots')->nullOnDelete();
            $table->decimal('qty', 18, 6);
            $table->string('uom', 20)->default('pcs');
            $table->timestamps();

            $table->index(['work_order_id','item_id','lot_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('work_order_inputs');
    }
};
