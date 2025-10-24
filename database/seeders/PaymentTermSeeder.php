<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentTermSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('payment_terms')->insert([
            ['name' => 'COD',   'days' => 0,  'description' => null, 'created_at'=>now(), 'updated_at'=>now()],
            ['name' => 'Net 7', 'days' => 7,  'description' => null, 'created_at'=>now(), 'updated_at'=>now()],
            ['name' => 'Net 14','days' => 14, 'description' => null, 'created_at'=>now(), 'updated_at'=>now()],
            ['name' => 'Net 30','days' => 30, 'description' => null, 'created_at'=>now(), 'updated_at'=>now()],
        ]);
    }
}
