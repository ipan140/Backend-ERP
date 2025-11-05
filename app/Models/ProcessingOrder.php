<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessingOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'warehouse_id',
        'product_id',        // ✅ tambahkan
        'finished_item_id',  // opsional kalau dipakai
        'qty',
        'date',
        'status',
        // 'notes',           // aktifkan hanya jika kolomnya ada
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'date'        => 'date',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    // ===== Relations =====
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // Bahan/produk yang diproses (input)
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Produk jadi (jika skema kamu memakai finished_item_id)
    public function finishedItem()
    {
        return $this->belongsTo(Product::class, 'finished_item_id');
    }

    public function items()
    {
        return $this->hasMany(ProcessingOrderItem::class);
    }
}
