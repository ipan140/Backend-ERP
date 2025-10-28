<?php

namespace App\Http\Controllers\Api\Quotation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationLogController extends Controller
{
    /**
     * GET /api/quotation-logs?quotation_id=1
     */
    public function index(Request $r)
    {
        $qid = (int) $r->query('quotation_id', 0);

        $q = DB::table('quotation_status_logs as l')
            ->leftJoin('users as u', 'u.id', '=', 'l.user_id')
            ->when($qid > 0, fn($qq) => $qq->where('l.quotation_id', $qid))
            ->selectRaw('l.*, u.name as user_name')
            ->orderBy('l.id')
            ->get();

        return response()->json($q);
    }

    /**
     * POST /api/quotation-logs
     * body: { quotation_id, status, note? }
     *
     * Catatan: TIDAK insert kolom "status" (memang tidak ada di tabel).
     * Kita catat from_status (status saat ini) → to_status (status target).
     * Optionally: update juga status quotation-nya.
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'quotation_id' => 'required|integer|min:1',
            'status'       => 'required|string|in:draft,sent,approved,rejected,expired,confirmed',
            'note'         => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($r, $data) {
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

            // Simpan log TANPA kolom "status"
            $logId = DB::table('quotation_status_logs')->insertGetId([
                'quotation_id' => (int) $data['quotation_id'],
                'from_status'  => $from,
                'to_status'    => $to,
                'note'         => $data['note'] ?? null,
                'user_id'      => optional($r->user())->id,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // (opsional) update status quotation ke target
            DB::table('quotations')->where('id', $data['quotation_id'])->update([
                'status'     => $to,
                'updated_at' => now(),
            ]);

            // Balikkan header + logs terbaru
            $logs = DB::table('quotation_status_logs as l')
                ->leftJoin('users as u', 'u.id', '=', 'l.user_id')
                ->where('l.quotation_id', $data['quotation_id'])
                ->selectRaw('l.*, u.name as user_name')
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

    /**
     * DELETE /api/quotation-logs/{id}
     */
    public function destroy(int $id)
    {
        $deleted = DB::table('quotation_status_logs')->where('id', $id)->delete();
        return response()->json(['deleted' => (bool) $deleted]);
    }

    /**
     * Normalisasi status dari UI ke status sistem.
     * - approved/confirmed -> won
     * - rejected -> lost
     */
    private function mapUiStatusToSystem(string $ui): string
    {
        $ui = strtolower($ui);
        return match ($ui) {
            'approved', 'confirmed' => 'won',
            'rejected'               => 'lost',
            default                  => $ui,  // draft, sent, expired
        };
    }
}
