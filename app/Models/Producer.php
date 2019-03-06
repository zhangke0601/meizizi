<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producer extends Model
{
    protected $table = 'producers';

    protected $fillable = [
        'producer_name', 'producer_rate'
    ];

    public function adminUser()
    {
        return $this->belongsTo(AdminUser::class);
    }
}
