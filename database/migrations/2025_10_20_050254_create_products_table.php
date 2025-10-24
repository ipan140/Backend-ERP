<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->engine = 'InnoDB'; // penting untuk FK di MySQL

            $table->id();                          // BIGINT UNSIGNED PK
            $table->string('sku')->unique();       // Kode unik, contoh: P-0001
            $table->string('name');                // Nama produk
            $table->string('uom', 32)->default('pcs'); // Satuan default
            $table->decimal('base_price', 18, 2)->default(0); // Harga dasar (opsional)
            $table->boolean('active')->default(true);        // Status produk

            // kolom opsional lain bisa ditambah nanti (description, tax, dsb)
            $table->timestamps();

            // index tambahan yang sering dipakai
            $table->index(['active']);
            $table->index(['name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
