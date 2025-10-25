<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'from_status',
        'to_status',
        'changed_by',
        'note',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    // 🔗 Relasi ke model lain
    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function user()
    {
        // diasumsikan kamu pakai Laravel default User
        return $this->belongsTo(User::class, 'changed_by');
    }
}
