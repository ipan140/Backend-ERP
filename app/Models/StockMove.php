<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockMove extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id','from_location_id','to_location_id','lot_id',
        'qty','uom','state','ref',
    ];

    protected $casts = ['qty' => 'decimal:6'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function fromLocation()
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }
}
