<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountJournal extends Model
{
    protected $fillable = [
        'company_id','code','name','type','sequence_prefix','sequence_padding','active'
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function moves()   { return $this->hasMany(Move::class, 'journal_id'); }
}
