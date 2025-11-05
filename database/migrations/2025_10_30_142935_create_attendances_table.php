<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('attendances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();

            $t->timestamp('check_in')->nullable();
            $t->timestamp('check_out')->nullable();

            $t->integer('work_minutes')->default(0);
            $t->integer('late_minutes')->default(0);
            $t->integer('overtime_minutes')->default(0);

            $t->enum('status', ['present','absent','leave','holiday','sick'])->default('present');
            $t->text('note')->nullable();

            $t->softDeletes();
            $t->timestamps();
            $t->index(['employee_id','check_in','check_out']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('attendances');
    }
};
