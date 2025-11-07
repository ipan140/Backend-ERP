<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->dateTime('scheduled_date')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('technician')->nullable();
            $table->enum('status', ['open','scheduled','in_progress','done','cancelled'])->default('open');
            $table->enum('priority', ['low','normal','high'])->default('normal');
            $table->timestamps();

            $table->index(['asset_id','status','scheduled_date']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('work_orders');
    }
};
