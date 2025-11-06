<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // Assets
            ['company_id'=>1, 'code'=>'1101', 'name'=>'Cash',                 'type'=>'asset'],
            ['company_id'=>1, 'code'=>'1102', 'name'=>'Bank',                 'type'=>'asset'],
            ['company_id'=>1, 'code'=>'1201', 'name'=>'Accounts Receivable',  'type'=>'asset'],

            // Liabilities
            ['company_id'=>1, 'code'=>'2101', 'name'=>'Accounts Payable',     'type'=>'liability'],
            ['company_id'=>1, 'code'=>'2105', 'name'=>'VAT Output',           'type'=>'liability'],

            // Equity
            ['company_id'=>1, 'code'=>'3101', 'name'=>'Owner Equity',         'type'=>'equity'],

            // Income & Expense
            // Jika enum di migrasi kamu pakai 'revenue', ganti 'income' -> 'revenue'
            ['company_id'=>1, 'code'=>'4101', 'name'=>'Sales Revenue',        'type'=>'income'],
            ['company_id'=>1, 'code'=>'5101', 'name'=>'COGS / Expense',       'type'=>'expense'],
        ];

        foreach ($rows as $a) {
            Account::updateOrCreate(
                ['company_id' => $a['company_id'], 'code' => $a['code']],
                [
                    'name' => $a['name'],
                    'type' => $a['type'],
                    // ⛔ Jangan kirim kolom yang belum ada di DB:
                    // 'parent_id', 'level', 'active', 'reconcile'
                ]
            );
        }
    }
}
