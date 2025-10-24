<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuotationStatusLogSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('quotation_status_logs')->insertOrIgnore([
            [
                'quotation_id' => 1,
                'from_status' => null,
                'to_status' => 'draft',
                'reason' => 'Created by seeder',
                'changed_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
