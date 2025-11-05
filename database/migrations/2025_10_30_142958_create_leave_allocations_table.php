<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('leave_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $t->integer('year');
            $t->unsignedInteger('allocated_days')->default(0);
            $t->unsignedInteger('used_days')->default(0);
            $t->softDeletes();
            $t->timestamps();
            $t->unique(['employee_id','leave_type_id','year']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('leave_allocations');
    }
};
