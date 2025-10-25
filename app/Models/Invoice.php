<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'number',
        'status',
        'currency',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'posted_at',
        'paid_at',
    ];

    // 🔗 Relasi
    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }
}
