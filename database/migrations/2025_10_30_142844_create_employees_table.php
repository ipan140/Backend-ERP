<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->string('emp_no', 30)->unique();
            $t->string('first_name', 100);
            $t->string('last_name', 100)->nullable();
            $t->string('full_name', 200);
            $t->string('email')->unique();
            $t->string('phone', 30)->nullable();

            $t->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $t->foreignId('job_position_id')->constrained('job_positions')->restrictOnDelete();
            $t->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();

            $t->date('hire_date');
            $t->enum('employment_type', ['permanent','contract','intern']);
            $t->enum('status', ['active','inactive'])->default('active');

            $t->enum('gender', ['male','female','other'])->nullable();
            $t->date('dob')->nullable();
            $t->text('address')->nullable();
            $t->string('city')->nullable();
            $t->string('province')->nullable();
            $t->string('country', 2)->nullable();
            $t->string('zip', 10)->nullable();
            $t->string('avatar_path')->nullable();

            $t->softDeletes();
            $t->timestamps();
            $t->index(['department_id','job_position_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('employees');
    }
};
