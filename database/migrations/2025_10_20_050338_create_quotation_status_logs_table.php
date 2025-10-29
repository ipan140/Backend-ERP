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
        Schema::create('quotation_status_logs', function (Blueprint $table) {
            $table->id();

            // Relasi ke quotations (header penawaran)
            $table->foreignId('quotation_id')
                  ->constrained('quotations')
                  ->onDelete('cascade');

            // Status sebelum dan sesudah
            $table->enum('from_status', ['draft', 'sent', 'won', 'lost', 'expired'])->nullable();
            $table->enum('to_status', ['draft', 'sent', 'won', 'lost', 'expired']);

            // Siapa yang mengubah (user ID dari tabel users)
            $table->foreignId('changed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Waktu perubahan (opsional, tapi controller kamu pakai ini)
            $table->timestamp('changed_at')->nullable();

            // Catatan / alasan perubahan status
            $table->text('reason')->nullable();

            // created_at = waktu log dibuat, updated_at = waktu edit log (jarang dipakai)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_status_logs');
    }
};
