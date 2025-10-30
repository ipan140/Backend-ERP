<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('salary_structure_rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('salary_structure_id')->constrained('salary_structures')->cascadeOnDelete();
            $t->foreignId('salary_rule_id')->constrained('salary_rules')->restrictOnDelete();
            $t->unsignedInteger('seq')->default(1);
            $t->timestamps();
            $t->unique(['salary_structure_id','salary_rule_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('salary_structure_rules');
    }
};
