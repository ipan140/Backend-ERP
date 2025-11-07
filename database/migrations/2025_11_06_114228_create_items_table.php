<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 100)->unique();
            $table->string('name');
            $table->string('uom', 20)->default('pcs'); // unit of measure
            $table->boolean('is_stockable')->default(true);
            $table->decimal('std_cost', 18, 6)->default(0);
            $table->timestamps();

            $table->index(['is_stockable']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('items');
    }
};
