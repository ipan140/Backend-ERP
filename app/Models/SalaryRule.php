<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class SalaryRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'salary_rules';

    protected $fillable = [
        'code',
        'name',
        'type',           // earning|deduction
        'amount_type',    // fixed|percent
        'fixed_amount',   // jika fixed
        'percent',        // jika percent
        'percent_base',   // basic|gross
        'active',
        'description',
    ];

    protected $casts = [
        'fixed_amount' => 'decimal:2',
        'percent'      => 'decimal:4',
        'active'       => 'boolean',
    ];

    /* ===== Relationships ===== */
    public function structures()
    {
        return $this->belongsToMany(SalaryStructure::class, 'salary_structure_rules')
                    ->withPivot(['seq'])
                    ->withTimestamps()
                    ->orderBy('salary_structure_rules.seq');
    }

    /* ===== Scopes ===== */
    public function scopeSearch($q, ?string $s)
    {
        if (!$s) return $q;
        $s = trim($s);
        return $q->where(function($w) use ($s){
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
