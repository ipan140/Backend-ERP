<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Customer, PaymentTerm};
use Carbon\Carbon;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $term = PaymentTerm::first(); // boleh null

        $rows = [
            [
                'code'            => 'CUST001',
                'name'            => 'PT Tani Makmur Sejahtera',
                'email'           => 'tani@example.com',
                'phone'           => null,
                'address'         => null,
                'payment_term_id' => optional($term)->id, // atau null
                'credit_limit'    => 0,
                'is_active'       => true,
                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now(),
            ],
        ];

        Customer::upsert(
            $rows,
            ['code'], // unique key
            ['name','email','phone','address','payment_term_id','credit_limit','is_active','updated_at']
        );
    }
}
