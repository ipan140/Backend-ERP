<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublicHoliday extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'public_holidays';

    protected $fillable = [
        'name',
        'date',
        'is_national',
        'note',
    ];

    protected $casts = [
        'date'        => 'date',
        'is_national' => 'boolean',
    ];

    /* -----------------------
     | Scopes (helper filter)
     -----------------------*/
    public function scopeSearch($q, ?string $s)
    {
        if (!$s) return $q;
        $s = trim($s);
        return $q->where(function ($w) use ($s) {
            $w->where('name', 'like', "%{$s}%")
              ->orWhere('note', 'like', "%{$s}%");
        });
    }

    public function scopeYear($q, $year)
    {
        return $year ? $q->whereYear('date', (int)$year) : $q;
    }

    public function scopeMonth($q, $month)
    {
        return $month ? $q->whereMonth('date', (int)$month) : $q;
    }

    public function scopeBetween($q, ?string $from, ?string $to)
    {
        if ($from && $to) return $q->whereBetween('date', [$from, $to]);
        if ($from) return $q->whereDate('date', '>=', $from);
        if ($to)   return $q->whereDate('date', '<=', $to);
        return $q;
    }

    public function scopeNational($q, $flag)
    {
        return is_null($flag) ? $q : $q->where('is_national', (bool)$flag);
    }

    /* -----------------------
     | Mutators (normalize)
     -----------------------*/
    public function setNameAttribute($val)
    {
        $this->attributes['name'] = trim($val);
    }

    public function setNoteAttribute($val)
    {
        $this->attributes['note'] = $val !== null ? trim($val) : null;
    }
}
