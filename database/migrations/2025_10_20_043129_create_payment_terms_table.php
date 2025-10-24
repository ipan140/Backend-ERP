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
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id(); // BIGINT unsigned primary key

            // Nama termin pembayaran
            // Contoh: "Cash On Delivery", "Net 30", "Net 14"
            $table->string('name');

            // Jumlah hari pembayaran setelah invoice terbit
            // 0 = COD (langsung), 30 = bayar maksimal 30 hari setelah invoice
            $table->unsignedSmallInteger('days')->default(0);

            // Keterangan tambahan (opsional)
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_terms');
    }
};
