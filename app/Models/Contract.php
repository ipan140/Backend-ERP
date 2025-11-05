<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'contract_no',
        'contract_type',      // permanent | contract | intern
        'start_date',
        'end_date',
        'base_salary',
        'currency',           // e.g. IDR
        'pay_frequency',      // monthly | weekly
        'structure_id',       // salary_structures.id
        'status',             // draft | active | ended | cancelled
        'note',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'base_salary' => 'decimal:2',
    ];

    /* ======== Relasi ======== */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function structure()
    {
        return $this->belongsTo(SalaryStructure::class, 'structure_id');
    }

    /* ======== Scope berguna ======== */
    public function scopeActive($q)
    {
        return $q->where('status','active')
                 ->where(function($sub){
                     $sub->whereNull('end_date')->orWhere('end_date','>=', now()->toDateString());
                 });
    }
}
