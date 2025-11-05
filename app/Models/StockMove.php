<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMove extends Model
{
    protected $fillable = [
        'item_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'qty',
        'status',
        'reference_type',
        'reference_id',
        'moved_at',
    ];

    protected $casts = [
        'qty' => 'float',
        'moved_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
}
