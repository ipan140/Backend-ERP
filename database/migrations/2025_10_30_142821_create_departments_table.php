<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('departments', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('name', 100);
            $t->foreignId('parent_id')->nullable()->constrained('departments')->nullOnDelete();
            // Hindari circular FK: cukup index dulu
            $t->unsignedBigInteger('manager_employee_id')->nullable()->index();
            $t->boolean('active')->default(true);
            $t->softDeletes();
            $t->timestamps();
            $t->index('name');
        });
    }
    public function down(): void {
        Schema::dropIfExists('departments');
    }
};
