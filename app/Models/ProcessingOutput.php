<?php

namespace App\Models\SCM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessingOutput extends Model
{
    use HasFactory;
    // use SoftDeletes;

    protected $table = 'processing_outputs';

    protected $fillable = [
        'work_order_id',
        'product_id',
        'qty_plan',
        'qty_actual',   // nullable
        'uom',
    ];

    protected $casts = [
        'work_order_id' => 'integer',
        'product_id'    => 'integer',
        'qty_plan'      => 'float',    // atau 'decimal:6' sesuai kebutuhan
        'qty_actual'    => 'float',    // atau 'decimal:6'
    ];

    public function workOrder()
    {
        return $this->belongsTo(ProcessingWorkOrder::class, 'work_order_id');
    }

    /* Opsional: helper */
    public function getVarianceAttribute(): float
    {
        $actual = (float) ($this->qty_actual ?? 0);
        return $actual - (float) $this->qty_plan;
    }

    public function setUomAttribute($v)
    {
        $this->attributes['uom'] = $v ? trim($v) : null;
    }
}
