<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = ['sku', 'name', 'unit', 'price', 'description'];

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stockLevels()
    {
        return $this->hasMany(StockLevel::class);
    }

    public function stockMoves()
    {
        return $this->hasMany(StockMove::class);
    }

    public function shipmentItems()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function qualityItems()
    {
        return $this->hasMany(QualityInspectionItem::class);
    }

    public function replenishments()
    {
        return $this->hasMany(Replenishment::class);
    }
}
