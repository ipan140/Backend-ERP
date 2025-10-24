<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',          // Contoh: "Net 30 Days"
        'description',   // Keterangan tambahan
        'days',          // Jumlah hari jatuh tempo
        'active',        // Status aktif/tidak
    ];

    protected $casts = [
        'days'   => 'integer',
        'active' => 'boolean',
    ];

    /* ==========================
       🔁 RELATIONSHIPS
    ========================== */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    /* ==========================
       🧭 SCOPES
    ========================== */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
