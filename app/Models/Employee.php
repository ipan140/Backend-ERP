<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'emp_no',
        'first_name',
        'last_name',
        'full_name',
        'email',
        'phone',
        'department_id',
        'job_position_id',
        'manager_id',
        'hire_date',
        'employment_type', // permanent|contract|intern
        'status',          // active|inactive
        'gender',          // male|female|other|null
        'dob',
        'address',
        'city',
        'province',
        'country',
        'zip',
        'avatar_path',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'dob'       => 'date',
    ];

    /* =======================
     | Relationships
     |=======================*/
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function jobPosition()
    {
        return $this->belongsTo(JobPosition::class, 'job_position_id');
    }

    public function manager()
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    /* =======================
     | Scopes (helpers filter & cari)
     |=======================*/
    public function scopeSearch($q, ?string $s)
    {
        if (!$s) return $q;
        $s = trim($s);
        return $q->where(function ($w) use ($s) {
            $w->where('full_name', 'like', "%{$s}%")
              ->orWhere('emp_no', 'like', "%{$s}%")
              ->orWhere('email', 'like', "%{$s}%");
        });
    }

    public function scopeDepartment($q, $id)
    {
        return $id ? $q->where('department_id', $id) : $q;
    }

    public function scopeJobPosition($q, $id)
    {
        return $id ? $q->where('job_position_id', $id) : $q;
    }

    public function scopeManager($q, $id)
    {
        return $id ? $q->where('manager_id', $id) : $q;
    }

    public function scopeStatus($q, ?string $status)
    {
        return $status ? $q->where('status', $status) : $q;
    }

    public function scopeEmploymentType($q, ?string $type)
    {
        return $type ? $q->where('employment_type', $type) : $q;
    }

    public function scopeGender($q, ?string $gender)
    {
        return $gender ? $q->where('gender', $gender) : $q;
    }

    /* =======================
     | Mutators / Normalizers
     |=======================*/
    public function setEmpNoAttribute($val)
    {
        $this->attributes['emp_no'] = Str::upper(trim($val));
    }

    public function setEmailAttribute($val)
    {
        $this->attributes['email'] = Str::lower(trim($val));
    }

    public function setFirstNameAttribute($val)
    {
        $this->attributes['first_name'] = trim($val);
        $this->syncFullName();
    }

    public function setLastNameAttribute($val)
    {
        $this->attributes['last_name'] = $val !== null ? trim($val) : null;
        $this->syncFullName();
    }

    public function setFullNameAttribute($val)
    {
        // jika dikirim manual, pakai saja; kalau kosong, sinkronkan dari first+last
        $this->attributes['full_name'] = $val ? trim($val) : null;
    }

    protected function syncFullName(): void
    {
        // Jika full_name belum diset pada payload, auto-bangun dari first+last
        if (!array_key_exists('full_name', $this->attributes) || !$this->attributes['full_name']) {
            $first = $this->attributes['first_name'] ?? '';
            $last  = $this->attributes['last_name'] ?? '';
            $full  = trim($first . ' ' . $last);
            if ($full !== '') {
                $this->attributes['full_name'] = $full;
            }
        }
    }
}
