<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Move extends Model
{
    protected $fillable = ['company_id','journal_id','number','date','ref','status','posted_at'];
    protected $casts = ['date'=>'date', 'posted_at'=>'datetime'];

    public function company() { return $this->belongsTo(Company::class); }
    public function journal() { return $this->belongsTo(AccountJournal::class, 'journal_id'); }
    public function lines()   { return $this->hasMany(MoveLine::class); }
}
