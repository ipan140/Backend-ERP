<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();

            // nomor WO unik (opsional, bisa dipakai untuk penomoran human readable)
            $table->string('number')->unique();

            // relasi ke asset (nullable supaya bisa bikin WO umum)
            $table->foreignId('asset_id')
                  ->nullable()
                  ->constrained('assets')
                  ->nullOnDelete();

            // field inti yang dipakai seeder/model
            $table->string('title');                        // ex: "Servis rutin pompa"
            $table->text('notes')->nullable();              // ex: "Ganti oli & cek seal"
            $table->dateTime('scheduled_date')->nullable(); // jadwal dikerjakan
            $table->dateTime('completed_at')->nullable();   // selesai kapan

            // info teknisi (opsional)
            $table->string('technician')->nullable();

            // status & prioritas
            $table->enum('status', ['open','scheduled','in_progress','done','cancelled'])
                  ->default('open');
            $table->enum('priority', ['low','normal','high'])->default('normal');

            $table->timestamps();

            // index bantu
            $table->index(['asset_id', 'status']);
            $table->index('scheduled_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
