<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('shifts', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('name', 100);
            $t->time('time_start');
            $t->time('time_end');
            $t->unsignedInteger('break_minutes')->default(0);
            $t->boolean('is_night')->default(false);
            $t->text('description')->nullable();
            $t->boolean('active')->default(true);
            $t->softDeletes();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('shifts');
    }
};
