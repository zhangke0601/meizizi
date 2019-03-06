<?php

namespace App\Models;

use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class Min extends Model
{
    protected $table = 'mins';

    public function cartoon()
    {
        return $this->belongsTo(Cartoon::class);
    }

    public function pf()
    {
        return $this->belongsTo(Pf::class);
    }
}
