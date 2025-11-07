<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quality_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->nullable()->constrained('lots')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->enum('point', ['receipt','in_process','delivery'])->default('receipt');
            $table->enum('result', ['pass','fail'])->default('pass');
            $table->json('metrics')->nullable(); // fleksibel simpan nilai pengukuran
            $table->text('note')->nullable();
            $table->string('photo_url')->nullable();
            $table->timestamps();

            $table->index(['point','result']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('quality_inspections');
    }
};
