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
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();

            // Relasi ke quotations (header)
            $table->unsignedBigInteger('quotation_id');
            $table->foreign('quotation_id')
                  ->references('id')
                  ->on('quotations')
                  ->onDelete('cascade'); // jika header dihapus, item ikut terhapus

            // Relasi ke produk (opsional: sesuaikan dengan nama tabel produkmu)
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products');

            // Data produk dalam quotation
            $table->string('description')->nullable();     // bisa override nama produk
            $table->decimal('qty', 18, 4);                // jumlah barang
            $table->string('uom', 32)->default('pcs');    // satuan (default pcs)
            $table->decimal('unit_price', 18, 2);         // harga satuan
            $table->decimal('discount_pct', 5, 2)->default(0);  // % diskon per item
            $table->decimal('line_total', 18, 2)->default(0);   // total per baris setelah diskon

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};