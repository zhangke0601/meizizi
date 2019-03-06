<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pf extends Model
{
    protected $table = 'pfs';

    protected $fillable = [
        'pfname', 'sortname' ,'full_name','remark'
    ];
}
