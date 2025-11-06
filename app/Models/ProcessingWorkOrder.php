<?php

namespace App\Models\SCM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessingWorkOrder extends Model
{
    use HasFactory;
    // use SoftDeletes;

    // Kalau nama tabelmu sudah "processing_work_orders", ini tidak wajib.
    protected $table = 'processing_work_orders';

    protected $fillable = [
        'name',
        'status',       // draft|in_progress|finished
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    /* =======================
     | Relasi
     ======================= */
    public function inputs()
    {
        return $this->hasMany(ProcessingInput::class, 'work_order_id');
    }

    public function outputs()
    {
        return $this->hasMany(ProcessingOutput::class, 'work_order_id');
    }

    /* =======================
     | Accessor agregat (opsional)
     ======================= */
    public function getTotalPlanOutputAttribute(): float
    {
        // pastikan kolom decimal di-cast ke float di ProcessingOutput
        return (float) $this->outputs->sum('qty_plan');
    }

    public function getTotalActualOutputAttribute(): float
    {
        return (float) $this->outputs->sum(function ($o) {
            return (float) ($o->qty_actual ?? 0);
        });
    }

    public function getIsFinishedAttribute(): bool
    {
        return $this->status === 'finished';
    }

    /* =======================
     | Scope filter (opsional)
     ======================= */
    public function scopeStatus($q, ?string $status)
    {
        return $status ? $q->where('status', $status) : $q;
    }

    public function scopeSearch($q, ?string $term)
    {
        if (!$term) return $q;
        $term = trim($term);
        return $q->where(function ($w) use ($term) {
            $w->where('name', 'like', "%{$term}%");
            if (is_numeric($term)) {
                $w->orWhere('id', (int) $term);
            }
        });
    }

    public function scopeCreatedFrom($q, ?string $date)
    {
        return $date ? $q->whereDate('created_at', '>=', $date) : $q;
    }

    public function scopeCreatedTo($q, ?string $date)
    {
        return $date ? $q->whereDate('created_at', '<=', $date) : $q;
    }

    /* =======================
     | Mutator sederhana (opsional)
     ======================= */
    public function setNameAttribute($v)
    {
        $this->attributes['name'] = $v ? trim($v) : null;
    }
}
