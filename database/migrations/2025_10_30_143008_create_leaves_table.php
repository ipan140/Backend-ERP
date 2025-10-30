<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('leaves', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();

            $t->date('date_start');
            $t->date('date_end');
            $t->unsignedInteger('days')->default(1);

            $t->text('reason')->nullable();
            $t->string('attachment_path')->nullable();

            $t->enum('status', ['draft','submitted','approved','rejected','cancelled'])->default('draft');
            $t->foreignId('approver_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();

            $t->softDeletes();
            $t->timestamps();
            $t->index(['employee_id','leave_type_id','status']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('leaves');
    }
};
