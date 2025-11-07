<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quality_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_inspection_id')->constrained('quality_inspections')->cascadeOnDelete();
            $table->string('parameter');      // contoh: moisture, size, color
            $table->string('unit', 20)->nullable(); // %
            $table->decimal('value', 18, 6)->nullable();
            $table->decimal('min', 18, 6)->nullable();
            $table->decimal('max', 18, 6)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('quality_inspection_items');
    }
};
