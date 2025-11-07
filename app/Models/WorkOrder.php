<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'asset_id',        // pastikan kolom ini ada di tabel work_orders
        // (opsional) 'equipment_id', jika kamu juga pakai per-alat
        'title',
        'notes',
        'scheduled_date',
        'completed_at',
        'technician',
        'status',          // draft|in_progress|done|cancelled (sesuaikan)
        'priority',        // low|medium|high|urgent (sesuaikan)
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'completed_at'   => 'datetime',
    ];

    /* ===================== RELATIONS ===================== */

    // ✅ Relasi yang diminta (menghilangkan error "undefined relationship [asset]")
    public function asset()
    {
        // table: assets (default Eloquent)
        // kolom FK di work_orders: asset_id
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    // (Opsional) jika kamu juga simpan equipment_id
    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function inputs()
    {
        return $this->hasMany(WorkOrderInput::class, 'work_order_id');
    }

    public function outputs()
    {
        return $this->hasMany(WorkOrderOutput::class, 'work_order_id');
    }

    /* ===================== SCOPES (opsional) ===================== */

    // contoh: filter status
    public function scopeStatus($q, ?string $status)
    {
        return $status ? $q->where('status', $status) : $q;
    }

    // contoh: filter jadwal
    public function scopeScheduledBetween($q, ?string $from, ?string $to)
    {
        if ($from) $q->where('scheduled_date', '>=', $from);
        if ($to)   $q->where('scheduled_date', '<=', $to);
        return $q;
    }
}
