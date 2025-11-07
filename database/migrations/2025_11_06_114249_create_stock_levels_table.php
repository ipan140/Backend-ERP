<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->decimal('qty', 18, 6)->default(0);
            $table->timestamps();

            $table->unique(['item_id','location_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('stock_levels');
    }
};
