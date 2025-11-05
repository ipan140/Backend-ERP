<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::create('quality_inspection_items', function (Blueprint $table) {
            $table->id();

            // Relasi ke tabel induk QC
            $table->foreignId('quality_inspection_id')
                ->constrained('quality_inspections')
                ->cascadeOnDelete();

            // Barang yang diperiksa
            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnDelete();

            // Jumlah dicek dan hasilnya
            $table->integer('qty_checked')->default(0);
            $table->integer('qty_passed')->default(0);
            $table->integer('qty_failed')->default(0);

            // Kolom tambahan untuk detail QC
            $table->enum('result', ['ok', 'defect'])->default('ok');
            $table->string('defect_code')->nullable(); // jika ada kode cacat
            $table->text('remarks')->nullable();       // catatan tambahan

            $table->timestamps();
        });
    }

    /**
     * Rollback migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_inspection_items');
    }
};
