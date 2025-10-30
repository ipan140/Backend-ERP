<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payslip_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payslip_id')->constrained('payslips')->cascadeOnDelete();
            $t->unsignedInteger('seq')->default(1);
            $t->string('code', 50)->nullable();
            $t->string('name', 150);
            $t->enum('type', ['earning','deduction']);
            $t->decimal('qty', 12, 4)->nullable();
            $t->decimal('rate', 15, 2)->nullable();
            $t->decimal('amount', 15, 2)->default(0);
            $t->text('notes')->nullable();
            $t->softDeletes();
            $t->timestamps();
            $t->index(['payslip_id','type']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('payslip_lines');
    }
};
