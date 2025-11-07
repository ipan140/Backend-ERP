<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Replenishment extends Model
{
    use HasFactory;

    /**
     * Nama tabel (opsional, tapi bagus untuk eksplisit).
     */
    protected $table = 'replenishments';

    /**
     * Kolom yang bisa diisi melalui mass assignment.
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
     * Nilai default.
     */
    protected $attributes = [
        'min_qty'     => 0,
        'max_qty'     => 0,
        'reorder_qty' => 0,
        'active'      => true,
    ];

    /**
     * Type casting.
     */
    protected $casts = [
        'min_qty'     => 'decimal:6',
        'max_qty'     => 'decimal:6',
        'reorder_qty' => 'decimal:6',
        'active'      => 'boolean',
    ];

    /* --------------------------
     |  Relationships
     |--------------------------- */

    /**
     * Barang yang diatur oleh aturan replenishment.
     */
    public function item()
    {
        return $this->belongsTo('App\Models\Item', 'item_id');
    }

    /**
     * Gudang tempat barang disimpan.
     */
    public function warehouse()
    {
        return $this->belongsTo('App\Models\Warehouse', 'warehouse_id');
    }

    /**
     * Level stok terkini (optional, join ke stock_levels).
     */
    public function stockLevel()
    {
        return $this->hasOne('App\Models\StockLevel', 'item_id', 'item_id')
            ->whereColumn('warehouse_id', 'replenishments.warehouse_id');
    }

    /* --------------------------
     |  Scopes
     |--------------------------- */

    /**
     * Ambil hanya rule yang aktif.
     */
    public function scopeActive($q)
    {
        return $q->where('active', true);
    }

    /**
     * Ambil rule berdasarkan item dan warehouse tertentu.
     */
    public function scopeFor($q, $itemId, $warehouseId)
    {
        return $q->where('item_id', $itemId)
                 ->where('warehouse_id', $warehouseId);
    }

    /**
     * Ambil rule yang butuh reorder (stok di bawah min).
     */
    public function scopeNeedsReorder($q)
    {
        return $q->whereColumn('min_qty', '>', 'reorder_qty');
    }
}
