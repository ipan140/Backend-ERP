<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('equipment', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();

            // tanpa FK (cukup index), sesuai pola “tanpa FK” yg kita pakai
            $table->unsignedBigInteger('asset_id')->nullable()->index();

            $table->string('name');
            $table->string('serial')->nullable();
            $table->string('category')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['category', 'active']);
            // kalau serial harus unik per alat:
            // $table->unique('serial');
        });
    }

    public function down(): void {
        Schema::dropIfExists('equipment');
    }
};
