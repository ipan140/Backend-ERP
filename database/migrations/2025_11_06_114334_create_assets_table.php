<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->date('acquired_at')->nullable();
            $table->string('serial')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['category','active']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('assets');
    }
};
