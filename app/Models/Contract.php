<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $table = 'contracts';

    public function cartoon()
    {
        return $this->belongsTo(Cartoon::class);
    }
}
