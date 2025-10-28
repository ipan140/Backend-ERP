<?php

namespace App\Http\Controllers\Api\Quotation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationWorkflowController extends Controller
{
    /** POST /api/quotations/{id}/send (Draft -> Sent) */
    public function send(Request $r, int $id)
    {
        return DB::transaction(function () use ($r, $id) {
            $q = DB::table('quotations')->where('id', $id)->lockForUpdate()->first();
            if (!$q) return response()->json(['message' => 'Not found'], 404);

            if ($q->status === 'sent') {
                return $this->respondWithQuotation($id, 200, 'Already sent');
            }
            if ($q->status !== 'draft') {
                return response()->json(['message' => 'Hanya Draft yang bisa dikirim.'], 422);
            }

            $hasItems = DB::table('quotation_items')->where('quotation_id', $id)->exists();
            if (!$hasItems) {
                return response()->json(['message' => 'Quotation belum memiliki item.'], 422);
            }

            if (!empty($q->valid_until) && now()->toDateString() > $q->valid_until) {
                return response()->json(['message' => 'Quotation sudah melewati tanggal berlaku.'], 422);
            }

            DB::table('quotations')->where('id', $id)->update([
                'status'     => 'sent',
                'extra'      => $this->mergeExtra($q->extra, [
                    'sent_at'   => now()->toISOString(),
                    'esign_url' => url("/esign/quotations/{$id}"),
                ]),
                'updated_at' => now(),
            ]);

            $this->logStatus($id, 'draft', 'sent', 'Sent', $r->user()?->id);
            return $this->respondWithQuotation($id, 200, 'Sent');
        });
    }

    /** POST /api/quotations/{id}/approve (Sent -> Won) */
    public function approve(Request $r, int $id)
    {
        $reason = $r->input('reason') ?? $r->input('note');

        return DB::transaction(function () use ($id, $reason, $r) {
            $q = DB::table('quotations')->where('id', $id)->lockForUpdate()->first();
            if (!$q) return response()->json(['message' => 'Not found'], 404);

            if ($q->status === 'won') {
                return $this->respondWithQuotation($id, 200, 'Already won');
            }
            if ($q->status !== 'sent') {
                return response()->json(['message' => 'Hanya Sent yang bisa disetujui.'], 422);
            }

            DB::table('quotations')->where('id', $id)->update([
                'status'     => 'won',
                'extra'      => $this->mergeExtra($q->extra, ['approved_at' => now()->toISOString()]),
                'updated_at' => now(),
            ]);

            $this->logStatus($id, 'sent', 'won', $reason, $r->user()?->id);
            return $this->respondWithQuotation($id, 200, 'Approved');
        });
    }

    /** POST /api/quotations/{id}/confirm (Sent -> Confirmed) */
    public function confirm(Request $r, int $id)
    {
        $note = $r->input('note') ?? $r->input('reason');

        return DB::transaction(function () use ($id, $note, $r) {
            $q = DB::table('quotations')->where('id', $id)->lockForUpdate()->first();
            if (!$q) return response()->json(['message' => 'Not found'], 404);

            if ($q->status === 'confirmed') {
                return $this->respondWithQuotation($id, 200, 'Already confirmed');
            }
            if ($q->status !== 'sent') {
                return response()->json(['message' => 'Hanya Sent yang bisa di-confirm.'], 422);
            }

            DB::table('quotations')->where('id', $id)->update([
                'status'     => 'confirmed',
                'extra'      => $this->mergeExtra($q->extra, ['confirmed_at' => now()->toISOString()]),
                'updated_at' => now(),
            ]);

            $this->logStatus($id, 'sent', 'confirmed', $note, $r->user()?->id);
            return $this->respondWithQuotation($id, 200, 'Confirmed');
        });
    }

    /** POST /api/quotations/{id}/lose (Sent -> Lost) */
    public function lose(Request $r, int $id)
    {
        $reason = $r->input('reason') ?? $r->input('note');

        return DB::transaction(function () use ($id, $reason, $r) {
            $q = DB::table('quotations')->where('id', $id)->lockForUpdate()->first();
            if (!$q) return response()->json(['message' => 'Not found'], 404);

            if ($q->status === 'lost') {
                return $this->respondWithQuotation($id, 200, 'Already lost');
            }
            if ($q->status !== 'sent') {
                return response()->json(['message' => 'Hanya Sent yang bisa di-mark Lost.'], 422);
            }

            DB::table('quotations')->where('id', $id)->update([
                'status'     => 'lost',
                'extra'      => $this->mergeExtra($q->extra, ['lost_at' => now()->toISOString()]),
                'updated_at' => now(),
            ]);

            $this->logStatus($id, 'sent', 'lost', $reason, $r->user()?->id);
            return $this->respondWithQuotation($id, 200, 'Marked as lost');
        });
    }

    /** POST /api/quotations/{id}/expire (Draft/Sent -> Expired) */
    public function expire(Request $r, int $id)
    {
        $reason = $r->input('reason') ?? $r->input('note');

        return DB::transaction(function () use ($id, $reason, $r) {
            $q = DB::table('quotations')->where('id', $id)->lockForUpdate()->first();
            if (!$q) return response()->json(['message' => 'Not found'], 404);

            if ($q->status === 'expired') {
                return $this->respondWithQuotation($id, 200, 'Already expired');
            }
            if (!in_array($q->status, ['draft','sent'])) {
                return response()->json(['message' => 'Status tidak bisa expire.'], 422);
            }

            DB::table('quotations')->where('id', $id)->update([
                'status'     => 'expired',
                'extra'      => $this->mergeExtra($q->extra, ['expired_at' => now()->toISOString()]),
                'updated_at' => now(),
            ]);

            $this->logStatus($id, $q->status, 'expired', $reason ?: 'Expired manually', $r->user()?->id);
            return $this->respondWithQuotation($id, 200, 'Expired');
        });
    }

    /** ===== Helpers ===== */

    private function respondWithQuotation(int $id, int $status = 200, string $message = 'OK')
    {
        $q     = DB::table('quotations')->where('id', $id)->first();
        $items = DB::table('quotation_items')->where('quotation_id', $id)->get();
        $logs  = DB::table('quotation_status_logs')->where('quotation_id', $id)->orderBy('id')->get();

        return response()->json([
            'message'   => $message,
            'quotation' => $q,
            'items'     => $items,
            'logs'      => $logs,
        ], $status);
    }

    private function mergeExtra($current, array $add): string
    {
        $cur = [];
        if ($current) {
            $cur = is_array($current) ? $current : (json_decode($current, true) ?: []);
        }
        return json_encode(array_merge($cur, $add));
    }

    private function logStatus(int $qid, string $from, string $to, ?string $note, ?int $userId): void
    {
        DB::table('quotation_status_logs')->insert([
            'quotation_id' => $qid,
            'from_status'  => $from,
            'to_status'    => $to,
            'status'       => $to,
            'note'         => $note,
            'user_id'      => $userId,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
}
