<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            AccountJournalSeeder::class,
            AccountSeeder::class,
            JournalSequenceSeeder::class,
            DemoMovesSeeder::class,
        ]);
    }
}
