<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkOrderOutput extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id','item_id','lot_id','qty_plan','qty_actual','uom',
    ];

    protected $casts = [
        'qty_plan'   => 'decimal:6',
        'qty_actual' => 'decimal:6',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }
}
