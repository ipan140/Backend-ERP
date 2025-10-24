<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricelists', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->string('name', 100)->unique();     // unik agar tidak dobel
            $table->string('currency', 8)->default('IDR');
            $table->enum('type', ['sale','purchase'])->default('sale'); // jenis pricelist
            $table->text('description')->nullable();    // keterangan opsional
            $table->date('valid_from')->nullable();     // periode aktif (opsional)
            $table->date('valid_until')->nullable();    // periode aktif (opsional)
            $table->boolean('active')->default(true);

            $table->timestamps();

            // index yang sering dipakai untuk filter
            $table->index(['active', 'type']);
            $table->index('valid_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricelists');
    }
};
