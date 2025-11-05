<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = ['number', 'vendor_id', 'date', 'status'];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function qualityInspections()
    {
        return $this->hasMany(QualityInspection::class);
    }
}
