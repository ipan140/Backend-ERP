<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['warehouse_id','code','name','type','active'];

    protected $casts = ['active' => 'boolean'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // Inventory
    public function stockLevels()
    {
        return $this->hasMany(StockLevel::class);
    }

    public function movesFrom()
    {
        return $this->hasMany(StockMove::class, 'from_location_id');
    }

    public function movesTo()
    {
        return $this->hasMany(StockMove::class, 'to_location_id');
    }
}
