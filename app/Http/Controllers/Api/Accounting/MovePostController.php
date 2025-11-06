<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Move;
use App\Models\JournalSequence;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MovePostController extends Controller
{
    /**
     * POST /api/accounting/moves/{move}/post
     *
     * Atur nomor berdasarkan journal & periode (YYYY-MM),
     * pastikan balanced, dan update status ke "posted".
     */
    public function post(Move $move): JsonResponse
    {
        $payload = DB::transaction(function () use ($move) {
            /** @var \App\Models\Accounting\Move $m */
            $m = Move::with(['journal', 'company'])
                ->lockForUpdate()
                ->findOrFail($move->id);

            abort_if($m->status === 'posted', 422, 'Already posted.');
            abort_if(!$m->journal, 422, 'Move has no journal.');
            abort_if(!$m->company, 422, 'Move has no company.');

            // Lock date guard
            $lock = optional($m->company)->lock_date;
            if ($lock && $m->date->lte($lock)) {
                abort(422, "Move date is locked (<= {$lock->toDateString()}).");
            }

            // Validasi lines
            $linesCount = (int) $m->lines()->count();
            abort_if($linesCount < 2, 422, 'Move must have at least 2 lines.');

            $sumD = (float) $m->lines()->sum('debit');
            $sumC = (float) $m->lines()->sum('credit');
            abort_if($sumD <= 0 && $sumC <= 0, 422, 'Total amount must be greater than zero.');
            abort_if(round($sumD, 2) !== round($sumC, 2), 422, 'Debits and credits must be equal.');

            // Ambil nomor berurutan per jurnal & periode
            $journal = $m->journal;
            $period  = $m->date->format('Y-m');

            $seq = JournalSequence::lockForUpdate()
                ->firstOrCreate(
                    ['journal_id' => $journal->id, 'period' => $period],
                    ['last_number' => 0]
                );

            $next = $seq->last_number + 1;
            $seq->update(['last_number' => $next]);

            $prefix  = $journal->sequence_prefix ?? 'GEN/%Y/%m/';
            $prefix  = str_replace(['%Y', '%m'], [$m->date->format('Y'), $m->date->format('m')], $prefix);
            $padding = $journal->sequence_padding ?? 6;
            $number  = $prefix . str_pad($next, $padding, '0', STR_PAD_LEFT);

            // Posting
            $m->update([
                'number'    => $number,
                'status'    => 'posted',
                'posted_at' => now(),
            ]);

            return [
                'id'     => $m->id,
                'number' => $m->number,
                'status' => $m->status,
            ];
        });

        return response()->json($payload);
    }

    /**
     * POST /api/accounting/moves/{move}/unpost
     *
     * Kembalikan move ke "draft".
     * (Opsional: larang unpost bila sudah ada reconcile)
     */
    public function unpost(Move $move): JsonResponse
    {
        $payload = DB::transaction(function () use ($move) {
            /** @var \App\Models\Accounting\Move $m */
            $m = Move::with('company')
                ->lockForUpdate()
                ->findOrFail($move->id);

            abort_if($m->status !== 'posted', 422, 'Move not posted.');

            // Lock date guard
            $lock = optional($m->company)->lock_date;
            if ($lock && $m->date->lte($lock)) {
                abort(422, "Move date is locked (<= {$lock->toDateString()}).");
            }

            // (Opsional) Cegah unpost jika sudah direconcile (AR/AP)
            // if ($m->lines()->whereHas('debitReconcile')->orWhereHas('creditReconcile')->exists()) {
            //     abort(422, 'Move lines already reconciled.');
            // }

            // Unpost
            $m->update([
                'status'    => 'draft',
                'posted_at' => null,
                // Kebijakan nomor: umumnya dibiarkan untuk audit trail.
                // Jika ingin dikosongkan:
                // 'number' => null,
            ]);

            return [
                'id'     => $m->id,
                'status' => $m->status,
            ];
        });

        return response()->json($payload);
    }
}
