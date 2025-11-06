<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{Move, MoveLine, AccountJournal, JournalSequence};

class DemoMovesSeeder extends Seeder
{
    public function run(): void
    {
        // dates
        $d1 = now()->startOfMonth()->addDays(1)->toDateString();
        $d2 = now()->startOfMonth()->addDays(3)->toDateString();
        $d3 = now()->startOfMonth()->addDays(10)->toDateString();

        // lookup journals & accounts (Company 1)
        $bankJ = AccountJournal::where('company_id',1)->where('code','BANK')->firstOrFail();
        $saleJ = AccountJournal::where('company_id',1)->where('code','SALE')->firstOrFail();

        $acc = fn($code) => DB::table('accounts')->where('company_id',1)->where('code',$code)->value('id');

        $accBank = $acc('1102');
        $accEqui = $acc('3101');
        $accAR   = $acc('1201');
        $accRev  = $acc('4101');
        $accVAT  = $acc('2105');

        // 1) Capital injection: Dr Bank 10,000,000 / Cr Equity 10,000,000
        $this->createPostedMove(1, $bankJ->id, $d1, 'Capital injection', [
            ['account_id'=>$accBank, 'label'=>'Bank In', 'debit'=>10000000, 'credit'=>0],
            ['account_id'=>$accEqui, 'label'=>'Owner Equity', 'debit'=>0, 'credit'=>10000000],
        ]);

        // 2) Sales invoice: Dr AR 5,500,000 / Cr Revenue 5,000,000 / Cr VAT 500,000
        $this->createPostedMove(1, $saleJ->id, $d2, 'Invoice INV-001', [
            ['account_id'=>$accAR,  'label'=>'AR',     'debit'=>5500000, 'credit'=>0],
            ['account_id'=>$accRev, 'label'=>'Sales',  'debit'=>0,       'credit'=>5000000],
            ['account_id'=>$accVAT, 'label'=>'VAT',    'debit'=>0,       'credit'=>500000],
        ]);

        // 3) Customer receipt: Dr Bank 5,500,000 / Cr AR 5,500,000
        $this->createPostedMove(1, $bankJ->id, $d3, 'Receipt INV-001', [
            ['account_id'=>$accBank, 'label'=>'Payment', 'debit'=>5500000, 'credit'=>0],
            ['account_id'=>$accAR,   'label'=>'AR',      'debit'=>0,       'credit'=>5500000],
        ]);
    }

    /**
     * Buat move + lines lalu auto-generate number & set status posted
     */
    private function createPostedMove(int $companyId, int $journalId, string $date, ?string $ref, array $lines): void
    {
        DB::transaction(function () use ($companyId, $journalId, $date, $ref, $lines) {
            // create draft move
            /** @var \App\Models\Accounting\Move $move */
            $move = Move::create([
                'company_id' => $companyId,
                'journal_id' => $journalId,
                'date'       => $date,
                'ref'        => $ref,
                'status'     => 'draft',
            ]);

            $move->lines()->createMany($lines);

            // simple balance check
            $sumD = (float) $move->lines()->sum('debit');
            $sumC = (float) $move->lines()->sum('credit');
            if (round($sumD,2) !== round($sumC,2)) {
                throw new \RuntimeException('Seeder move not balanced.');
            }

            // generate number per journal+period
            $period = date('Y-m', strtotime($date));
            $seq = JournalSequence::lockForUpdate()->firstOrCreate(
                ['journal_id'=>$journalId,'period'=>$period],
                ['last_number'=>0]
            );
            $next = $seq->last_number + 1;
            $seq->update(['last_number'=>$next]);

            // read journal prefix/padding
            $j = AccountJournal::findOrFail($journalId);
            $prefix = $j->sequence_prefix ?? 'GEN/%Y/%m/';
            $prefix = str_replace(['%Y','%m'], [date('Y', strtotime($date)), date('m', strtotime($date))], $prefix);
            $padding = $j->sequence_padding ?? 6;
            $number  = $prefix . str_pad($next, $padding, '0', STR_PAD_LEFT);

            // set posted
            $move->update([
                'number'    => $number,
                'status'    => 'posted',
                'posted_at' => now(),
            ]);
        });
    }
}
