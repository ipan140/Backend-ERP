<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'shift_id',
        'check_in',
        'check_out',
        'work_duration_minutes',
        'source',           // kiosk | mobile | manual
        'note',
    ];

    protected $casts = [
        'check_in'  => 'datetime',
        'check_out' => 'datetime',
        'work_duration_minutes' => 'integer',
    ];

    /** ========= Relasi ========= */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /** ========= Scopes berguna ========= */
    public function scopeBetweenDate($q, $from, $to)
    {
        if ($from) $q->whereDate('check_in', '>=', $from);
        if ($to)   $q->whereDate('check_in', '<=', $to);
        return $q;
    }

    public function scopeForEmployee($q, $employeeId)
    {
        return $employeeId ? $q->where('employee_id', $employeeId) : $q;
    }

    /** ========= Helper ========= */
    /**
     * Hitung ulang durasi kerja (menit) jika check_in & check_out ada.
     * Mengembalikan $this agar bisa chaining ->recomputeDuration()->save()
     */
    public function recomputeDuration(): self
    {
        if ($this->check_in instanceof Carbon && $this->check_out instanceof Carbon) {
            $this->work_duration_minutes = max(0, $this->check_out->diffInMinutes($this->check_in));
        }
        return $this;
    }
}
