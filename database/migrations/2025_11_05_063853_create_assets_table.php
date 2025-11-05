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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            
            // Kode unik aset (misal AST-0001)
            $table->string('code')->unique();
            
            // Nama aset (misal: Pompa Air, Conveyor, dsb)
            $table->string('name');
            
            // Lokasi fisik aset (Plant, Gudang, Workshop, dsb)
            $table->string('location')->nullable();
            
            // Kategori aset (misal: mesin, kendaraan, peralatan)
            $table->string('category')->nullable();
            
            // Tanggal pembelian (opsional)
            $table->date('purchase_date')->nullable();
            
            // Nilai aset (harga atau nilai buku)
            $table->decimal('value', 15, 2)->default(0);
            
            // Status operasional
            $table->enum('status', ['active', 'maintenance', 'retired', 'disposed'])->default('active');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
