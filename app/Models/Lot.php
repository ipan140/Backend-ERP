<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lot extends Model
{
    use HasFactory;

    // Kolom yang dipakai controller & routes:
    protected $fillable = ['item_id','number','mfg_date','expiry_date'];

    protected $casts = [
        'mfg_date'    => 'date',
        'expiry_date' => 'date',
    ];

    /* ===================== RELATIONS ===================== */

    // Lot milik satu Item
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    // Relasi-relasi di bawah asumsi FK = lot_id
    public function stockMoves()
    {
        return $this->hasMany(StockMove::class, 'lot_id');
    }

    public function shipmentItems()
    {
        return $this->hasMany(ShipmentItem::class, 'lot_id');
    }

    public function qualityInspections()
    {
        return $this->hasMany(QualityInspection::class, 'lot_id');
    }

    public function woInputs()
    {
        return $this->hasMany(WorkOrderInput::class, 'lot_id');
    }

    public function woOutputs()
    {
        return $this->hasMany(WorkOrderOutput::class, 'lot_id');
    }

    /* ===================== SCOPES (opsional) ===================== */

    // Contoh helper untuk alert expiry
    public function scopeExpiringWithin($q, int $days = 30)
    {
        return $q->whereNotNull('expiry_date')
                 ->where('expiry_date', '<=', now()->addDays($days));
    }
}
