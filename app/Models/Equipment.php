<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = ['asset_id','name','serial','category','active'];

    protected $casts = ['active' => 'boolean'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function plans()
    {
        return $this->hasMany(MaintenancePlan::class);
    }

    public function requests()
    {
        return $this->hasMany(MaintenanceRequest::class);
    }
}
