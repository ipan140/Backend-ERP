<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'qty',
        'uom',
        'unit_price',
        'discount',
        'tax_rate',
        'line_total',
    ];

    // 🔗 Relasi
    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
