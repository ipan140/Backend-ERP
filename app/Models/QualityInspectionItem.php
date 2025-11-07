<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QualityInspectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quality_inspection_id','parameter','unit','value','min','max',
    ];

    protected $casts = [
        'value' => 'decimal:6',
        'min'   => 'decimal:6',
        'max'   => 'decimal:6',
    ];

    public function inspection()
    {
        return $this->belongsTo(QualityInspection::class, 'quality_inspection_id');
    }
}
