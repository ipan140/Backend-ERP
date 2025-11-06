<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountJournal;

class AccountJournalSeeder extends Seeder
{
    public function run(): void
    {
        // Company 1 default journals
        $rows = [
            ['company_id'=>1,'code'=>'BANK','name'=>'Bank','type'=>'bank','sequence_prefix'=>'BNK/%Y/%m/','sequence_padding'=>6,'active'=>true],
            ['company_id'=>1,'code'=>'CASH','name'=>'Cash','type'=>'cash','sequence_prefix'=>'CSH/%Y/%m/','sequence_padding'=>6,'active'=>true],
            ['company_id'=>1,'code'=>'SALE','name'=>'Sales','type'=>'sale','sequence_prefix'=>'SAL/%Y/%m/','sequence_padding'=>6,'active'=>true],
            ['company_id'=>1,'code'=>'GEN', 'name'=>'General','type'=>'general','sequence_prefix'=>'GEN/%Y/%m/','sequence_padding'=>6,'active'=>true],
        ];

        foreach ($rows as $r) {
            AccountJournal::firstOrCreate(
                ['company_id'=>$r['company_id'],'code'=>$r['code']],
                $r
            );
        }
    }
}
