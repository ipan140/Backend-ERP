<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityInspection extends Model
{
    use HasFactory;

    protected $fillable = ['number', 'shipment_id', 'inspection_date', 'inspector_name', 'status'];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function items()
    {
        return $this->hasMany(QualityInspectionItem::class);
    }
}
