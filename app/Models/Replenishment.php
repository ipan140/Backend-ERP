<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Replenishment extends Model
{
    use HasFactory;

    protected $table = 'replenishments';

    /**
     * Kolom yang boleh diisi secara mass assignment.
     */
    protected $fillable = [
        'item_id',
        'warehouse_id',
        'min_qty',
        'max_qty',
        'reorder_qty',
        'active',
    ];

    /**
     * Default attribute values.
     */
    protected $attributes = [
        'min_qty'     => 0,
        'max_qty'     => 0,
        'reorder_qty' => 0,
        'active'      => true,
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'min_qty'     => 'decimal:6',
        'max_qty'     => 'decimal:6',
        'reorder_qty' => 'decimal:6',
        'active'      => 'boolean',
    ];

    /* ---------------------------------------------
     |  Relationships
     |--------------------------------------------- */

    /**
     * Relasi ke item.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    /**
     * Relasi ke warehouse.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Relasi ke stock level sesuai item & warehouse.
     */
    public function stockLevel()
    {
        return $this->hasOne(StockLevel::class, 'item_id', 'item_id')
            ->whereColumn('warehouse_id', 'replenishments.warehouse_id');
    }

    /* ---------------------------------------------
     |  Query Scopes
     |--------------------------------------------- */

    /**
     * Scope rule aktif.
     */
    public function scopeActive($q)
    {
        return $q->where('active', true);
    }

    /**
     * Scope rule untuk item & warehouse tertentu.
     */
    public function scopeFor($q, $itemId, $warehouseId)
    {
        return $q->where('item_id', $itemId)
                 ->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope rule yang membutuhkan pengisian ulang.
     * 
     * Logika yang benar:
     * - reorder diperlukan jika stok < min_qty
     */
    public function scopeNeedsReorder($q)
    {
        return $q->whereHas('stockLevel', function ($q) {
            $q->whereColumn('qty', '<', 'replenishments.min_qty');
        });
    }
}
