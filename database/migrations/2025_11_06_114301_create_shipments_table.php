<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->enum('direction', ['inbound','outbound'])->default('inbound');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->unsignedBigInteger('partner_id')->nullable(); // vendor/customer id (generic)
            $table->string('partner_type')->nullable(); // 'vendor' / 'customer'
            $table->enum('status', ['draft','confirmed','delivered','cancelled'])->default('draft');
            $table->date('scheduled_date')->nullable();
            $table->timestamps();

            $table->index(['direction','status','warehouse_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('shipments');
    }
};
