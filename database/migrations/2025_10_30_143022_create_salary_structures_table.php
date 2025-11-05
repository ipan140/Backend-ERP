<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('salary_structures', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name', 150);
            $t->decimal('base_basic', 15, 2)->nullable();
            $t->boolean('active')->default(true);
            $t->text('description')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('salary_structures');
    }
};
