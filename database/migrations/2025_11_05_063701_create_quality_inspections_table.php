<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_inspections', function (Blueprint $table) {
            $table->id();

            // Nomor QC
            $table->string('number')->unique();

            // Referensi fleksibel: shipment / purchase / processing / work_order
            $table->enum('reference_type', ['shipment','purchase','processing','work_order'])->index();
            $table->unsignedBigInteger('reference_id')->nullable()->index();

            // Metadata QC
            $table->timestamp('inspected_at')->nullable();
            $table->string('inspector_name')->nullable();

            // Status sesuai seeder (bukan approved/rejected)
            $table->enum('status', ['pending','passed','failed','rework'])->default('pending')->index();

            // Catatan opsional
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_inspections');
    }
};
