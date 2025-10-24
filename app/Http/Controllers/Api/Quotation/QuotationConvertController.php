<?php

namespace App\Http\Controllers\Api\Quotation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class QuotationConvertController extends Controller
{
    use QuotationHelper;

    /**
     * POST /api/quotations/{id}/convert
     * Konversi quotation berstatus 'won' menjadi Sales Order (SO).
     * - Idempoten: jika sudah pernah dikonversi, kembalikan data SO yang sama (200).
     * - Transaksional: semua insert dibungkus DB::transaction().
     * - Aman: cek tabel target, status, dan item.
     */
    public function convert(int $id, Request $request)
    {
        // 0) Pastikan tabel SO ada (kalau belum dibuat, beri pesan yang jelas)
        if (!Schema::hasTable('sales_orders') || !Schema::hasTable('sales_order_items')) {
            return response()->json([
                'message' => 'Tabel sales_orders / sales_order_items belum ada. Buat dulu migrasinya.',
                'hint'    => 'Buat migrasi: sales_orders(id, number, quotation_id, customer_id, total, notes, created_at, updated_at) dan sales_order_items(id, sales_order_id, product_id, quantity, unit_price, total).'
            ], 501);
        }

        // 1) Validasi opsional input (mis. tanggal SO atau catatan tambahan)
        $data = $request->validate([
            'so_date'    => 'nullable|date',
            'notes'      => 'nullable|string',
        ]);

        // 2) Jalankan dalam transaksi + kunci baris quotation agar aman di concurrent request
        return DB::transaction(function () use ($id, $data) {

            // 2a) Ambil quotation + lock
            $q = DB::table('quotations')->where('id', $id)->lockForUpdate()->first();
            if (!$q) {
                return response()->json(['message' => 'Quotation not found'], 404);
            }

            // 2b) Hanya status 'won' yang bisa dikonversi
            if ($q->status !== 'won') {
                return response()->json(['message' => 'Hanya quotation berstatus WON yang dapat di-convert.'], 422);
            }

            // 2c) Idempoten: jika sudah pernah dibuat SO untuk quotation ini, kembalikan yg lama
            $existing = DB::table('sales_orders')->where('quotation_id', $q->id)->first();
            if ($existing) {
                $items = DB::table('sales_order_items')->where('sales_order_id', $existing->id)->get();
                return response()->json([
                    'message' => 'Sudah pernah dikonversi.',
                    'sales_order' => [
                        'id'           => $existing->id,
                        'number'       => $existing->number,
                        'quotation_id' => $existing->quotation_id,
                        'customer_id'  => $existing->customer_id,
                        'total'        => (float)$existing->total,
                        'items'        => $items,
                    ]
                ], 200);
            }

            // 2d) Ambil item quotation
            $qItems = DB::table('quotation_items')
                ->where('quotation_id', $q->id)
                ->get();

            if ($qItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => ['Quotation tidak memiliki item, tidak bisa dikonversi.']
                ]);
            }

            // 2e) Generate nomor SO (SO-YYYYMM-XXXX) berdasarkan running number bulanan
            $prefix = 'SO-' . now()->format('Ym') . '-';
            $last = DB::table('sales_orders')
                ->where('number', 'like', $prefix.'%')
                ->orderByDesc('number')
                ->value('number');

            $nextSeq = 1;
            if ($last) {
                $lastSeq = (int)substr($last, -4); // ambil 4 digit terakhir
                $nextSeq = $lastSeq + 1;
            }
            $soNumber = $prefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);

            // 2f) Hitung ulang total SO dari item (supaya konsisten)
            $subtotal = 0;
            foreach ($qItems as $it) {
                $lineTotal = ((float)$it->quantity) * ((float)$it->unit_price);
                $subtotal += $lineTotal;
            }
            // Sesuaikan jika kamu punya diskon/pajak; di sini contoh sederhana:
            $discount = property_exists($q, 'discount_amount') ? (float)$q->discount_amount : 0.0;
            $tax      = property_exists($q, 'tax_amount') ? (float)$q->tax_amount : 0.0;
            $grand    = max(0, $subtotal - $discount + $tax);

            // 2g) Insert header SO
            $soId = DB::table('sales_orders')->insertGetId([
                'number'       => $soNumber,
                'quotation_id' => $q->id,
                'customer_id'  => $q->customer_id,
                'total'        => $grand,
                'notes'        => $data['notes'] ?? $q->notes,
                'so_date'      => $data['so_date'] ?? now()->toDateString(), // kalau ada kolomnya
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // 2h) Insert item SO
            $insertItems = [];
            foreach ($qItems as $it) {
                $lineTotal = ((float)$it->quantity) * ((float)$it->unit_price);
                $insertItems[] = [
                    'sales_order_id' => $soId,
                    'product_id'     => $it->product_id,
                    'quantity'       => $it->quantity,
                    'unit_price'     => $it->unit_price,
                    'total'          => $lineTotal,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }
            DB::table('sales_order_items')->insert($insertItems);

            // 2i) (Opsional) update status quotation → 'converted'
            DB::table('quotations')->where('id', $q->id)->update([
                'status'     => 'converted',
                'updated_at' => now(),
            ]);

            // 2j) Ambil items untuk response
            $soItems = DB::table('sales_order_items')->where('sales_order_id', $soId)->get();

            return response()->json([
                'message'     => 'Converted to Sales Order',
                'sales_order' => [
                    'id'           => $soId,
                    'number'       => $soNumber,
                    'quotation_id' => $q->id,
                    'customer_id'  => $q->customer_id,
                    'total'        => $grand,
                    'items'        => $soItems,
                ],
            ], 201);
        });
    }
}
