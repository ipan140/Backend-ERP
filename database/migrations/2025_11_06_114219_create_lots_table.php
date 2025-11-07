<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id(); // primary key (bigint unsigned)

            // item_id tanpa FK (cukup index agar cepat dicari)
            $table->unsignedBigInteger('item_id')->index();

            // nomor lot unik per item
            $table->string('number', 100);
            $table->unique(['item_id', 'number'], 'lots_item_number_unique');

            // tanggal produksi & kedaluwarsa
            $table->date('mfg_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->index('expiry_date', 'lots_expiry_date_index');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
