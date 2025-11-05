<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class JobPosition extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'job_positions';

    protected $fillable = [
        'code',
        'name',
        'department_id',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /* =======================
     | Relationships
     |=======================*/
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // Opsional: jika model Employee punya kolom job_position_id
    public function employees()
    {
        return $this->hasMany(Employee::class, 'job_position_id');
    }

    /* =======================
     | Scopes
     |=======================*/
    public function scopeSearch($q, ?string $s)
    {
        if (!$s) return $q;
        $s = trim($s);
        return $q->where(function ($w) use ($s) {
            $w->where('name', 'like', "%{$s}%")
              ->orWhere('code', 'like', "%{$s}%");
        });
    }

    public function scopeDepartment($q, $departmentId)
    {
        return $departmentId ? $q->where('department_id', $departmentId) : $q;
    }

    public function scopeActive($q, $flag = true)
    {
        return $q->where('active', (bool) $flag);
    }

    /* =======================
     | Mutators / Normalizers
     |=======================*/
    public function setCodeAttribute($val)
    {
        $this->attributes['code'] = Str::upper(trim($val));
    }

    public function setNameAttribute($val)
    {
        $this->attributes['name'] = trim($val);
    }
}
