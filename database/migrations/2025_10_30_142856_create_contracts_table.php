<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('contract_no', 50)->unique();
            $t->enum('type', ['permanent','contract','intern']);
            $t->date('start_date');
            $t->date('end_date')->nullable();
            $t->decimal('basic_salary', 15, 2)->nullable();
            $t->text('notes')->nullable();
            $t->softDeletes();
            $t->timestamps();
            $t->index(['employee_id','type','start_date']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('contracts');
    }
};
