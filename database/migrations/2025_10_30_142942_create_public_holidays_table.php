<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('public_holidays', function (Blueprint $t) {
            $t->id();
            $t->string('name', 150);
            $t->date('date');
            $t->boolean('is_national')->default(false);
            $t->text('note')->nullable();
            $t->softDeletes();
            $t->timestamps();
            $t->unique(['date', 'deleted_at']); // unique aware soft delete
        });
    }
    public function down(): void {
        Schema::dropIfExists('public_holidays');
    }
};
