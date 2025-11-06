<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    // Boleh dibiarkan lengkap; field yang tidak ada di DB tidak akan dikirim
    protected $fillable = ['company_id', 'code', 'name', 'type', 'parent_id', 'level', 'active', 'reconcile'];
    protected $casts = ['active' => 'boolean', 'reconcile' => 'boolean'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function moveLines()
    {
        return $this->hasMany(MoveLine::class);
    }
}
