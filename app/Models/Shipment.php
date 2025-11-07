<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'number','direction','warehouse_id','partner_id','partner_type',
        'status','scheduled_date','carrier','route', // kalau kolom ini ada
    ];

    protected $casts = [
        'scheduled_date' => 'date',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    // ✅ Tambahkan relasi vendor dan customer berbasis partner_type
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'partner_id')
                    ->where('partner_type', 'vendor');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'partner_id')
                    ->where('partner_type', 'customer');
    }

    // (opsional) helper untuk ambil partner generik
    public function getPartnerNameAttribute(): ?string
    {
        if ($this->partner_type === 'vendor') {
            return $this->relationLoaded('vendor')
                ? optional($this->vendor)->name
                : optional($this->vendor()->first())->name;
        }
        if ($this->partner_type === 'customer') {
            return $this->relationLoaded('customer')
                ? optional($this->customer)->name
                : optional($this->customer()->first())->name;
        }
        return null;
    }
}
