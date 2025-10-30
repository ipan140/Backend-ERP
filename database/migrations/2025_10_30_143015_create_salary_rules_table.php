<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('salary_rules', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name', 150);
            $t->enum('type', ['earning','deduction']);
            $t->enum('amount_type', ['fixed','percent']);
            $t->decimal('fixed_amount', 15, 2)->nullable();
            $t->decimal('percent', 5, 2)->nullable(); // 0..100
            $t->enum('percent_base', ['basic','gross'])->nullable();
            $t->boolean('active')->default(true);
            $t->text('description')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('salary_rules');
    }
};
