<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('leave_types', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('name', 100);
            $t->text('description')->nullable();
            $t->unsignedInteger('default_days')->default(0);
            $t->boolean('active')->default(true);
            $t->softDeletes();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('leave_types');
    }
};
