<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cartoon extends Model
{
    protected $table = 'cartoons';

    protected $fillable = [
        'cartoon_name', 'copyright_id', 'copyright_rate', 'producer_rate', 'producer_id'
    ];

    public function copyright()
    {
        return $this->belongsTo(Copyright::class);
    }

    public function producer()
    {
        return $this->belongsTo(Producer::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
