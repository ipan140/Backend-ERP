<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('job_positions', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('name', 100);
            $t->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $t->text('description')->nullable();
            $t->boolean('active')->default(true);
            $t->softDeletes();
            $t->timestamps();
            $t->index(['department_id','name']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('job_positions');
    }
};
