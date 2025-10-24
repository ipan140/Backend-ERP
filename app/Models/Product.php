<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'uom',
        'base_price',
        'active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'active' => 'boolean',
    ];

    // Relasi: Product bisa muncul di banyak quotation items
    public function quotationItems()
    {
        return $this->hasMany(QuotationItem::class);
    }
}
