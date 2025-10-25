<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'address',
        'payment_term_id',
        'credit_limit',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'is_active'    => 'boolean',
    ];

    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
    public function orders()
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }
}
