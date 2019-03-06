<?php

namespace App\Models;

use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class Bill extends Model
{
    protected $table = 'bills';

    public function copyright()
    {
        return $this->belongsTo(Copyright::class, 'r_id');
    }

    public function producer()
    {
        return $this->belongsTo(Producer::class, 'r_id');
    }

}
