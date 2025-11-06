<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderInput extends Model
{
    protected $fillable = ['work_order_id','product_id','lot_id','qty','uom'];
    public function workOrder(): BelongsTo { return $this->belongsTo(WorkOrder::class); }
}
