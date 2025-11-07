<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\{Item, Lot, Location, Warehouse, StockMove, StockLevel};

class InventoryController extends Controller
{
    /**
     * Locations list (inventory master)
     * GET /api/scm/inventory
     */
    public function index(Request $r)
    {
        $perPage = (int) $r->get('per_page', 20);

        $q = Location::query()->with(['warehouse:id,code,name']);

        if ($r->filled('warehouse_id')) {
            $q->where('warehouse_id', $r->warehouse_id);
        }

        if ($search = trim((string) $r->get('search', ''))) {
            $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")
                   ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $data = $q->orderBy('warehouse_id')->orderBy('code')->paginate($perPage);

        return response()->json(['ok' => true, 'data' => $data]);
    }

    /**
     * POST /api/scm/inventory
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'code'         => [
                'required','string','max:50',
                // kalau ingin unik global: 'unique:locations,code'
                // unik per-gudang:
                Rule::unique('locations','code')->where(fn($q)=>$q->where('warehouse_id',$r->warehouse_id)),
            ],
            'name'         => 'required|string|max:100',
            'type'         => 'nullable|string|max:50', // bin/rack/staging
        ]);

        $loc = Location::create($data);

        return response()->json(['ok' => true, 'data' => $loc], 201);
    }

    /**
     * PUT/PATCH /api/scm/inventory/{id}
     */
    public function update(Request $r, $id)
    {
        $loc = Location::findOrFail($id);

        $data = $r->validate([
            'warehouse_id' => 'sometimes|required|integer|exists:warehouses,id',
            'code'         => [
                'sometimes','required','string','max:50',
                Rule::unique('locations','code')
                    ->where(fn($q)=>$q->where('warehouse_id',$r->get('warehouse_id',$loc->warehouse_id)))
                    ->ignore($loc->id),
            ],
            'name'         => 'sometimes|required|string|max:100',
            'type'         => 'nullable|string|max:50',
        ]);

        if (empty($data)) {
            return response()->json(['ok' => false, 'message' => 'No changes'], 422);
        }

        $loc->update($data);

        return response()->json(['ok' => true, 'data' => $loc]);
    }

    /**
     * DELETE /api/scm/inventory/{id}
     */
    public function destroy($id)
    {
        $loc = Location::findOrFail($id);
        $loc->delete();

        return response()->json(['ok' => true, 'message' => 'Location deleted']);
    }

    /* ============================================================
     * Custom endpoints (prefix /api/scm/inventory/...)
     * ============================================================
     */

    /**
     * GET /inventory/stocks
     * Ringkasan stok agregat per item+location
     */
    public function stocks(Request $r)
    {
        $perPage = (int) $r->get('per_page', 20);
        $search  = trim((string) $r->get('search', ''));

        $q = StockLevel::with([
            'item:id,sku,name,uom',
            'location:id,code,name,warehouse_id',
            'location.warehouse:id,code,name',
        ])->select(['id','item_id','location_id','qty']);

        if ($r->filled('item_id')) {
            $q->where('item_id', $r->item_id);
        }
        if ($r->filled('location_id')) {
            $q->where('location_id', $r->location_id);
        }
        if ($r->filled('warehouse_id')) {
            $wid = (int) $r->get('warehouse_id');
            $q->whereHas('location', fn($loc) => $loc->where('warehouse_id', $wid));
        }
        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->whereHas('item', fn($i) => $i
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%"))
                   ->orWhereHas('location', fn($l) => $l
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"));
            });
        }

        $data = $q->orderByDesc('id')->paginate($perPage);

        return response()->json(['ok' => true, 'data' => $data]);
    }

    /**
     * GET /inventory/lots
     */
    public function lots(Request $r)
    {
        $perPage = (int) $r->get('per_page', 20);

        $q = Lot::with(['item:id,sku,name'])
            ->select(['id','item_id','number','mfg_date','expiry_date','created_at']);

        if ($r->filled('item_id')) {
            $q->where('item_id', $r->item_id);
        }
        if ($r->filled('number')) {
            $q->where('number', 'like', '%'.$r->number.'%');
        }

        $data = $q->orderByDesc('id')->paginate($perPage);

        return response()->json(['ok' => true, 'data' => $data]);
    }

    /**
     * GET /inventory/expiry-alerts
     */
    public function expiryAlerts(Request $r)
    {
        $days = max(1, (int) $r->get('days', 30));

        $data = Lot::with('item:id,sku,name')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->orderBy('expiry_date')
            ->get(['id','item_id','number','mfg_date','expiry_date']);

        return response()->json([
            'ok'             => true,
            'threshold_days' => $days,
            'data'           => $data,
        ]);
    }

    /**
     * POST /inventory/lots
     */
    public function storeLot(Request $r)
    {
        $data = $r->validate([
            'item_id'     => 'required|integer|exists:items,id',
            'number'      => 'required|string|max:100|unique:lots,number',
            'mfg_date'    => 'nullable|date',
            'expiry_date' => 'nullable|date|after:mfg_date',
        ]);

        $lot = Lot::create($data);

        return response()->json(['ok' => true, 'data' => $lot], 201);
    }

    /**
     * POST /inventory/receipt
     */
    public function receipt(Request $r)
    {
        $data = $r->validate([
            'location_id'         => 'required|integer|exists:locations,id',
            'notes'               => 'nullable|string',
            'items'               => 'required|array|min:1',
            'items.*.item_id'     => 'required|integer|exists:items,id',
            'items.*.qty'         => 'required|numeric|min:0.0001',
            'items.*.uom'         => 'required|string|max:20',
            'items.*.lot_id'      => 'nullable|integer|exists:lots,id',
            'moved_at'            => 'nullable|date',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $row) {
                StockMove::create([
                    'item_id'          => $row['item_id'],
                    'from_location_id' => null,
                    'to_location_id'   => $data['location_id'],
                    'qty'              => $row['qty'],
                    'uom'              => $row['uom'],
                    'lot_id'           => $row['lot_id'] ?? null,
                    'type'             => 'in',
                    'ref'              => 'receipt',
                    'moved_at'         => $data['moved_at'] ?? now(),
                    'notes'            => $data['notes'] ?? null,
                ]);

                $level = StockLevel::firstOrCreate(
                    ['item_id' => $row['item_id'], 'location_id' => $data['location_id']],
                    ['qty' => 0]
                );
                $level->increment('qty', $row['qty']);
            }
        });

        return response()->json(['ok' => true, 'message' => 'Receipt posted']);
    }

    /**
     * POST /inventory/transfer
     */
    public function transfer(Request $r)
    {
        $data = $r->validate([
            'from_location_id'    => 'required|integer|exists:locations,id',
            'to_location_id'      => 'required|integer|exists:locations,id|different:from_location_id',
            'items'               => 'required|array|min:1',
            'items.*.item_id'     => 'required|integer|exists:items,id',
            'items.*.qty'         => 'required|numeric|min:0.0001',
            'items.*.uom'         => 'required|string|max:20',
            'items.*.lot_id'      => 'nullable|integer|exists:lots,id',
            'moved_at'            => 'nullable|date',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $row) {
                // (opsional) strict stok tidak boleh kurang dari 0
                $from = StockLevel::firstOrCreate(
                    ['item_id' => $row['item_id'], 'location_id' => $data['from_location_id']],
                    ['qty' => 0]
                );
                // if ($from->qty < $row['qty']) {
                //     throw new \RuntimeException("Insufficient stock for item {$row['item_id']} at location {$data['from_location_id']}");
                // }

                StockMove::create([
                    'item_id'          => $row['item_id'],
                    'from_location_id' => $data['from_location_id'],
                    'to_location_id'   => $data['to_location_id'],
                    'qty'              => $row['qty'],
                    'uom'              => $row['uom'],
                    'lot_id'           => $row['lot_id'] ?? null,
                    'type'             => 'transfer',
                    'ref'              => 'transfer',
                    'moved_at'         => $data['moved_at'] ?? now(),
                ]);

                $to = StockLevel::firstOrCreate(
                    ['item_id' => $row['item_id'], 'location_id' => $data['to_location_id']],
                    ['qty' => 0]
                );
                $from->decrement('qty', $row['qty']);
                $to->increment('qty', $row['qty']);
            }
        });

        return response()->json(['ok' => true, 'message' => 'Transfer posted']);
    }

    /**
     * POST /inventory/adjust
     */
    public function adjust(Request $r)
    {
        $data = $r->validate([
            'location_id'  => 'required|integer|exists:locations,id',
            'item_id'      => 'required|integer|exists:items,id',
            'diff_qty'     => 'required|numeric|not_in:0',
            'uom'          => 'required|string|max:20',
            'reason'       => 'nullable|string|max:100',
            'note'         => 'nullable|string',
            'moved_at'     => 'nullable|date',
        ]);

        DB::transaction(function () use ($data) {
            $level = StockLevel::firstOrCreate(
                ['item_id' => $data['item_id'], 'location_id' => $data['location_id']],
                ['qty' => 0]
            );

            // (opsional) strict tidak boleh minus:
            // if ($data['diff_qty'] < 0 && $level->qty < abs($data['diff_qty'])) {
            //     throw new \RuntimeException("Insufficient stock to adjust down for item {$data['item_id']} at location {$data['location_id']}");
            // }

            StockMove::create([
                'item_id'          => $data['item_id'],
                'from_location_id' => $data['diff_qty'] < 0 ? $data['location_id'] : null,
                'to_location_id'   => $data['diff_qty'] > 0 ? $data['location_id'] : null,
                'qty'              => abs($data['diff_qty']),
                'uom'              => $data['uom'],
                'type'             => 'adjust',
                'ref'              => 'adjust',
                'moved_at'         => $data['moved_at'] ?? now(),
                'notes'            => $data['note'] ?? ($data['reason'] ?? null),
            ]);

            if ($data['diff_qty'] > 0) {
                $level->increment('qty', $data['diff_qty']);
            } else {
                $level->decrement('qty', abs($data['diff_qty']));
            }
        });

        return response()->json(['ok' => true, 'message' => 'Adjustment posted']);
    }
}
