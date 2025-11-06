<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    /**
     * GET /api/scm/vendors
     * Query:
     *  - search: cari di name/email/phone
     *  - active: 1|0 (opsional)
     *  - per_page: default 15
     */
    public function index(Request $r)
    {
        $perPage = (int) $r->integer('per_page', 15);

        $q = DB::table('vendors')
            ->when($r->filled('search'), function ($qq) use ($r) {
                $s = trim((string) $r->get('search'));
                $qq->where(function ($w) use ($s) {
                    $w->where('name', 'like', "%{$s}%")
                      ->orWhere('email', 'like', "%{$s}%")
                      ->orWhere('phone', 'like', "%{$s}%");
                });
            })
            ->when($r->filled('active'), fn($qq) => $qq->where('is_active', (int) $r->get('active')))
            ->orderBy('name');

        $p = $q->paginate($perPage);

        return response()->json([
            'ok'   => true,
            'data' => $p->items(),
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page'     => $p->perPage(),
                'total'        => $p->total(),
            ],
        ]);
    }

    /**
     * POST /api/scm/vendors
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'name'              => ['required','string','max:190'],
            'email'             => ['nullable','email','max:190', Rule::unique('vendors','email')],
            'phone'             => ['nullable','string','max:50'],
            'address'           => ['nullable','string'],
            'payment_term_days' => ['nullable','integer','min:0'],
            'is_active'         => ['nullable','boolean'],
        ]);

        $id = DB::table('vendors')->insertGetId([
            'name'              => $data['name'],
            'email'             => $data['email'] ?? null,
            'phone'             => $data['phone'] ?? null,
            'address'           => $data['address'] ?? null,
            'payment_term_days' => $data['payment_term_days'] ?? 0,
            'is_active'         => array_key_exists('is_active', $data) ? (int) $data['is_active'] : 1,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return response()->json([
            'ok'      => true,
            'message' => 'Vendor created',
            'vendor'  => DB::table('vendors')->where('id', $id)->first(),
        ], 201);
    }

    /**
     * GET /api/scm/vendors/{id}
     */
    public function show(int $id)
    {
        $row = DB::table('vendors')->where('id', $id)->first();
        if (!$row) return response()->json(['ok' => false, 'message' => 'Vendor not found'], 404);

        return response()->json(['ok' => true, 'vendor' => $row]);
    }

    /**
     * PUT/PATCH /api/scm/vendors/{id}
     */
    public function update(Request $r, int $id)
    {
        $exists = DB::table('vendors')->where('id', $id)->exists();
        if (!$exists) return response()->json(['ok' => false, 'message' => 'Vendor not found'], 404);

        $data = $r->validate([
            'name'              => ['sometimes','string','max:190'],
            'email'             => ['sometimes','nullable','email','max:190', Rule::unique('vendors','email')->ignore($id)],
            'phone'             => ['sometimes','nullable','string','max:50'],
            'address'           => ['sometimes','nullable','string'],
            'payment_term_days' => ['sometimes','nullable','integer','min:0'],
            'is_active'         => ['sometimes','boolean'],
        ]);

        $payload = collect($data)->only([
            'name','email','phone','address','payment_term_days','is_active'
        ])->toArray();

        if ($payload) {
            $payload['updated_at'] = now();
            DB::table('vendors')->where('id', $id)->update($payload);
        }

        return $this->show($id);
    }

    /**
     * DELETE /api/scm/vendors/{id}
     */
    public function destroy(int $id)
    {
        $exists = DB::table('vendors')->where('id', $id)->exists();
        if (!$exists) return response()->json(['ok' => false, 'message' => 'Vendor not found'], 404);

        DB::table('vendors')->where('id', $id)->delete();

        return response()->json(['ok' => true, 'message' => "Vendor {$id} deleted"]);
    }

    /**
     * GET /api/scm/vendors/{id}/rating
     * Mengembalikan score sederhana + metrik ringkas.
     * Aman meskipun tabel lain belum ada (akan di-skip).
     */
    public function rating(int $id)
    {
        $vendor = DB::table('vendors')->where('id', $id)->first();
        if (!$vendor) return response()->json(['ok' => false, 'message' => 'Vendor not found'], 404);

        // default metrics
        $metrics = [
            'po_total'          => 0,
            'po_confirmed'      => 0,
            'po_received'       => 0,
            'on_time_shipments' => null,
            'shipments_total'   => null,
            'on_time_rate'      => null, // %
        ];

        // PO metrics (jika tabel purchases ada)
        if (Schema::hasTable('purchases')) {
            $poQ = DB::table('purchases')->where('vendor_id', $id);
            $metrics['po_total']     = (clone $poQ)->count();
            $metrics['po_confirmed'] = (clone $poQ)->where('status','confirmed')->count();
            $metrics['po_received']  = (clone $poQ)->where('status','received')->count();
        }

        // Shipment metrics (opsional)
        if (Schema::hasTable('shipments')) {
            // diasumsikan kolom: vendor_id, expected_date, delivered_at (nullable)
            $shQ = DB::table('shipments')->where('vendor_id', $id);
            $metrics['shipments_total'] = (clone $shQ)->count();

            if ($metrics['shipments_total'] > 0 && Schema::hasColumn('shipments','expected_date') && Schema::hasColumn('shipments','delivered_at')) {
                $onTime = (clone $shQ)
                    ->whereNotNull('delivered_at')
                    ->whereColumn('delivered_at', '<=', 'expected_date')
                    ->count();
                $metrics['on_time_shipments'] = $onTime;
                $metrics['on_time_rate']      = round($onTime / max(1, $metrics['shipments_total']) * 100, 2);
            }
        }

        // Skoring sederhana
        $score = 0;
        // base berdasarkan PO
        if ($metrics['po_total'] > 0) {
            $recvRate = $metrics['po_received'] / max(1, $metrics['po_total']);
            $score += 60 * $recvRate;
        }
        // bonus on-time
        if (!is_null($metrics['on_time_rate'])) {
            $score += min(40, $metrics['on_time_rate'] * 0.4); // 40 poin maksimum dari on-time
        }
        $score = round(min(100, $score), 2);

        return response()->json([
            'ok'        => true,
            'vendor_id' => $id,
            'score'     => $score,
            'metrics'   => $metrics,
        ]);
    }
}
