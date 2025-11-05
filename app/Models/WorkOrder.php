<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    use HasFactory;

    /** Status & Priority yang valid (ikuti enum di migration) */
    public const STATUS_OPEN         = 'open';
    public const STATUS_SCHEDULED    = 'scheduled';
    public const STATUS_IN_PROGRESS  = 'in_progress';
    public const STATUS_DONE         = 'done';
    public const STATUS_CANCELLED    = 'cancelled';

    public const PRIORITY_LOW    = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH   = 'high';

    protected $fillable = [
        'number',
        'asset_id',
        'title',
        'notes',
        'scheduled_date',
        'technician',
        'status',
        'priority',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'completed_at'   => 'datetime',
    ];

    /** Relasi */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /** Scopes kecil yang berguna */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeUpcoming($query)
    {
        return $query->whereNull('completed_at')
                     ->whereDate('scheduled_date', '>=', now()->startOfDay())
                     ->orderBy('scheduled_date');
    }

    /** Helper */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_DONE || !is_null($this->completed_at);
    }
}
