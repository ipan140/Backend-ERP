<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('maintenance_plans', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();

            // tanpa FK, cukup index
            $table->unsignedBigInteger('equipment_id')->index();

            $table->enum('frequency', ['weekly','monthly','quarterly','semiannual','annual'])->default('monthly');
            $table->date('next_date')->nullable();
            $table->text('procedure')->nullable();
            $table->timestamps();

            $table->index(['frequency','next_date'], 'mp_freq_next_idx');
        });
    }

    public function down(): void {
        Schema::dropIfExists('maintenance_plans');
    }
};
