<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address')->nullable();
            $table->decimal('rating', 3, 2)->nullable(); // 0.00 - 5.00
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['active', 'rating']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('vendors');
    }
};
