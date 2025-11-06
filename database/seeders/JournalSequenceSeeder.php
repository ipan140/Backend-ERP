<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{AccountJournal, JournalSequence};

class JournalSequenceSeeder extends Seeder
{
    public function run(): void
    {
        $journals = AccountJournal::all();

        foreach ($journals as $j) {
            JournalSequence::firstOrCreate(
                ['journal_id' => $j->id, 'period' => now()->format('Y-m')],
                ['last_number' => 0]
            );
        }
    }
}
