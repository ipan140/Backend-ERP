<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'location',
        'category',
        'purchase_date',
        'value',
        'status',
    ];

    /**
     * Relasi ke WorkOrder (1 asset bisa punya banyak work order).
     */
    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    /**
     * (Opsional) Jika kamu nanti ingin catat histori perawatan.
     */
    // public function maintenanceLogs()
    // {
    //     return $this->hasMany(Maintenance::class);
    // }
}
