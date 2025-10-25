<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'product_id',
        'qty',
        'uom',
        'unit_price',
        'discount',
        'tax_rate',
        'line_total',
    ];

    // 🔗 Relasi
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
