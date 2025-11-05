<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessingOrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['processing_order_id', 'item_id', 'qty'];

    public function processingOrder()
    {
        return $this->belongsTo(ProcessingOrder::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
