<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    use HasFactory;

    // Pastikan nama tabelnya eksplisit
    protected $table = 'quotation_items';

    // Kolom sesuai tabel & yang dipakai frontend
    protected $fillable = [
        'quotation_id',
        'product_id',
        'description',
        'qty',
        'uom',
        'unit_price',
        'discount_pct', // persen diskon
        'line_total',
    ];

    /**
     * Catatan: cast 'decimal' di Laravel akan mengembalikan string.
     * Agar Vue bisa langsung hitung angka, kita pakai float di JSON.
     */
    protected $casts = [
        'qty'          => 'float',
        'unit_price'   => 'float',
        'discount_pct' => 'float',
        'line_total'   => 'float',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
