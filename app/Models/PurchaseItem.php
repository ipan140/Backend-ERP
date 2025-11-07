<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = ['purchase_id','item_id','qty','uom','price','subtotal'];

    protected $casts = [
        'qty'      => 'decimal:6',
        'price'    => 'decimal:6',
        'subtotal' => 'decimal:6',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
