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
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED primary key

            // Kode unik untuk customer (misal: CUST-0001)
            $table->string('code')->unique();

            // Nama utama customer / perusahaan
            $table->string('name');

            // Kontak dasar
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // Alamat penagihan (billing address)
            $table->text('address')->nullable();

            // Relasi ke payment_terms (nullable, jika tidak semua punya term khusus)
            $table->foreignId('payment_term_id')
                  ->nullable()
                  ->constrained('payment_terms')
                  ->nullOnDelete();

            // Batas kredit (limit hutang), default=0 artinya tidak ada kredit
            $table->decimal('credit_limit', 18, 2)->default(0);

            // Status aktif customer (1 = aktif, 0 = nonaktif)
            $table->boolean('is_active')->default(true);

            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
