<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockLevel extends Model
{
    use HasFactory;

    protected $table = 'stock_levels';

    /**
     * Kolom yang bisa diisi melalui mass assignment.
     */
    protected $fillable = [
        'item_id',
        'location_id',   // ⬅️ TAMBAH kalau kolom ini ada di DB
        'warehouse_id',  // opsional: kalau kamu masih menyimpan langsung ke warehouse
        'qty',
    ];

    /**
     * Type casting.
     */
    protected $casts = [
        'qty' => 'decimal:6',
    ];

    /**
     * Default attributes.
     */
    protected $attributes = [
        'qty' => 0,
    ];

    /* --------------------------
     |  Relationships
     |--------------------------- */

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    // ⬅️ RELASI YANG DIBUTUHKAN FRONTEND/CONTROLLER
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    // Tetap boleh ada untuk kompatibilitas lama
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
