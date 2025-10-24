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
            $table->unsignedBigInteger('quotation_id');
            $table->foreign('quotation_id')
                  ->references('id')
                  ->on('quotations')
                  ->onDelete('cascade');

            // Status sebelum dan sesudah
            $table->enum('from_status', ['draft', 'sent', 'won', 'lost', 'expired'])->nullable();
            $table->enum('to_status', ['draft', 'sent', 'won', 'lost', 'expired']);

            // Siapa yang mengubah (user ID dari tabel users) - bisa null (jika auto expired oleh sistem)
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->foreign('changed_by')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();

            // Alasan atau catatan perubahan status
            $table->text('reason')->nullable();

            $table->timestamps(); // created_at = waktu perubahan status
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