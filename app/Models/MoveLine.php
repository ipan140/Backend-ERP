<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoveLine extends Model
{
    protected $fillable = ['move_id','account_id','label','debit','credit','date_maturity'];
    protected $casts = ['date_maturity'=>'date'];

    public function move()    { return $this->belongsTo(Move::class); }
    public function account() { return $this->belongsTo(Account::class); }
}
