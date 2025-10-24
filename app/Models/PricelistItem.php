<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricelistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pricelist_id',
        'product_id',
        'price',      // DECIMAL(18,2) nullable -> null = pakai base_price
        'min_qty',    // DECIMAL(12,2) default 1
        'date_start', // DATE nullable
        'date_end',   // DATE nullable
        'active',     // tinyint(1)
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'min_qty'    => 'decimal:2',
        'date_start' => 'date',
        'date_end'   => 'date',
        'active'     => 'boolean',
    ];

    public function pricelist() { return $this->belongsTo(Pricelist::class); }
    public function product()   { return $this->belongsTo(Product::class); }

    public function scopeActive($q) { return $q->where('active', true); }
}
