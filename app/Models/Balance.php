<?php

namespace App\Models;

use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class Balance extends Model
{
//    protected $table = '';

    protected $fillable = [
        'pf_id', 'cartoon_id', 'copyright_id', 'month','plan_money', 'plan_money_type', 'plan_money_status', 'infact_money', 'infact_money_type','infact_money_status', 'min_money'
    ];

    //筛选搜索方法
    public function balance_paginate($is_expoter = false)
    {
        $month = Request::get('month','');
        $copyright_id = Request::get('copyright_id','');
        $cartoon_id = Request::get('cartoon_id','');
        $where = [];
        if ($month) {
            $where[] = ['pfdatas.month', '=', $month];
        }
        if ($copyright_id) {
            $where[] = ['pfdatas.copyright_id', '=', $copyright_id];
        }
        if ($cartoon_id) {
            $where[] = ['pfdatas.cartoon_id', '=', $cartoon_id];
        }

//        $pfdatas = DB::table('pfdatas')
//            ->leftJoin('cartoons', 'pfdatas.cartoon_id', '=', 'cartoons.id')
//            ->leftJoin('copyrights', 'pfdatas.copyright_id', '=', 'copyrights.id')
//            ->where('pfdatas.infact_money_status', '=', 3)
//            ->orderBy('pfdatas.month', 'desc')
//            ->get();

        if (Admin::user()->inRoles(['版权方'])) {

            $user = DB::table('admin_users')
                ->leftJoin('copyrights','admin_users.id','=','copyrights.admin_user_id')
                ->select('copyrights.id')
                ->where('admin_users.id','=',Admin::user()->id)
                ->first();
            $where[] = ['pfdatas.copyright_id', '=', $user->id];
        }
        $pfdatas = DB::table('pfdatas')
            ->leftJoin('cartoons', 'pfdatas.cartoon_id', '=', 'cartoons.id')
            ->leftJoin('producers', 'cartoons.producer_id', '=', 'producers.id')
            ->leftJoin('copyrights', 'pfdatas.copyright_id', '=', 'copyrights.id')
            ->leftJoin('copyrightoutcomes', function ($join) {
                $join->on('copyrightoutcomes.month', '=', 'pfdatas.month')->on('copyrightoutcomes.cartoon_id', '=', 'pfdatas.cartoon_id')->where('copyrightoutcomes.type', '=', 1);
            })
            ->select('pfdatas.*','cartoons.cartoon_name', 'copyrights.copyright_rate','copyrights.company_name','pfdatas.producer_rate','copyrightoutcomes.status')
            ->where('pfdatas.infact_money_status', '=', 3)

            ->where($where)
            ->orderBy('pfdatas.month', 'desc')
            ->get();

//        dump($pfdatas);
        $final_data = [];
        $pfsortname = DB::table('pfs')->select('id','sortname')->get()->toArray();
        $pfsortnameArray = [];
        foreach ($pfsortname as $sortname) {
            $pfsortnameArray[$sortname->id] = $sortname->sortname;
        }

        foreach ($pfdatas as $pfdata) {
            //如果时间和作品是同一部，则合并，否则增加一个元素
            $key = $this->isSameCartoon($pfdata->cartoon_id, $pfdata->month, $final_data);
            if ($key !== false) {
                $final_data[$key]['money_infact'] += $pfdata->infact_money;
//                $final_data[$key]['copyright_money'] += $pfdata->copyright_money;
            } else {
                $final_data[] = [
                    'month' => $pfdata->month,
                    'cartoon_id' => $pfdata->cartoon_id,
                    'cartoon_name' => $pfdata->cartoon_name,
                    'company_name' => $pfdata->company_name,
                    'money_infact' => $pfdata->infact_money,
                    'copyright_money' => $pfdata->copyright_money,
                    'producer_rate' => $pfdata->producer_rate,
                    'copyright_rate' => $pfdata->copyright_rate,
//                    'copyright_rate' => $pfdata->copyright_rate,
                    'payment' => $pfdata->payment,
                    'status' => $pfdata->status,
                ];
            }
        }


        foreach ($final_data as $key => &$value) {
            $kfpjs = $value['money_infact'] * 0.3;
            $all_bqcb = $value['copyright_money'];
            $zzfbl = $value['producer_rate'];
            $bqgs_rate = $value['copyright_rate']; //版权公司授权比例
            $gf = $value['payment'];
            $value['bqfcsj'] =  round((($kfpjs - $all_bqcb - $gf - (($zzfbl/100)*($kfpjs - $all_bqcb)))*($bqgs_rate/100))/(1-($bqgs_rate/100)*($zzfbl/100)),2);
        }


        if($is_expoter && Request::get('_export_') == 'all') {

            return $final_data;
        }

        if($is_expoter) {
            $page = Request::get('page', 1);
            $new_rows = array_chunk($final_data,Request::get('per_page', 20));
            $new_rows = $new_rows[$page-1] ?? [];
            return $new_rows;
        }

        $total = count($final_data);
        $perPage = Request::get('per_page', 20);

        $page = Request::get('page', 1);
        $new_rows = array_chunk($final_data,$perPage);
        $new_rows = $new_rows[$page-1] ?? [];

        $movies = static::hydrate($new_rows);

        $paginator = new LengthAwarePaginator($movies, $total, $perPage);

        $paginator->setPath(url()->current());

        return $paginator;
    }

    public function forecast($is_expoter = false)
    {
        $type = Request::get('type');
        $month = Request::get('month','');
        $copyright_id = Request::get('copyright_id','');
        $cartoon_id = Request::get('cartoon_id','');
        $where = [];
        if ($month) {
            $where[] = ['pfdatas.month', '=', $month];
        }
        if ($copyright_id) {
            $where[] = ['pfdatas.copyright_id', '=', $copyright_id];
        }
        if ($cartoon_id) {
            $where[] = ['pfdatas.cartoon_id', '=', $cartoon_id];
        }

        $pfdatas = DB::table('pfdatas')
            ->leftJoin('cartoons', 'pfdatas.cartoon_id', '=', 'cartoons.id')
            ->leftJoin('copyrights', 'pfdatas.copyright_id', '=', 'copyrights.id')
            ->where($where)
            ->orderBy('pfdatas.month', 'desc')
            ->get();
//        $pfdatas = Db::select($sql);

        $final_data = [];
        $pfsortname = DB::table('pfs')->select('id','sortname')->get()->toArray();
        $pfsortnameArray = [];
        foreach ($pfsortname as $sortname) {
            $pfsortnameArray[$sortname->id] = $sortname->sortname;
        }

        foreach ($pfdatas as $pfdata) {
            //如果时间和作品是同一部，则合并，否则增加一个元素
            $key = $this->isSameCartoon($pfdata->cartoon_id, $pfdata->month, $final_data);
            if ($key !== false) {
                $final_data[$key][$pfsortnameArray[$pfdata->pf_id]] = $pfdata->infact_money;
                $final_data[$key]['money_count'] += $pfdata->infact_money;
                $final_data[$key]['min_money'] += $pfdata->min_money;
            } else {
                $final_data[] = [
                    'month' => $pfdata->month,
                    'cartoon_id' => $pfdata->cartoon_id,
                    'cartoon_name' => $pfdata->cartoon_name,
                    'company_name' => $pfdata->company_name,
                    'money_count' => $pfdata->infact_money,
                    'min_money' => $pfdata->min_money,
                    $pfsortnameArray[$pfdata->pf_id] => $pfdata->infact_money,
                ];
            }

        }

        if($is_expoter && Request::get('_export_') == 'all') {

            return $final_data;
        }

        if($is_expoter) {
            $page = Request::get('page', 1);
            $new_rows = array_chunk($final_data,Request::get('per_page', 20));
            $new_rows = $new_rows[$page-1] ?? [];
            return $new_rows;
        }

        $total = count($final_data);
        $perPage = Request::get('per_page', 20);

        $page = Request::get('page', 1);
        $new_rows = array_chunk($final_data,$perPage);
        $new_rows = $new_rows[$page-1] ?? [];

        $movies = static::hydrate($new_rows);

        $paginator = new LengthAwarePaginator($movies, $total, $perPage);

        $paginator->setPath(url()->current());

        return $paginator;
    }

    public function paginate()
    {
        //获取所有渠道方
        $sql = 'select a.*,c.cartoon_name,cr.company_name from pfdatas a LEFT JOIN cartoons c ON c.id = a.cartoon_id LEFT JOIN copyrights cr ON cr.id=a.copyright_id';
        $pfdatas = Db::select($sql);

//        return 1;

        $final_data = [];
        $pfsortname = DB::table('pfs')->select('id','sortname')->get()->toArray();
        $pfsortnameArray = [];
        foreach ($pfsortname as $sortname) {
            $pfsortnameArray[$sortname->id] = $sortname->sortname;
        }

        foreach ($pfdatas as $pfdata) {
            //如果时间和作品是同一部，则合并，否则增加一个元素
            $key = $this->isSameCartoon($pfdata->cartoon_id, $pfdata->month, $final_data);
            if ($key !== false) {
                $final_data[$key][$pfsortnameArray[$pfdata->pf_id]] = $pfdata->plan_money;
                $final_data[$key]['money_count'] += $pfdata->plan_money;
            } else {
                $final_data[] = [
                    'month' => $pfdata->month,
                    'cartoon_id' => $pfdata->cartoon_id,
                    'cartoon_name' => $pfdata->cartoon_name,
                    'company_name' => $pfdata->company_name,
                    'money_count' => $pfdata->plan_money,
                    'min_money' => $pfdata->min_money,
                    $pfsortnameArray[$pfdata->pf_id] => $pfdata->plan_money,
                ];
            }

        }
        dump($final_data);
        $total = DB::table('pfdatas')->count();
        $perPage = Request::get('per_page', 20);

        $movies = static::hydrate($final_data);

        $paginator = new LengthAwarePaginator($movies, $total, $perPage);

        $paginator->setPath(url()->current());

        return $paginator;
    }

    private function isSameCartoon($cartoon_id, $month, $data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if ($value['cartoon_id'] == $cartoon_id && $value['month'] == $month) {
                    return $key;
                }
            }
        }
        return false;
    }

    public static function with($relations)
    {
        return new static;
    }
}
