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

        return response()->json([
            'ok'             => true,
            'replenishments' => $p->items(),
            'pagination'     => [
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

        if (!array_key_exists('active', $data)) {
            $data['active'] = true;
        }

        // 🔥 CEK DUPLIKAT
        $exists = Replenishment::where('item_id', $data['item_id'])
            ->where('warehouse_id', $data['warehouse_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'ok'    => false,
                'error' => 'Aturan untuk item dan warehouse ini sudah ada!',
            ], 422);
        }

        $rule = Replenishment::create($data)->load(['item:id,name', 'warehouse:id,name']);

        return response()->json([
            'ok'            => true,
            'replenishment' => $rule,
        ], 201);
    }

    /**
     * Show detail of a replenishment rule.
     */
    public function show($id)
    {
        $rule = Replenishment::with(['item:id,name', 'warehouse:id,name'])->findOrFail($id);

        return response()->json([
            'ok'            => true,
            'replenishment' => $rule,
        ]);
    }

    /**
     * Update a replenishment rule.
     */
    public function update(Request $r, $id)
    {
        $rule = Replenishment::findOrFail($id);

        $data = $r->validate([
            'item_id'      => 'sometimes|integer|exists:items,id',
            'warehouse_id' => 'sometimes|integer|exists:warehouses,id',
            'min_qty'      => 'sometimes|numeric|min:0',
            'max_qty'      => 'sometimes|numeric|min:0',
            'reorder_qty'  => 'nullable|numeric|min:0',
            'active'       => 'boolean',
        ]);

        // 🔥 CEK DUPLIKAT SAAT UPDATE
        $newItem = $data['item_id'] ?? $rule->item_id;
        $newWarehouse = $data['warehouse_id'] ?? $rule->warehouse_id;

        $exists = Replenishment::where('item_id', $newItem)
            ->where('warehouse_id', $newWarehouse)
            ->where('id', '!=', $rule->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'ok'    => false,
                'error' => 'Aturan untuk item dan warehouse ini sudah ada!',
            ], 422);
        }

        // Validasi max >= min
        $min = $data['min_qty'] ?? $rule->min_qty;
        $max = $data['max_qty'] ?? $rule->max_qty;

        if ($max < $min) {
            return response()->json([
                'ok'    => false,
                'error' => 'max_qty must be greater than or equal to min_qty'
            ], 422);
        }

        $rule->update($data);

        return response()->json([
            'ok'            => true,
            'replenishment' => $rule->fresh()->load(['item:id,name', 'warehouse:id,name']),
        ]);
    }

    /**
     * Delete a replenishment rule.
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

            $level = StockLevel::where('item_id', $rule->item_id)
                ->where('warehouse_id', $rule->warehouse_id)
                ->first();

            $currentQty = $level->qty ?? 0;

            if ((float) $currentQty < (float) $rule->min_qty) {
                $results[] = [
                    'rule_id'      => $rule->id,
                    'item_id'      => $rule->item_id,
                    'item'         => $rule->item?->name,
                    'warehouse_id' => $rule->warehouse_id,
                    'warehouse'    => $rule->warehouse?->name,
                    'current_qty'  => (float) $currentQty,
                    'min_qty'      => (float) $rule->min_qty,
                    'max_qty'      => (float) $rule->max_qty,
                    'reorder_qty'  => (float) ($rule->reorder_qty ?? 0),
                    'suggest_qty'  => max(0, (float) $rule->max_qty - (float) $currentQty),
                ];
            }
        }

        return response()->json([
            'ok'      => true,
            'results' => $results,
        ]);
    }

    public function autoGenerate(Request $r)
    {
        return response()->json([
            'ok'      => true,
            'message' => 'Auto-generate not implemented yet',
        ]);
    }

    public function items()
    {
        return response()->json([
            'ok'    => true,
            'items' => Item::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function warehouses()
    {
        return response()->json([
            'ok'         => true,
            'warehouses' => Warehouse::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function formData()
    {
        return response()->json([
            'ok'         => true,
            'items'      => Item::select('id','name')->orderBy('name')->get(),
            'warehouses' => Warehouse::select('id','name')->orderBy('name')->get(),
        ]);
    }
}
