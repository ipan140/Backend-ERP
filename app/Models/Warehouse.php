<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'location'];

    public function stockLevels()
    {
        return $this->hasMany(StockLevel::class);
    }

    public function stockMoves()
    {
        return $this->hasMany(StockMove::class);
    }

    public function processingOrders()
    {
        return $this->hasMany(ProcessingOrder::class);
    }

    public function replenishments()
    {
        return $this->hasMany(Replenishment::class);
    }
}
