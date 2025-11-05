<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payslip extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payslips';

    protected $fillable = [
        'employee_id',
        'period_start',
        'period_end',
        'status',            // draft|submitted|approved|paid|cancelled
        'basic_salary',      // nilai gaji pokok (earning)
        'gross_earnings',    // total earning (termasuk basic_salary + line earning)
        'total_deductions',  // total deduction
        'net_pay',           // gross_earnings - total_deductions
        'notes',
        'approved_by',
        'approved_at',
        'posted_at',         // tanggal dibayar
    ];

    protected $casts = [
        'period_start'     => 'date',
        'period_end'       => 'date',
        'approved_at'      => 'datetime',
        'posted_at'        => 'datetime',
        'basic_salary'     => 'decimal:2',
        'gross_earnings'   => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay'          => 'decimal:2',
    ];

    /* =========================
     | Relationships
     ==========================*/
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function lines()
    {
        return $this->hasMany(PayslipLine::class)->orderBy('seq');
    }

    /* =========================
     | Helpers
     ==========================*/
    public function recalcTotals(bool $persist = false): self
    {
        // Gunakan relasi yang sudah diload, atau query jika belum
        $lines = $this->relationLoaded('lines')
            ? $this->lines
            : $this->lines()->get(['type','amount']);

        // Hitung total earning dan deduction
        $earnings = (float) $lines->where('type', 'earning')->sum('amount');
        $deducts  = (float) $lines->where('type', 'deduction')->sum('amount');
        $basic    = (float) ($this->basic_salary ?? 0);

        // ✅ Hitung total (pakai named arguments resmi PHP 8)
        $this->gross_earnings   = round(num: $basic + $earnings, precision: 2);
        $this->total_deductions = round(num: max(0, $deducts), precision: 2);
        $this->net_pay          = round(num: $this->gross_earnings - $this->total_deductions, precision: 2);

        // Jika $persist = true, langsung simpan ke database
        if ($persist) {
            $this->save();
        }

        return $this;
    }
}
