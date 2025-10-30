<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class SalaryStructure extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'salary_structures';

    protected $fillable = [
        'code',
        'name',
        'base_basic',   // gaji pokok default pada struktur (optional)
        'active',
        'description',
    ];

    protected $casts = [
        'active'     => 'boolean',
        'base_basic' => 'decimal:2',
    ];

    /* ===== Relationships ===== */
    public function rules()
    {
        return $this->belongsToMany(SalaryRule::class, 'salary_structure_rules')
                    ->withPivot(['seq'])
                    ->withTimestamps()
                    ->orderBy('salary_structure_rules.seq');
    }

    /* ===== Scopes ===== */
    public function scopeSearch($q, ?string $s)
    {
        if (!$s) return $q;
        $s = trim($s);
        return $q->where(function ($w) use ($s) {
            $w->where('name','like',"%{$s}%")
              ->orWhere('code','like',"%{$s}%");
        });
    }

    public function scopeActive($q, $flag = true)
    {
        return $q->where('active', (bool)$flag);
    }

    /* ===== Mutators ===== */
    public function setCodeAttribute($val) { $this->attributes['code'] = Str::upper(trim($val)); }
    public function setNameAttribute($val) { $this->attributes['name'] = trim($val); }
}
