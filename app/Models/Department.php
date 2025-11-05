<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'departments';

    protected $fillable = [
        'code',
        'name',
        'parent_id',
        'manager_employee_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /* =======================
     | Relationships
     |=======================*/
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function manager()
    {
        // asumsi model Employee ada di App\Models\Employee dengan kolom id
        return $this->belongsTo(Employee::class, 'manager_employee_id');
    }

    public function employees()
    {
        // opsional: jika Employee punya kolom department_id
        return $this->hasMany(Employee::class, 'department_id');
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

    public function scopeActive($q, $flag = true)
    {
        return $q->where('active', (bool)$flag);
    }

    /* =======================
     | Mutators (normalize)
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
