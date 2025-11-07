<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Item extends Model
{
    use HasFactory;

    /**
     * Mass-assignable fields
     */
    protected $fillable = [
        'sku',
        'name',
        'uom',
        'is_stockable',
        'std_cost',
    ];

    /**
     * Default values (kalau kolomnya null saat create)
     */
    protected $attributes = [
        'uom'          => 'pcs',
        'is_stockable' => true,
        'std_cost'     => 0,
    ];

    /**
     * Type casting
     */
    protected $casts = [
        'is_stockable' => 'boolean',
        'std_cost'     => 'decimal:6',
    ];

    /* --------------------------
     |  Accessors / Mutators
     |--------------------------- */

    /**
     * SKU selalu trim + uppercase
     */
    protected function sku(): Attribute
    {
        return Attribute::make(
            set: fn ($v) => $v === null ? null : strtoupper(trim($v))
        );
    }

    /**
     * Name di-trim
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn ($v) => $v === null ? null : trim($v)
        );
    }

    /**
     * UoM di-trim & lower-case (opsional)
     */
    protected function uom(): Attribute
    {
        return Attribute::make(
            set: fn ($v) => $v === null ? null : strtolower(trim($v))
        );
    }

    /* --------------------------
     |  Scopes
     |--------------------------- */

    public function scopeStockable($q)
    {
        return $q->where('is_stockable', true);
    }

    /* --------------------------
     |  Relationships
     |--------------------------- */

    public function purchaseItems()
    {
        return $this->hasMany('App\Models\PurchaseItem');
    }

    public function stockLevels()
    {
        return $this->hasMany('App\Models\StockLevel');
    }

    public function stockMoves()
    {
        return $this->hasMany('App\Models\StockMove');
    }

    public function shipmentItems()
    {
        return $this->hasMany('App\Models\ShipmentItem');
    }

    public function qualityInspections()
    {
        return $this->hasMany('App\Models\QualityInspection');
    }

    public function replenishments()
    {
        return $this->hasMany('App\Models\Replenishment');
    }

    public function woInputs()
    {
        return $this->hasMany('App\Models\WorkOrderInput');
    }

    public function woOutputs()
    {
        return $this->hasMany('App\Models\WorkOrderOutput');
    }

    // Jika ada tabel lots dengan kolom item_id, bisa diaktifkan:
    // public function lots()
    // {
    //     return $this->hasMany('App\Models\Lot');
    // }
}
