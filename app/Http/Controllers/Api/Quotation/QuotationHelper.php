<?php

namespace App\Http\Controllers\Api\Quotation;

use Illuminate\Support\Facades\DB;

trait QuotationHelper
{
    /**
     * Auto-number: QO-YYYYMM-XXXX
     */
    protected function nextNumber(): string
    {
        $prefix = 'QO-'.now()->format('Ym').'-';

        $last = DB::table('quotations')
            ->where('number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('number')
            ->value('number');

        $seq = 1;
        if ($last) {
            $seq = (int) substr($last, -4);
            $seq = max(0, $seq) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Insert 1 item baris (menerima key qty/quantity).
     */
    protected function insertItem(int $quotationId, array $row): void
    {
        $qty  = $row['qty'] ?? ($row['quantity'] ?? null);
        $uom  = $row['uom'] ?? 'pcs';
        $unit = $row['unit_price'] ?? null;
        $disc = $row['discount_pct'] ?? 0;

        $qty  = (float) $qty;
        $unit = (float) $unit;
        $disc = (float) $disc;

        if ($qty <= 0)            abort(422, 'Qty harus lebih dari 0.');
        if ($unit < 0)            abort(422, 'Unit price tidak boleh negatif.');
        if ($disc < 0 || $disc>50)abort(422, 'Diskon item harus 0–50%.');

        $line = ($qty * $unit) * (1 - $disc / 100);
        $line = round($line, 2);

        DB::table('quotation_items')->insert([
            'quotation_id' => $quotationId,
            'product_id'   => $row['product_id'] ?? null,
            'description'  => $row['description'] ?? null,
            'qty'          => $qty,
            'uom'          => $uom,
            'unit_price'   => $unit,
            'discount_pct' => $disc,
            'line_total'   => $line,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    /**
     * Hitung ulang subtotal / tax / total dari items.
     */
    protected function recalcTotals(int $quotationId, float $headerDiscount = 0.0): void
    {
        $subtotal = (float) (DB::table('quotation_items')
            ->where('quotation_id', $quotationId)
            ->sum('line_total') ?? 0);

        $headerDiscount = max(0.0, $headerDiscount);
        $dpp = max(0.0, $subtotal - $headerDiscount);

        $ppnRate = 0.11; // contoh PPN 11%
        $tax   = round($dpp * $ppnRate, 2);
        $total = round($dpp + $tax, 2);

        DB::table('quotations')->where('id', $quotationId)->update([
            'subtotal'        => round($subtotal, 2),
            'discount_amount' => round($headerDiscount, 2),
            'tax_amount'      => $tax,
            'total'           => $total,
            'updated_at'      => now(),
        ]);
    }

    /** Merge JSON field "extra" dengan aman */
    protected function mergeExtra($currentJson, array $append): string
    {
        $curr = [];
        if (!empty($currentJson)) {
            try { $curr = json_decode($currentJson, true) ?: []; } catch (\Throwable $e) {}
        }
        return json_encode(array_merge($curr, $append), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Simpan status log (COCOK dengan kolom tabel: note & user_id).
     */
    protected function logStatus(
        int $quotationId,
        ?string $from,
        string $to,
        ?string $note = null,
        ?int $userId = null
    ): void {
        DB::table('quotation_status_logs')->insert([
            'quotation_id' => $quotationId,
            'from_status'  => $from,
            'to_status'    => $to,
            'note'         => $note,        // <- gunakan 'note', bukan 'reason'
            'user_id'      => $userId,      // <- gunakan 'user_id', bukan 'changed_by'
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
}
