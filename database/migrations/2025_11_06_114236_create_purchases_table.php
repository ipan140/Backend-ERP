<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->date('order_date')->nullable();
            $table->enum('status', ['draft','confirmed','received','cancelled'])->default('draft');
            $table->decimal('total', 18, 6)->default(0);
            $table->timestamps();

            $table->index(['vendor_id','status','order_date']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('purchases');
    }
};
