<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayslipLine extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payslip_lines';

    protected $fillable = [
        'payslip_id',
        'seq',        // urutan tampilan
        'code',       // ex: ALW_MEAL, DED_BPJS, etc
        'name',       // label line
        'type',       // earning|deduction
        'qty',        // optional (jam/hari/unit)
        'rate',       // optional (tarif per unit)
        'amount',     // nominal final (jika qty & rate ada, controller bisa hitung)
        'notes',
    ];

    protected $casts = [
        'qty'    => 'decimal:4',
        'rate'   => 'decimal:2',
        'amount' => 'decimal:2',
        'seq'    => 'integer',
    ];

    public function payslip()
    {
        return $this->belongsTo(Payslip::class);
    }
}
