<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    use HasFactory;

    // protected $table = 'quotation_items';

    protected $fillable = [
        'quotation_id',
        'product_id',
        'qty',
        'uom',
        'unit_price',
        'discount',   // nominal
        'tax_rate',   // %
        'line_total', // base - discount + tax
    ];

    protected $casts = [
        'qty'        => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount'   => 'decimal:2',
        'tax_rate'   => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
