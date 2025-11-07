<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Warehouse extends Model
{
    use HasFactory;

    /**
     * Kolom yang bisa diisi mass assignment
     */
    protected $fillable = [
        'code',
        'name',
        'address',
        'active',
    ];

    /**
     * Default value untuk kolom tertentu
     */
    protected $attributes = [
        'active' => true,
    ];

    /**
     * Type casting
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /* --------------------------
     |  Accessors / Mutators
     |--------------------------- */

    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn ($v) => $v === null ? null : strtoupper(trim($v))
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn ($v) => $v === null ? null : trim($v)
        );
    }

    protected function address(): Attribute
    {
        return Attribute::make(
            set: fn ($v) => $v === null ? null : trim($v)
        );
    }

    /* --------------------------
     |  Scopes
     |--------------------------- */

    public function scopeActive($q)
    {
        return $q->where('active', true);
    }

    /* --------------------------
     |  Relationships
     |--------------------------- */

    public function locations()
    {
        return $this->hasMany('App\Models\Location');
    }

    public function shipments()
    {
        return $this->hasMany('App\Models\Shipment');
    }

    public function replenishments()
    {
        return $this->hasMany('App\Models\Replenishment');
    }

    public function stockLevels()
    {
        return $this->hasMany('App\Models\StockLevel');
    }

    public function stockMoves()
    {
        return $this->hasMany('App\Models\StockMove');
    }
}
