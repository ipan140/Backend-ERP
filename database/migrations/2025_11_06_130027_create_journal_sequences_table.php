<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('journal_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('account_journals')->cascadeOnDelete();
            $table->string('period', 7); // YYYY-MM
            $table->integer('last_number')->default(0);
            $table->timestamps();

            $table->unique(['journal_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_sequences');
    }
};