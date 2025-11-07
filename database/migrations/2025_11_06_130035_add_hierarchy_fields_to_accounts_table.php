<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('accounts', function (Blueprint $table) {
            // hierarchy & status
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete()->after('type');
            $table->unsignedTinyInteger('level')->default(1)->after('parent_id');
            $table->boolean('active')->default(true)->after('level');

            // (opsional) kalau belum ada kolom reconcile, tambahkan:
            if (!Schema::hasColumn('accounts', 'reconcile')) {
                $table->boolean('reconcile')->default(false)->after('active');
            }
        });
    }

    public function down(): void {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'reconcile')) $table->dropColumn('reconcile');
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id','level','active']);
        });
    }
};