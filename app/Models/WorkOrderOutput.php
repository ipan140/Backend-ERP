<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderOutput extends Model
{
    protected $fillable = ['work_order_id','product_id','qty_plan','qty_actual','uom'];
    public function workOrder(): BelongsTo { return $this->belongsTo(WorkOrder::class); }
}
