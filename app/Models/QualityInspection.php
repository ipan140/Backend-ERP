<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QualityInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'lot_id','item_id','point','result','metrics','note','photo_url',
    ];

    protected $casts = [
        'metrics' => 'array',
    ];

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function items()
    {
        return $this->hasMany(QualityInspectionItem::class);
    }
}
