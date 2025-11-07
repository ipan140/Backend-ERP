<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();

            // tanpa FK, cukup index (konsisten dg lots/equipment kamu)
            $table->unsignedBigInteger('equipment_id')->index();

            $table->enum('type', ['corrective','preventive'])->default('corrective');
            $table->text('note')->nullable();
            $table->enum('status', ['open','in_progress','done','cancelled'])->default('open');
            $table->timestamps();

            $table->index(['type','status'], 'mr_type_status_idx');
        });
    }

    public function down(): void {
        Schema::dropIfExists('maintenance_requests');
    }
};
