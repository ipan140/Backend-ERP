<?php

namespace App\Http\Controllers\Api\Quotation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuotationLogController extends Controller
{
    /** Utility: pilih nama kolom catatan yg tersedia di DB */
    private function noteColumn(): ?string
    {
        $table = 'quotation_status_logs';
        foreach (['note', 'reason', 'description', 'remark'] as $c) {
            if (Schema::hasColumn($table, $c)) return $c;
        }
        return null;
    }

    /**
     * GET /api/quotation-logs?quotation_id=1
     * Mengembalikan baris dengan alias user_name & note yg konsisten.
     */
    public function index(Request $r)
    {
        $qid = (int) $r->query('quotation_id', 0);
        $noteCol = $this->noteColumn();

        $q = DB::table('quotation_status_logs as l')
            ->leftJoin('users as u', 'u.id', '=', 'l.changed_by') // <- kolom relasi user yg benar
            ->when($qid > 0, fn($qq) => $qq->where('l.quotation_id', $qid))
            ->selectRaw('l.*, u.name as user_name' . ($noteCol ? ", l.`$noteCol` as note" : ''))
            ->orderBy('l.id')
            ->get();

        return response()->json($q);
    }

    /**
     * POST /api/quotation-logs
     * body: { quotation_id, status, note? }
     * Mencatat from_status -> to_status dan update status quotation.
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'quotation_id' => 'required|integer|min:1',
            'status'       => 'required|string|in:draft,sent,approved,rejected,expired,confirmed',
            'note'         => 'nullable|string|max:255',
        ]);

        $noteCol = $this->noteColumn();

        return DB::transaction(function () use ($r, $data, $noteCol) {
            // Lock quotation
            $q = DB::table('quotations')
                ->where('id', $data['quotation_id'])
                ->lockForUpdate()
                ->first();

            if (!$q) {
                return response()->json(['message' => 'Quotation not found'], 404);
            }

            $from = $q->status ?? 'draft';
            $to   = $this->mapUiStatusToSystem($data['status']); // ui -> system

            // payload insert log
            $insert = [
                'quotation_id' => (int) $data['quotation_id'],
                'from_status'  => $from,
                'to_status'    => $to,
                'changed_by'   => optional($r->user())->id,
                'changed_at'   => now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
            if ($noteCol) {
                $insert[$noteCol] = $data['note'] ?? null;
            }

            $logId = DB::table('quotation_status_logs')->insertGetId($insert);

            // update status quotation
            DB::table('quotations')->where('id', $data['quotation_id'])->update([
                'status'     => $to,
                'updated_at' => now(),
            ]);

            // balikan data terbaru (alias-kan note)
            $logs = DB::table('quotation_status_logs as l')
                ->leftJoin('users as u', 'u.id', '=', 'l.changed_by')
                ->where('l.quotation_id', $data['quotation_id'])
                ->selectRaw('l.*, u.name as user_name' . ($noteCol ? ", l.`$noteCol` as note" : ''))
                ->orderBy('l.id')
                ->get();

            return response()->json([
                'message'   => 'Log added',
                'log_id'    => $logId,
                'quotation' => DB::table('quotations')->where('id', $data['quotation_id'])->first(),
                'logs'      => $logs,
            ], 201);
        });
    }

    /** DELETE /api/quotation-logs/{id} */
    public function destroy(int $id)
    {
        $deleted = DB::table('quotation_status_logs')->where('id', $id)->delete();
        return response()->json(['deleted' => (bool) $deleted]);
    }

    /** Normalisasi status UI → status sistem */
    private function mapUiStatusToSystem(string $ui): string
    {
        $ui = strtolower($ui);
        return match ($ui) {
            'approved', 'confirmed' => 'won',
            'rejected'               => 'lost',
            default                  => $ui, // draft, sent, expired
        };
    }
}
