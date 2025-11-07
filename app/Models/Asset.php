<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = ['code','name','category','acquired_at','serial','active'];

    protected $casts = [
        'acquired_at' => 'date',
        'active'      => 'boolean',
    ];

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function equipments()
    {
        return $this->hasMany(Equipment::class);
    }
}
