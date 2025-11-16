<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipment'; // ← PERBAIKAN PENTING

    protected $fillable = [
        'asset_id',
        'name',
        'serial',
        'category',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function plans()
    {
        return $this->hasMany(MaintenancePlan::class, 'equipment_id');
    }

    public function requests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'equipment_id');
    }
}
