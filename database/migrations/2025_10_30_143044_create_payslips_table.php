<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payslips', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $t->date('period_start');
            $t->date('period_end');
            $t->enum('status', ['draft','submitted','approved','paid','cancelled'])->default('draft');

            $t->decimal('basic_salary', 15, 2)->default(0);
            $t->decimal('gross_earnings', 15, 2)->default(0);
            $t->decimal('total_deductions', 15, 2)->default(0);
            $t->decimal('net_pay', 15, 2)->default(0);

            $t->text('notes')->nullable();

            $t->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('posted_at')->nullable();

            $t->softDeletes();
            $t->timestamps();
            $t->index(['employee_id','status','period_start','period_end']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('payslips');
    }
};
