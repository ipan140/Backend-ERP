<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $table = 'vendors';

    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'address',
    ];


    // === Relationships ===
    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'vendor_id');
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'vendor_id');
    }
}
