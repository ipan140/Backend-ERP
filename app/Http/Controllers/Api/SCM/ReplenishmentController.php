<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Replenishment, Item, Warehouse, StockLevel};

class ReplenishmentController extends Controller
{
    /**
     * List all replenishment rules.
     */
    public function index(Request $r)
    {
        $query = Replenishment::with([
            'item:id,name',
            'warehouse:id,name',
        ]);

        if ($r->filled('search')) {
            $s = trim((string) $r->search);
            if ($s !== '') {
                $query->where(function ($q) use ($s) {
                    $q->whereHas('item', fn ($qq) => $qq->where('name', 'like', "%{$s}%"))
                      ->orWhereHas('warehouse', fn ($qq) => $qq->where('name', 'like', "%{$s}%"));
                });
            }
        }

        $p = $query->orderByDesc('id')->paginate(20);

        // Kompatibel dengan beberapa versi frontend:
        // - this.rows = data?.data
        // - this.rows = data?.replenishments
        // - atau langsung baca meta.pagination
        return response()->json([
            'ok'              => true,
            'data'            => $p->items(),            // ← array saja
            'replenishments'  => $p->items(),            // ← alias, biar aman
            'pagination'      => [                       // ← meta ringkas
                'current_page' => $p->currentPage(),
                'per_page'     => $p->perPage(),
                'total'        => $p->total(),
                'last_page'    => $p->lastPage(),
            ],
        ]);
    }

    /**
     * Create a new replenishment rule.
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'item_id'      => 'required|integer|exists:items,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'min_qty'      => 'required|numeric|min:0',
            'max_qty'      => 'required|numeric|gte:min_qty',
            'reorder_qty'  => 'nullable|numeric|min:0',
            'active'       => 'boolean',
        ]);

        // default active = true jika tidak dikirim
        if (!array_key_exists('active', $data)) {
            $data['active'] = true;
        }

        $rule = Replenishment::create($data)->load(['item:id,name', 'warehouse:id,name']);

        return response()->json([
            'ok'             => true,
            'replenishment'  => $rule,
        ], 201);
    }

    /**
     * Show detail of a rule.
     */
    public function show($id)
    {
        $rule = Replenishment::with(['item:id,name', 'warehouse:id,name'])->findOrFail($id);

        return response()->json([
            'ok'             => true,
            'replenishment'  => $rule,
        ]);
    }

    /**
     * Update a rule.
     */
    public function update(Request $r, $id)
    {
        $rule = Replenishment::findOrFail($id);

        $data = $r->validate([
            'item_id'      => 'sometimes|integer|exists:items,id',
            'warehouse_id' => 'sometimes|integer|exists:warehouses,id',
            'min_qty'      => 'sometimes|numeric|min:0',
            'max_qty'      => 'sometimes|numeric|gte:min_qty',
            'reorder_qty'  => 'nullable|numeric|min:0',
            'active'       => 'boolean',
        ]);

        $rule->update($data);

        return response()->json([
            'ok'             => true,
            'replenishment'  => $rule->fresh()->load(['item:id,name', 'warehouse:id,name']),
        ]);
    }

    /**
     * Delete a rule.
     */
    public function destroy($id)
    {
        $rule = Replenishment::findOrFail($id);
        $rule->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Check which items need replenishment.
     */
    public function check(Request $r)
    {
        $rules = Replenishment::with(['item:id,name', 'warehouse:id,name'])
            ->where('active', true)
            ->get();

        $results = [];

        foreach ($rules as $rule) {
            // Ambil level stok per (item, warehouse). Jika belum ada, anggap 0.
            $level = StockLevel::firstOrNew(
                [
                    'item_id'      => $rule->item_id,
                    'warehouse_id' => $rule->warehouse_id,
                ],
                ['qty' => 0]
            );

            // Butuh isi ulang jika di bawah min
            if ((float) $level->qty < (float) $rule->min_qty) {
                $results[] = [
                    'rule_id'      => $rule->id,
                    'item_id'      => $rule->item_id,
                    'item'         => $rule->item?->name,
                    'warehouse_id' => $rule->warehouse_id,
                    'warehouse'    => $rule->warehouse?->name,
                    'current_qty'  => (float) $level->qty,
                    'min_qty'      => (float) $rule->min_qty,
                    'max_qty'      => (float) $rule->max_qty,
                    'reorder_qty'  => (float) ($rule->reorder_qty ?? 0),
                    'suggest_qty'  => (float) max(0, (float) $rule->max_qty - (float) $level->qty),
                ];
            }
        }

        return response()->json([
            'ok'      => true,
            'results' => $results,
        ]);
    }

    /**
     * Auto generate purchase/manufacturing order (optional).
     */
    public function autoGenerate(Request $r)
    {
        // Integrasi ke modul Purchase/Production bisa ditambahkan di sini
        return response()->json([
            'ok'      => true,
            'message' => 'Auto-generate not implemented yet',
        ]);
    }
}
