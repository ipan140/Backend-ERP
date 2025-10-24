<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1) User untuk login (email & password pasti)
        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name'              => 'Test User',
                'password'          => Hash::make('password'), // <- login pakai 'password'
                'email_verified_at' => now(),
            ]
        );

        // 2) (Opsional) matikan FK checks saat seeding awal
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // 3) Jalankan seeders berurutan (hindari FK error)
        $this->call([
            CustomerSeeder::class,
            ProductSeeder::class,
            QuotationSeeder::class,
            QuotationItemSeeder::class,
            QuotationStatusLogSeeder::class,
            PaymentTermSeeder::class,   // ⬅️ tambahkan
            PricelistSeeder::class
        ]);

        // 4) Aktifkan lagi FK checks
        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}
    }
}
