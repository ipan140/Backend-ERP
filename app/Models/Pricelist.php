<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pricelist extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',        // varchar(100)
        'currency',    // varchar(8)  (contoh: IDR, USD)
        'type',        // enum('sale','purchase')
        'description', // text|null
        'valid_from',  // date|null
        'valid_until', // date|null
        'active',      // tinyint(1)
    ];

    protected $casts = [
        'valid_from'  => 'date',
        'valid_until' => 'date',
        'active'      => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(PricelistItem::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function scopeActive($q)
    {
        return $q->where('active', true);
    }

    public function isCurrentlyValid(): bool
    {
        $today = now()->toDateString();
        return (
            (is_null($this->valid_from)  || $this->valid_from  <= $today) &&
            (is_null($this->valid_until) || $this->valid_until >= $today)
        );
    }
}
