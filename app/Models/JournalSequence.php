<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalSequence extends Model
{
    protected $fillable = ['journal_id','period','last_number'];

    public function journal() { return $this->belongsTo(AccountJournal::class); }
}
