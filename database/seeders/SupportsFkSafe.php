<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;

trait SupportsFkSafe
{
    protected function fkOff(): void
    {
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}
    }

    protected function fkOn(): void
    {
        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}
    }

    /**
     * Hapus isi tabel dengan aman.
     * - default: delete() agar anti-1701
     * - kalau mau cepat: set $truncate=true (pastikan di dalam fkOff()..fkOn())
     */
    protected function wipe(string $table, bool $truncate = false): void
    {
        if (!DB::getSchemaBuilder()->hasTable($table)) return;

        if ($truncate) {
            DB::table($table)->truncate();
        } else {
            DB::table($table)->delete();
        }
    }
}
