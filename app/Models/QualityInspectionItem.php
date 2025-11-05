<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityInspectionItem extends Model
{
    use HasFactory;

    protected $fillable = ['quality_inspection_id', 'item_id', 'qty', 'result'];

    public function inspection()
    {
        return $this->belongsTo(QualityInspection::class, 'quality_inspection_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
