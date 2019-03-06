<?php

namespace App\Models;

use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * @property mixed month
 * @property int copyright_id
 */
class PartnerDetail extends Model
{
    protected $table = 'pfdatas';

    public $type = 1;

    protected $fillable = [
        'pf_id', 'cartoon_id', 'copyright_id', 'month','plan_money', 'plan_money_type', 'plan_money_status', 'infact_money', 'infact_money_type','infact_money_status', 'min_money'
    ];

    //关联渠道方
    public function pf()
    {
        return $this->belongsTo(Pf::class);
    }

    //关联漫画
    public function cartoon()
    {
        return $this->belongsTo(Cartoon::class);
    }

    //关联版权方
    public function copyright()
    {
        return $this->belongsTo(Copyright::class);
    }

    /**
     * @return LengthAwarePaginator
     */
    public function ajax_paginate($is_expoter = false)
    {
        $where = [];
        $whereOr = [];
        $whereOr1 = [];
        $whereOr2 = [];
        if (Admin::user()->isRole('版权方')) {
            $copyright = Copyright::query()->where('admin_user_id','=', Admin::user()->id)->pluck('id')->toArray();
            $whereOr1[] = ['pfdatas.copyright_id', '=', $copyright[0]];
        }

        if (Admin::user()->isRole('制作方')) {
            $producer = Producer::query()->where('admin_user_id','=', Admin::user()->id)->pluck('id')->toArray();
//            dump($producer);die;
            $whereOr2[] = ['pfdatas.producer_id', '=', $producer[0]];
        }

        $month = Request::get('month','');
        $month2 = Request::get('month2','');
//        $copyright_id = Request::get('copyright_id','');
        $cartoon_id = Request::get('cartoon_id','');
        $together = Request::get('together', 1);

        if ($month) {
            $where[] = ['pfdatas.month', '>=', $month];
        }
        if ($month2) {
            $where[] = ['pfdatas.month', '<=', $month2];
        }
//        if ($copyright_id) {
//            $where[] = ['pfdatas.copyright_id', '=', 1];
//        }
        if ($cartoon_id) {
            $where[] = ['pfdatas.cartoon_id', '=', $cartoon_id];
        }

//        $where[] = ['pfdatas.infact_money_status', '=', 3];
        $where[] = ['pfdatas.rate_sure', '=', 1];
        $pfdatas = DB::table('pfdatas')
            ->leftJoin('cartoons', 'pfdatas.cartoon_id', '=', 'cartoons.id')
            ->leftJoin('copyrights', 'pfdatas.copyright_id', '=', 'copyrights.id')
            ->leftJoin('producers', 'pfdatas.producer_id', '=', 'producers.id')
            ->leftJoin('pfs', 'pfdatas.pf_id', '=', 'pfs.id')
            ->select('pfdatas.*','copyrights.company_name','producers.producer_name','cartoons.cartoon_name','cartoons.crate2','cartoons.mode','cartoons.crate4','pfs.type','cartoons.crate5')
            ->where($where)
            ->where(function ($query) use ($whereOr1, $whereOr2) {
                $query->where($whereOr1)
                      ->orWhere($whereOr2);
            })
            ->orderBy('pfdatas.month', 'desc')
            ->orderBy('pfs.sort', 'asc')
            ->orderBy('pfdatas.infact_money', 'desc')
            ->get();
//        dump($pfdatas);

        if (!empty($pfdatas)) {
            foreach ($pfdatas as $key => $data) {
                $rate2 = $data->rate2 > 0 ? $data->rate2 : $data->crate2;
                if ($data->mode == 2) {
                    $rate2 = 100;
                }
                if ($data->infact_money_status != 3) {
                    $pfdatas[$key]->infact_money = 0;
                    continue;
                }
                if (Admin::user()->isRole('制作方')) {
                    $rate2 = $data->rate5 > 0 ? $data->rate5 : $data->crate5;
                }
                $pfdatas[$key]->infact_money = round($pfdatas[$key]->infact_money * $rate2 / 100 , 2);
                $pfdatas[$key]->infact_money = $pfdatas[$key]->infact_money >= 50 ? $pfdatas[$key]->infact_money : 0;
            }
        }

        $final_data = [];
        $pfsortname = DB::table('pfs')->select('id','sortname','type')->get()->toArray();
        $pfsortnameArray = [];
        $pfType = [];
        foreach ($pfsortname as $sortname) {
            $pfsortnameArray[$sortname->id] = $sortname->sortname;
            $pfType[$sortname->id] = $sortname->type;
        }

        foreach ($pfdatas as $pfdata) {
            //如果时间和作品是同一部，则合并，否则增加一个元素
            $key = $this->isSameCartoon($pfdata->cartoon_id, $pfdata->month, $final_data);
            if ($key !== false) {
                $final_data[$key][$pfsortnameArray[$pfdata->pf_id]] = [
                    'money'=> $pfdata->type == 3 ? $pfdata->min_money :$pfdata->infact_money,
                    'type'=> $pfdata->infact_money_type,
                    'id' => $pfdata->id,
                    'is_line' => $pfdata->is_line,
                    'is_nopay' => $pfdata->is_nopay,
                ];
                $final_data[$key]['money_count'] += $pfdata->infact_money;
                $final_data[$key]['min_money'] += ($pfdata->rate4 > 0 ? $pfdata->rate4 : $pfdata->crate4) * $pfdata->min_money /100;
            } else {
                $final_data[] = [
                    'month' => $pfdata->month,
                    'cartoon_id' => $pfdata->cartoon_id,
                    'cartoon_name' => $pfdata->cartoon_name,
                    'company_name' => $pfdata->company_name,
                    'producer_name' => $pfdata->producer_name,
                    'money_count' => $pfdata->infact_money,
                    'min_money' => ($pfdata->rate4 > 0 ? $pfdata->rate4 : $pfdata->crate4) * $pfdata->min_money /100,
                    'copyright_money' => $pfdata->copyright_money,
                    'payment' => $pfdata->payment,
                    'pay_other' => $pfdata->pay_other,
                    'rate2' => $pfdata->rate2 > 0 ? $pfdata->rate2 : $pfdata->crate2,
                    $pfsortnameArray[$pfdata->pf_id] => [
                        'money'=> $pfdata->type == 3 ? $pfdata->min_money :$pfdata->infact_money,
                        'type'=>$pfdata->infact_money_type,
                        'id' => $pfdata->id,
                        'is_line' => $pfdata->is_line,
                        'is_nopay' => $pfdata->is_nopay,
                    ],
                ];
            }

        }

        if (!empty($final_data)) {
            foreach ($final_data as $key => $final_datum) {
                $biaoji = false;
                foreach ($final_datum as $item) {
                    if (is_array($item) && $item['money'] > 0) {
                        $biaoji = true;
                    }
                }

                if (!$biaoji) {
                    unset($final_data[$key]);
                }
            }
        }



        /* 计算总计(全部数据总和)s */
        $dataTotal = [];
        $dataTotal['month'] = '总计';

        foreach ($final_data as $data) {
            $dataTotal['money_count'] = ($dataTotal['money_count'] ?? 0) + $data['money_count'];
            $dataTotal['min_money'] = ($dataTotal['min_money'] ?? 0) + $data['min_money'];
            $dataTotal['payment'] = ($dataTotal['payment'] ?? 0) + $data['payment'];
            $dataTotal['pay_other'] = ($dataTotal['pay_other'] ?? 0) + $data['pay_other'];
            $dataTotal['copyright_money'] = ($dataTotal['copyright_money'] ?? 0) + $data['copyright_money'];

            foreach ($pfsortnameArray as $pfid => $pfsortname) {
                $dataTotal[$pfsortname] = [
                    'money' => ($dataTotal[$pfsortname]['money'] ?? 0) + ($data[$pfsortname]['money'] ?? 0),
                    'type' => 1,
                    'id' => ($dataTotal[$pfsortname]['id'] ?? 0),
                    'is_line' => 2,
                    'is_nopay' => 2,
                ];
            }
        }
        /* 计算总计e */

        /* 合并同月份数据s */

        $together_data = [];
        if ($together == 2) {
           // dump($final_data);
            foreach ($final_data as $data) {
                $key = $this->isSameMonth($data['month'], $together_data);
                if ($key !== false) {
                    $together_data[$key]['cartoon_name'] = '全部作品';
                    $together_data[$key]['company_name'] = '--';
                    $together_data[$key]['money_count'] += $data['money_count'];
                    $together_data[$key]['min_money'] += $data['min_money'];
                    $together_data[$key]['payment'] += $data['payment'];
                    $together_data[$key]['pay_other'] += $data['pay_other'];
                    $together_data[$key]['copyright_money'] += $data['copyright_money'];
                    foreach ($pfsortnameArray as $pfid => $pfsortname) {
                        if (isset($data[$pfsortname])) {
                            $a = $data[$pfsortname]['id'];
                        } else {
                            $a = $together_data[$key][$pfsortname]['id'] ?? 0;
                        }
                        $together_data[$key][$pfsortname] = [
                            'money' => ($together_data[$key][$pfsortname]['money'] ?? 0) + ($data[$pfsortname]['money'] ?? 0),
                            'type' => 1,
                            'id' => $a,
                            'is_line' => 2,
                            'is_nopay' => 2,
                        ];
                    }
                } else {
                    $together_data[] = $data;
                }
            }
        } else {
            $together_data = $final_data;
        }
        /* 合并同月份数据e */

        if($is_expoter && Request::get('_export_') == 'all') {
            $together_data[] = $dataTotal;
            return $together_data;
        }

        $total = count($together_data);
        $perPage = Request::get('per_page', 20);

        $page = Request::get('page', 1);
        $new_rows = array_chunk($together_data,$perPage);
        $new_rows = $new_rows[$page-1] ?? [];

        $new_rows[] = $dataTotal;

        if($is_expoter){
            return $new_rows;
        }
        $movies = static::hydrate($new_rows);
//        dump($movies);

        $paginator = new LengthAwarePaginator($movies, $total, $perPage);


        $paginator->setPath(url()->current());

        return $paginator;
    }

    public function forecast($is_expoter = false)
    {
        $type = Request::get('type');
        $month = Request::get('month','');
        $month2 = Request::get('month2','');
        $copyright_id = Request::get('copyright_id','');
        $cartoon_id = Request::get('cartoon_id','');
        $together = Request::get('together', 1);
        $where = [];
        if ($month) {
            $where[] = ['pfdatas.month', '>=', $month];
        }
        if ($month2) {
            $where[] = ['pfdatas.month', '<=', $month2];
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
            ->leftJoin('pfs', 'pfdatas.pf_id', '=', 'pfs.id')
            ->select('pfdatas.*','copyrights.company_name','cartoons.cartoon_name')
            ->where($where)
            ->orderBy('pfdatas.month', 'desc')
            ->orderBy('pfs.sort', 'asc')
            ->orderBy('pfdatas.infact_money', 'desc')
            ->get();
//        $pfdatas = Db::select($sql);

        $final_data = [];
        $pfsortname = DB::table('pfs')->select('id','sortname','type')->get()->toArray();
        $pfsortnameArray = [];
        $pfType = [];
        foreach ($pfsortname as $sortname) {
            $pfsortnameArray[$sortname->id] = $sortname->sortname;
            $pfType[$sortname->id] = $sortname->type;
        }

        foreach ($pfdatas as $pfdata) {
            //如果时间和作品是同一部，则合并，否则增加一个元素
            $key = $this->isSameCartoon($pfdata->cartoon_id, $pfdata->month, $final_data);
            if ($key !== false) {
                $final_data[$key][$pfsortnameArray[$pfdata->pf_id]] = [
                    'money'=>$pfType[$pfdata->pf_id]==1 ? $pfdata->infact_money : $pfdata->min_money,
                    'type'=>$pfdata->infact_money_status,
                    'id' => $pfdata->id,
                    'is_line' => $pfdata->is_line,
                    'is_nopay' => $pfdata->is_nopay,
                ];
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
                    'copyright_money' => $pfdata->copyright_money,
                    'payment' => $pfdata->payment,
                    'pay_other' => $pfdata->pay_other,
                    $pfsortnameArray[$pfdata->pf_id] => [
                        'money'=>$pfType[$pfdata->pf_id]==1 ? $pfdata->infact_money : $pfdata->min_money,
                        'type'=>$pfdata->infact_money_status,
                        'id' => $pfdata->id,
                        'is_line' => $pfdata->is_line,
                        'is_nopay' => $pfdata->is_nopay,
                    ],
                ];
            }

        }

        /* 计算总计(全部数据总和)s */
        $dataTotal = [];
        $dataTotal['month'] = '总计';

        foreach ($final_data as $data) {
            $dataTotal['money_count'] = ($dataTotal['money_count'] ?? 0) + $data['money_count'];
            $dataTotal['min_money'] = ($dataTotal['min_money'] ?? 0) + $data['min_money'];
            $dataTotal['payment'] = ($dataTotal['payment'] ?? 0) + $data['payment'];
            $dataTotal['pay_other'] = ($dataTotal['pay_other'] ?? 0) + $data['pay_other'];
            $dataTotal['copyright_money'] = ($dataTotal['copyright_money'] ?? 0) + $data['copyright_money'];

            foreach ($pfsortnameArray as $pfid => $pfsortname) {
                $dataTotal[$pfsortname] = [
                    'money' => ($dataTotal[$pfsortname]['money'] ?? 0) + ($data[$pfsortname]['money'] ?? 0),
                    'type' => 1,
                    'id' => ($dataTotal[$pfsortname]['id'] ?? 0),
                    'is_line' => 2,
                    'is_nopay' => 2,
                ];
            }
        }
        /* 计算总计e */

        /* 合并同月份数据s */
        $together_data = [];
        if ($together == 2) {
            foreach ($final_data as $data) {
                $key = $this->isSameMonth($data['month'], $together_data);
                if ($key !== false) {
                    $together_data[$key]['cartoon_name'] = '全部作品';
                    $together_data[$key]['company_name'] = '--';
                    $together_data[$key]['money_count'] += $data['money_count'];
                    $together_data[$key]['min_money'] += $data['min_money'];
                    $together_data[$key]['copyright_money'] += $data['copyright_money'];
                    $together_data[$key]['payment'] += $data['payment'];
                    $together_data[$key]['pay_other'] += $data['pay_other'];
                    foreach ($pfsortnameArray as $pfid => $pfsortname) {
                        if (isset($data[$pfsortname])) {
                            $a = $data[$pfsortname]['id'];
                        } else {
                            $a = $together_data[$key][$pfsortname]['id'] ?? 0;
                        }
                        $together_data[$key][$pfsortname] = [
                            'money' => ($together_data[$key][$pfsortname]['money'] ?? 0) + ($data[$pfsortname]['money'] ?? 0),
                            'type' => 1,
                            'id' => $a,
                            'is_line' => 2,
                            'is_nopay' => 2,
                        ];
                    }
                } else {
                    $together_data[] = $data;
                }
            }
        } else {
            $together_data = $final_data;
        }
        /* 合并同月份数据e */

        if($is_expoter && Request::get('_export_') == 'all') {
            $together_data[] = $dataTotal;
            return $together_data;
        }

        $total = count($together_data);
//        $perPage = Request::get('per_page', 10);

        $perPage = Request::get('per_page', 20);

        $page = Request::get('page', 1);
        $new_rows = array_chunk($together_data,$perPage);
        $new_rows = $new_rows[$page-1] ?? [];
        $new_rows[] = $dataTotal;

        if($is_expoter){
            return $new_rows;
        }

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

    private function isSameMonth($month, $data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if ($value['month'] == $month) {
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
