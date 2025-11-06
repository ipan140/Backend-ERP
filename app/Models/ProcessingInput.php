<?php

namespace App\Models\SCM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessingInput extends Model
{
    use HasFactory;
    // use SoftDeletes;

    protected $table = 'processing_inputs';

    protected $fillable = [
        'work_order_id',
        'product_id',   // nullable (boleh pakai lot saja)
        'lot_id',       // nullable
        'qty',
        'uom',
    ];

    protected $casts = [
        'work_order_id' => 'integer',
        'product_id'    => 'integer',
        'lot_id'        => 'integer',
        'qty'           => 'float',    // atau 'decimal:6' jika ingin presisi fixed
    ];

    public function workOrder()
    {
        return $this->belongsTo(ProcessingWorkOrder::class, 'work_order_id');
    }

    /* Opsional: mutator trimming */
    public function setUomAttribute($v)
    {
        $this->attributes['uom'] = $v ? trim($v) : null;
    }
}
