<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    // kalau tabelmu "quotations", tidak perlu $table
    // protected $table = 'quotations';

    protected $fillable = [
        'number',
        'customer_id',
        'pricelist_id',
        'valid_until',
        'status',              // draft, sent, confirm, lose, expire, sale
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'notes',
        'extra',               // json ok
    ];

    protected $casts = [
        'valid_until'     => 'date',
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total'           => 'decimal:2',
        'extra'           => 'array',
    ];

    // 🔗 relasi
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function pricelist()
    {
        return $this->belongsTo(Pricelist::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function salesOrder()
    {
        return $this->hasOne(SalesOrder::class, 'quotation_id');
    }

    // enum ringan (opsional)
    public const ST_DRAFT   = 'draft';
    public const ST_SENT    = 'sent';
    public const ST_CONFIRM = 'confirm';
    public const ST_LOSE    = 'lose';
    public const ST_EXPIRE  = 'expire';
    public const ST_SALE    = 'sale';
}
