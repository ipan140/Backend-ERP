<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = ['equipment_id','type','note','status'];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
