<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Copyright extends Model
{
    protected $table = 'copyrights';

    protected $fillable = [
        'company_name', 'expire_time', 'copyright_rate'
    ];

    public function adminUser()
    {
        return $this->belongsTo(AdminUser::class);
    }
}
