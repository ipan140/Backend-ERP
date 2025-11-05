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
        Schema::create('processing_orders', function (Blueprint $table) {
        $table->id();
        $table->string('number')->unique();
        $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
        $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
        $table->foreignId('finished_item_id')->nullable()->constrained('products')->nullOnDelete();
        $table->decimal('qty', 15, 3)->nullable();
        $table->date('date')->nullable();
        $table->enum('status', ['draft', 'in_progress', 'done', 'cancelled'])->default('draft');
        $table->timestamp('started_at')->nullable();
        $table->timestamp('finished_at')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processing_orders');
    }
};
