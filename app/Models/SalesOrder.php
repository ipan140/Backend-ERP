<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'customer_id',
        'number',
        'status',
        'currency',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'delivered_at',
    ];

    // 🔗 Relasi
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function items()
    {
        return $this->hasMany(SalesOrderItem::class, 'order_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'order_id');
    }
}
