<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'code','name','email','phone','address','rating','active',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'active' => 'boolean',
    ];

    // Procurement
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
