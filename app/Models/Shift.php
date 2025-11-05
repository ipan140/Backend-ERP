<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'shifts';

    protected $fillable = [
        'code',
        'name',
        'time_start',      // "08:00:00"
        'time_end',        // "17:00:00"
        'break_minutes',   // 60
        'is_night',        // true jika melewati midnight
        'description',
        'active',
    ];

    protected $casts = [
        'is_night'      => 'boolean',
        'active'        => 'boolean',
        'break_minutes' => 'integer',
    ];

    /* ===== Scopes ===== */
    public function scopeSearch($q, ?string $s)
    {
        if (!$s) return $q;
        $s = trim($s);
        return $q->where(function ($w) use ($s) {
            $w->where('name', 'like', "%{$s}%")
              ->orWhere('code', 'like', "%{$s}%");
        });
    }

    public function scopeActive($q, $flag = true)
    {
        return $q->where('active', (bool)$flag);
    }

    /* ===== Mutators ===== */
    public function setCodeAttribute($val)  { $this->attributes['code'] = Str::upper(trim($val)); }
    public function setNameAttribute($val)  { $this->attributes['name'] = trim($val); }
}
