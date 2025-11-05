<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration.
     */
    public function up(): void
    {
        if (!Schema::hasTable('shipments')) { // ✅ pastikan belum ada
            Schema::create('shipments', function (Blueprint $table) {
                $table->id();
                $table->string('number')->unique();
                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
                $table->date('date')->nullable();
                $table->enum('status', ['draft', 'in_transit', 'delivered', 'cancelled'])->default('draft');
                $table->timestamps();
            });
        }
    }

    /**
     * Hapus migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
