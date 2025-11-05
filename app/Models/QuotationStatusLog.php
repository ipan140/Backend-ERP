<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class QuotationStatusLog extends Model
{
    use HasFactory;

    // Jika nama tabel non-standar, tetap eksplisit
    protected $table = 'quotation_status_logs';

    /**
     * Kita TIDAK memakai created_at/updated_at bawaan.
     * Log ini punya kolom waktu sendiri: changed_at.
     */
    public $timestamps = false;

    protected $fillable = [
        'quotation_id',
        'from_status',
        'to_status',
        'changed_by',   // FK ke users.id
        'note',
        'changed_at',   // datetime
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    /**
     * Accessor praktis: $log->status
     * - utamakan to_status, fallback ke from_status atau kolom 'status' jika kebetulan ada.
     */
    protected $appends = ['status'];

    public function getStatusAttribute(): ?string
    {
        return $this->attributes['to_status']
            ?? ($this->attributes['from_status'] ?? ($this->attributes['status'] ?? null));
    }

    /* =========================
       Relationships
    ==========================*/
    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function user()
    {
        // relasi ke users menggunakan FK 'changed_by'
        return $this->belongsTo(User::class, 'changed_by');
    }

    /* =========================
       Query Scopes
    ==========================*/
    public function scopeForQuotation($q, int $quotationId)
    {
        return $q->where('quotation_id', $quotationId);
    }

    public function scopeOldestFirst($q)
    {
        return $q->orderBy('id', 'asc');
    }

    public function scopeLatestFirst($q)
    {
        return $q->orderBy('id', 'desc');
    }

    /* =========================
       Model Events
    ==========================*/
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            // Auto-isi changed_at kalau belum ada
            if (empty($model->changed_at)) {
                $model->changed_at = Carbon::now();
            }
        });
    }
}
