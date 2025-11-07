<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaintenancePlan extends Model
{
    use HasFactory;

    protected $fillable = ['equipment_id','frequency','next_date','procedure'];

    protected $casts = ['next_date' => 'date'];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
