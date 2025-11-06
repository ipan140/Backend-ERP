<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRequest extends Model
{
    protected $fillable = ['equipment_id','type','note','status'];
    public function equipment(): BelongsTo { return $this->belongsTo(Equipment::class); }
}
