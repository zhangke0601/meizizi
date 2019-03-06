<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class Statistics extends Model
{
    public function statistics_paginate($is_expoter = false)
    {
        if(!(php_sapi_name() == 'cli') && \Request::route()->getName() == 'statistics.prefer') {
            return $this->prefer_paginate();
        }

        /* 查询-计算数据s */
        $where = [];
        if (!empty(Request::get('copyright_id',''))) {
            $where[] = ['pfdatas.copyright_id', '=', Request::get('copyright_id','')];
        }
        if (!empty(Request::get('producer_id',''))) {
            $where[] = ['pfdatas.producer_id', '=', Request::get('producer_id','')];
        }
        if (!empty(Request::get('month',''))) {
            $where[] = ['pfdatas.month', '>=', Request::get('month','')];
        }
        if (!empty(Request::get('month2',''))) {
            $where[] = ['pfdatas.month', '<=', Request::get('month2','')];
        }
        if (!empty(Request::get('cartoon_id',''))) {
            $where[] = ['pfdatas.cartoon_id', '=', Request::get('cartoon_id','')];
        }
//        if (!empty(Request::get('company',''))) {
//            $where1[] = ['copyrights.company_name', 'like', '%'.Request::get('company','').'%'];
//            $where2[] = ['producers.producer_name', 'like', '%'.Request::get('company','').'%'];
//        }
        $pfdatas = DB::table('pfdatas')
            ->leftJoin('cartoons', 'pfdatas.cartoon_id', '=', 'cartoons.id')
            ->leftJoin('partners', 'cartoons.partner_id', '=', 'partners.id')
            ->leftJoin('producers', 'cartoons.producer_id', '=', 'producers.id')
            ->leftJoin('pfs', 'pfs.id', '=', 'pfdatas.pf_id')
//            ->leftJoin('copyrights', 'pfdatas.copyright_id', '=', 'copyrights.id')
            ->leftJoin('copyrights', 'cartoons.copyright_id', '=', 'copyrights.id')
            ->leftJoin('copyrightoutcomes', function ($join) {
                $join->on('copyrightoutcomes.month', '=', 'pfdatas.month')->on('copyrightoutcomes.cartoon_id', '=', 'pfdatas.cartoon_id')->where('copyrightoutcomes.type','=',1);
            })
            ->leftJoin('copyrightoutcomes as cs', function ($join) {
                $join->on('cs.month', '=', 'pfdatas.month')->on('cs.cartoon_id', '=', 'pfdatas.cartoon_id')->where('cs.type','=',2);
            })
            ->select('pfdatas.*','cartoons.cartoon_name','copyrights.company_name', 'copyrights.expire_time','copyrights.expire_time2','copyrights.copyright_rate','producers.producer_name', 'copyrightoutcomes.status','cs.status as status2','partners.partner_name','cartoons.partner_rate','cartoons.crate1','cartoons.crate2','cartoons.crate3','cartoons.crate4','cartoons.crate5','cartoons.mode','cartoons.mode2', 'pfs.type')
            ->where($where)
//            ->where(function ($query) use($where1, $where2) {
//                $query->where($where1)
//                    ->orWhere($where2);
//            })
            ->orderBy('pfdatas.month', 'desc')
            ->get();

        $rows = [];
        foreach ($pfdatas as $pfdata) {
            $key = $this->isSameCartoon($pfdata->cartoon_name, $pfdata->month, $rows);

            if ($key !== false) {
                $rows[$key]['all_money_infact'] += (($pfdata->infact_money_status==3 ? $pfdata->infact_money : 0)+($pfdata->type != 3 ? $pfdata->min_money : 0));
                $rows[$key]['gptfcjssj'] += ($pfdata->infact_money_status==3 ? $pfdata->infact_money : 0);
                $rows[$key]['content_kouchu'] += (($pfdata->infact_money_status == 3 ? $pfdata->infact_money : 0) + ($pfdata->type != 3 ? $pfdata->min_money : 0))  * ($pfdata->crate3/100);
                $rows[$key]['dlfc'] += ($pfdata->infact_money_status==3 ? $pfdata->infact_money : 0) * ($pfdata->partner_rate/100);
                $rows[$key]['kfpjs'] += ($pfdata->infact_money_status==3 ? $pfdata->infact_money : 0);
//                $rows[$key]['kfpjs'] += (($pfdata->infact_money_status==3 ? $pfdata->infact_money : 0) * ($pfdata->crate2/100));
                $rows[$key]['bdsr1'] += ($pfdata->type != 3 ? $pfdata->min_money : 0);
                $rows[$key]['bdsr'] += ($pfdata->per_min_money);
                $rows[$key]['hzfmx'] += ($pfdata->type == 3 ? $pfdata->min_money : 0);
            } else {
                $month = $pfdata->month;    //月份
                $cartoon_name = $pfdata->cartoon_name;    //作品名称
                $all_money_infact = ($pfdata->infact_money_status==3 ? $pfdata->infact_money : 0)+($pfdata->type != 3 ? $pfdata->min_money : 0);    //总收入实际

                $all_bqcb = $pfdata->copyright_money;    //版权成本
                $bqffcjs = '';//版权方分成结算
                $zzffejs = '';//制作方分成结算

                $gptfcjssj = ($pfdata->infact_money_status==3 ? $pfdata->infact_money : 0);   //分成流水
                $content_kouchu = (($pfdata->infact_money_status == 3 ? $pfdata->infact_money : 0) + ($pfdata->type != 3 ? $pfdata->min_money : 0))  * ($pfdata->crate3/100);   //制作方内容扣除
//                $kfpjs = $gptfcjssj * ($pfdata->crate2/100);            //可分配结算实际
                $kfpjs = $gptfcjssj;                                      //可分配结算实际
                $bdsr1 = ($pfdata->type != 3 ? $pfdata->min_money : 0);                              //保底收入
                $bdsr = $pfdata->per_min_money;                           //保底收入
                $bqgs = $pfdata->company_name;                            //版权公司
                $bqgs_time = ($pfdata->expire_time??'-').'-'.($pfdata->expire_time2??'-'); //版权公司授权期限
                $bqgs_rate = $pfdata->copyright_rate;                     //版权公司授权比例50
                $gf = $pfdata->payment;
                $zf = $pfdata->pay_other;
                //版权分成结算
                $bqfcjs = 0;
                $zzgs = $pfdata->producer_name;
                $zzfbl = $pfdata->producer_rate;

                $zzfcjs = ($kfpjs - $all_bqcb - ($bqgs_rate/100)*($kfpjs - $all_bqcb) + $gf*$bqgs_rate/100) * ($zzfbl/100) / (1-$bqgs_rate/100*$zzfbl/100);

                $all_cost_infact = $all_bqcb + $gf + $bqfcjs + $zzfcjs;    //成本总额实际
                $income = $all_money_infact - $all_cost_infact;            //毛利润

                $dl = $pfdata->partner_name;//代理
                $dlfc = $gptfcjssj * $pfdata->partner_rate /100;

                // 合作方明细数据
                $hzfmx = $pfdata->type == 3 ? $pfdata->min_money : 0;

                $status1 = $pfdata->status;
                switch ($status1) {
                    case 1:
                        $status1 = '未结算';
                        break;
                    case 2:
                        $status1 = '结算中';
                        break;
                    case 3:
                        $status1 = '已结算';
                        break;
                    default:
                        $status1 = '未结算';
                        break;
                }

                $status2 = $pfdata->status2;
                switch ($status2) {
                    case 1:
                        $status2 = '未结算';
                        break;
                    case 2:
                        $status2 = '结算中';
                        break;
                    case 3:
                        $status2 = '已结算';
                        break;
                    default:
                        $status2 = '未结算';
                        break;
                }

                $rows[] = [
                    'month' => $month,
                    'cartoon_name' => $cartoon_name,
                    'all_money_infact'=>$all_money_infact,
                    'all_cost_infact'=>$all_cost_infact,
                    'income2'=>$income,
                    'income'=>$income,
                    'gptfcjssj'=>$gptfcjssj,
                    'kfpjs'=>$kfpjs,
                    'bdsr'=>$bdsr,
                    'bdsr1'=>$bdsr1,
                    'bqgs' => $bqgs,
                    'all_bqcb'=>$all_bqcb,
                    'bqfcjs'=>$bqfcjs,
                    'bqgs_time' => $bqgs_time,
                    'bqgs_rate'=>$bqgs_rate,
                    'status1'=>$status1,
                    'zzgs' => $zzgs,
                    'zzfcjs'=>$zzfcjs,
                    'zzfbl'=>$zzfbl,
                    'gf'=>$gf,
                    'zf'=>$zf,
                    'content_kouchu'=>$content_kouchu,
                    'status2'=>$status2,
                    'dl'=>$dl,
                    'dlfc'=>$dlfc,
                    'rate1'=>$pfdata->crate1,
//                    'rate2' => $pfdata->crate2,
                    'rate2' => $pfdata->rate2 > 0 ? $pfdata->rate2 : $pfdata->crate2,
                    'rate3'=>$pfdata->crate3,
//                    'rate4'=>$pfdata->crate4,
                    'rate4' => $pfdata->rate4 > 0 ? $pfdata->rate4 : $pfdata->crate4,
                    'rate5' => $pfdata->rate5 > 0 ? $pfdata->rate5 : $pfdata->crate5,
                    'mode'=>$pfdata->mode,
                    'mode2'=>$pfdata->mode2,
                    'hzfmx' => $hzfmx,
                ];

            }

        }

        foreach ($rows as $key => &$row) {
            $row['kfpjs'] =  ($row['kfpjs'] - $row['content_kouchu'])*0.6;


        //    $row['bqfcjs'] = ($row['kfpjs'] - $row['gf']) * ($row['bqgs_rate']/100) * (1-($row['zzfbl']/100));
            //新版权分成
//            dump($row['gptfcjssj']);15424.65
//            dump($row['rate5']);100.0
//            dump($row['bdsr1']);25000.0
//            dump($row['rate4']);100
//            dump($row['rate3']);10
//            dump($row['all_bqcb']);0
//            dump($row['gf']);0
//
//            dump($row['zf']);0
//            dump($row['content_kouchu']);4042.465
//            dump($row['bqgs_rate']);10
//            dump($row['zzfbl']);60
//
//            ((15424.65 * 100% + 25000 * 100%) - 0 - 0 - 0 - 4042.465 - (15424.65 * 100% + 25000 * 100% - 0 - 0)*10% ) * 60%
//            dump($row['mode2']);

            $row['bqfcjs'] = ((($row['gptfcjssj']*($row['rate2']/100))-$row['gf']-$row['zf'] + $row['hzfmx']) - (($row['gptfcjssj']*($row['rate2']/100)-$row['gf']-$row['zf'] + $row['hzfmx'])*($row['zzfbl']/100))) * ($row['bqgs_rate']/100);
            if ($row['mode'] == 2) {
                $row['bqfcjs'] = ($row['gptfcjssj']*($row['rate2']/100)  + $row['hzfmx'])*($row['bqgs_rate']/100);
            }

//            $row['bqfcjs'] = ((($row['gptfcjssj']*($row['rate2']/100))-$row['gf']-$row['zf'] + $row['hzfmx']) - (($row['gptfcjssj']-$row['gf']-$row['zf'] + $row['hzfmx'])*($row['zzfbl']/100))) * ($row['bqgs_rate']/100);
//            if ($row['mode'] == 2) {
//                $row['bqfcjs'] = ($row['gptfcjssj']*($row['rate2']/100) + $row['hzfmx'])*($row['bqgs_rate']/100);
//            }

            $row['zzfcjs'] = ((($row['gptfcjssj']*($row['rate5']/100) + $row['bdsr1']*($row['rate4']/100))-($row['rate3']>0 ? 0 : $row['all_bqcb'])*($row['gf']>0 ? 0 : 1)-$row['gf']-$row['zf']-$row['content_kouchu']) - (($row['gptfcjssj']*($row['rate5']/100)+ $row['bdsr1']*($row['rate4']/100)-$row['gf']-$row['zf'])*($row['bqgs_rate']/100))) * ($row['zzfbl']/100);
            if ($row['mode2'] == 2) {
                $row['zzfcjs'] = ((($row['gptfcjssj']*($row['rate5']/100) + $row['bdsr1']*($row['rate4']/100))) - (($row['gptfcjssj']+$row['bdsr1'])*($row['rate3']/100))) * ($row['zzfbl']/100);
            }

            $row['all_cost_infact'] = ($row['rate3']>0 ? 0 : $row['all_bqcb']) + $row['gf'] + ($row['bqfcjs']<0 ? 0 : $row['bqfcjs']) + ($row['zzfcjs']<0 ? 0 : $row['zzfcjs']);
            $row['income'] = $row['all_money_infact'] - $row['all_cost_infact'];
            $row['income2'] = $row['all_money_infact'] - $row['gf'];
        }

        /* 查询-计算数据e */

        if($is_expoter && Request::get('_export_') == 'all') {
            return $rows;
        }

        if($is_expoter && php_sapi_name() == 'cli') {
            return $rows;
        }
//        dump($rows);

        $total = count($rows);
        $perPage = Request::get('per_page', 20);

        $page = Request::get('page', 1);
        $new_rows = array_chunk($rows,$perPage);
        $new_rows = $new_rows[$page-1] ?? [];

        $totalRows = [
            'month' => '总计',
            'cartoon_name' => '',
            'all_money_infact' => 0,
            'all_cost_infact' => 0,
            'income2' => 0,
            'income' => 0,
            'gptfcjssj' => 0,
            'kfpjs' => 0,
            'bdsr' => 0,
            'bqgs' => '',
            'all_bqcb' => 0,
            'bqfcjs' => 0,
            'bqgs_time' => '',
            'bqgs_rate' => '',
            'status1' => '',
            'zzgs' => '',
            'zzfcjs' => 0,
            'zzfbl' => '',
            'gf' => 0,
            'zf' => 0,
            'content_kouchu' => 0,
            'status2' => '',
            'dl'=>'',
            'dlfc'=>'',
        ];
        foreach ($new_rows as $single) {

            $totalRows['all_money_infact'] += $single['all_money_infact'];
            $totalRows['all_cost_infact'] += $single['all_cost_infact'];
            $totalRows['income2'] += $single['income2'];
            $totalRows['income'] += $single['income'];
            $totalRows['gptfcjssj'] += $single['gptfcjssj'];
            $totalRows['kfpjs'] += $single['kfpjs'];
            $totalRows['bdsr'] += $single['bdsr'];
            $totalRows['all_bqcb'] += $single['all_bqcb'];
            $totalRows['bqfcjs'] += $single['bqfcjs'];
            $totalRows['zzfcjs'] += $single['zzfcjs'];
            $totalRows['gf'] += $single['gf'];
            $totalRows['zf'] += $single['zf'];
            $totalRows['content_kouchu'] += $single['content_kouchu'];
        }

        $new_rows[] = $totalRows;
        if($is_expoter) {
            return $new_rows;
        }

        $movies = static::hydrate($new_rows);

        $paginator = new LengthAwarePaginator($movies, $total, $perPage);

        $paginator->setPath(url()->current());

        return $paginator;
    }

    public function prefer_paginate($is_expoter = false)
    {
        /* 查询-计算数据s */
        $where = [];

        if (!empty(Request::get('copyright_id',''))) {
            $where[] = ['pfdatas.copyright_id', '=', Request::get('copyright_id','')];
        }
        if (!empty(Request::get('producer_id',''))) {
            $where[] = ['pfdatas.producer_id', '=', Request::get('producer_id','')];
        }
        if (!empty(Request::get('month',''))) {
            $where[] = ['pfdatas.month', '>=', Request::get('month','')];
        }
        if (!empty(Request::get('month2',''))) {
            $where[] = ['pfdatas.month', '<=', Request::get('month2','')];
        }
        if (!empty(Request::get('cartoon_id',''))) {
            $where[] = ['pfdatas.cartoon_id', '=', Request::get('cartoon_id','')];
        }
//        if (!empty(Request::get('company',''))) {
//            $where1[] = ['copyrights.company_name', 'like', '%'.Request::get('company','').'%'];
//            $where2[] = ['producers.producer_name', 'like', '%'.Request::get('company','').'%'];
//        }
        $pfdatas = DB::table('pfdatas')
            ->leftJoin('cartoons', 'pfdatas.cartoon_id', '=', 'cartoons.id')
            ->leftJoin('partners', 'cartoons.partner_id', '=', 'partners.id')
            ->leftJoin('producers', 'cartoons.producer_id', '=', 'producers.id')
//            ->leftJoin('copyrights', 'pfdatas.copyright_id', '=', 'copyrights.id')
            ->leftJoin('copyrights', 'cartoons.copyright_id', '=', 'copyrights.id')
            ->leftJoin('pfs', 'pfs.id', '=', 'pfdatas.pf_id')
            ->leftJoin('copyrightoutcomes', function ($join) {
                $join->on('copyrightoutcomes.month', '=', 'pfdatas.month')->on('copyrightoutcomes.cartoon_id', '=', 'pfdatas.cartoon_id')->where('copyrightoutcomes.type','=',1);
            })
            ->leftJoin('copyrightoutcomes as cs', function ($join) {
                $join->on('cs.month', '=', 'pfdatas.month')->on('cs.cartoon_id', '=', 'pfdatas.cartoon_id')->where('cs.type','=',2);
            })
            ->select('pfdatas.*','cartoons.cartoon_name','copyrights.company_name', 'copyrights.expire_time','copyrights.expire_time2','copyrights.copyright_rate','producers.producer_name', 'copyrightoutcomes.status','cs.status as status2','partners.partner_name','cartoons.partner_rate','cartoons.crate1','cartoons.crate2','cartoons.crate3','cartoons.crate4','cartoons.crate5','cartoons.mode','cartoons.mode2', 'pfs.type')
            ->where($where)
//            ->where(function ($query) use($where1, $where2) {
//                $query->where($where1)
//                    ->orWhere($where2);
//            })
            ->orderBy('pfdatas.month', 'desc')
            ->get();

        $rows = [];
        foreach ($pfdatas as $pfdata) {
            $key = $this->isSameCartoon($pfdata->cartoon_name, $pfdata->month, $rows);

            if ($key !== false) {
                $rows[$key]['all_money_infact'] += ($pfdata->plan_money+($pfdata->type != 3 ? $pfdata->min_money : 0));
                $rows[$key]['dlfc'] += ($pfdata->plan_money) * ($pfdata->partner_rate/100);
                $rows[$key]['gptfcjssj'] += ($pfdata->plan_money);
                $rows[$key]['content_kouchu'] += (($pfdata->plan_money + ($pfdata->type != 3 ? $pfdata->min_money : 0)) * ($pfdata->crate3/100));
//                $rows[$key]['kfpjs'] += ($pfdata->plan_money * ($pfdata->crate2/100));
                $rows[$key]['kfpjs'] += $pfdata->plan_money;
                $rows[$key]['bdsr1'] += ($pfdata->type != 3 ? $pfdata->min_money : 0);
                $rows[$key]['bdsr'] += ($pfdata->per_min_money);
                $rows[$key]['hzfmx'] += ($pfdata->type == 3 ? $pfdata->min_money : 0);
            } else {
                $month = $pfdata->month;    //月份
                $cartoon_name = $pfdata->cartoon_name;    //作品名称
                $all_money_infact = $pfdata->plan_money+($pfdata->type != 3 ? $pfdata->min_money : 0);    //总收入实际

                $all_bqcb = $pfdata->copyright_money;    //版权成本
                $bqffcjs = '';//版权方分成结算
                $zzffejs = '';//制作方分成结算

                $gptfcjssj = $pfdata->plan_money;   //个平台分成结算实际
                $content_kouchu = ($pfdata->plan_money + ($pfdata->type != 3 ? $pfdata->min_money : 0)) * ($pfdata->crate3/100);   //个平台分成结算实际
                $kfpjs = $gptfcjssj;  //可分配结算实际
                $bdsr1 = ($pfdata->type != 3 ? $pfdata->min_money : 0);
//                $bdsr1 = $pfdata->min_money;  //保底收入
                $bdsr = $pfdata->per_min_money;  //保底收入
                $bqgs = $pfdata->company_name; //版权公司
                $bqgs_time = ($pfdata->expire_time??'-').'-'.($pfdata->expire_time2??'-'); //版权公司授权期限
                $bqgs_rate = $pfdata->copyright_rate; //版权公司授权比例50
                $gf = $pfdata->payment;
                $zf = $pfdata->pay_other;
                //版权分成结算
                $bqfcjs = 0;
                $zzgs = $pfdata->producer_name;
                $zzfbl = $pfdata->producer_rate;

                $zzfcjs = ($kfpjs - $all_bqcb - ($bqgs_rate/100)*($kfpjs - $all_bqcb) + $gf*$bqgs_rate/100) * ($zzfbl/100) / (1-$bqgs_rate/100*$zzfbl/100);

                $all_cost_infact = $all_bqcb + $gf + $bqfcjs + $zzfcjs;    //成本总额实际
                $income = $all_money_infact - $all_cost_infact;//毛利润

                $dl = $pfdata->partner_name;//代理
                $dlfc = $gptfcjssj * $pfdata->partner_rate /100;

                // 合作方明细数据
                $hzfmx = $pfdata->type == 3 ? $pfdata->min_money : 0;

                $status1 = $pfdata->status;
                switch ($status1) {
                    case 1:
                        $status1 = '未结算';
                        break;
                    case 2:
                        $status1 = '结算中';
                        break;
                    case 3:
                        $status1 = '已结算';
                        break;
                    default:
                        $status1 = '未结算';
                        break;
                }

                $status2 = $pfdata->status2;
                switch ($status2) {
                    case 1:
                        $status2 = '未结算';
                        break;
                    case 2:
                        $status2 = '结算中';
                        break;
                    case 3:
                        $status2 = '已结算';
                        break;
                    default:
                        $status2 = '未结算';
                        break;
                }

                $rows[] = [
                    'month' => $month,
                    'cartoon_name' => $cartoon_name,
                    'all_money_infact'=>$all_money_infact,
                    'all_cost_infact'=>$all_cost_infact,
                    'income2'=>$income,
                    'income'=>$income,
                    'gptfcjssj'=>$gptfcjssj,
                    'kfpjs'=>$kfpjs,
                    'bdsr'=>$bdsr,
                    'bdsr1'=>$bdsr1,
                    'bqgs' => $bqgs,
                    'all_bqcb'=>$all_bqcb,
                    'bqfcjs'=>$bqfcjs,
                    'bqgs_time' => $bqgs_time,
                    'bqgs_rate'=>$bqgs_rate,
                    'status1'=>$status1,
                    'zzgs' => $zzgs,
                    'zzfcjs'=>$zzfcjs,
                    'zzfbl'=>$zzfbl,
                    'gf'=>$gf,
                    'zf'=>$zf,
                    'content_kouchu'=>$content_kouchu,
                    'status2'=>$status2,
                    'dl'=>$dl,
                    'dlfc'=>$dlfc,
                    'rate1'=>$pfdata->crate1,
//                    'rate2'=>$pfdata->crate2,
                    'rate2' => $pfdata->rate2 > 0 ? $pfdata->rate2 : $pfdata->crate2,
                    'rate3'=>$pfdata->crate3,
//                    'rate4'=>$pfdata->crate4,
                    'rate4' => $pfdata->rate4 > 0 ? $pfdata->rate4 : $pfdata->crate4,
                    'rate5' => $pfdata->rate5 > 0 ? $pfdata->rate5 : $pfdata->crate5,
                    'mode'=>$pfdata->mode,
                    'mode2'=>$pfdata->mode2,
                    'hzfmx' => $hzfmx,
                ];
            }

        }

        foreach ($rows as $key => &$row) {
//            $row['bqfcjs'] = ($row['kfpjs'] - $row['gf'] - $row['all_bqcb']) * ($row['bqgs_rate']/100) * (1-($row['zzfbl']/100));
//
//
//            $row['zzfcjs'] = ($row['kfpjs'] - $row['gf'] - $row['all_bqcb']) * ($row['zzfbl']/100) * (1-($row['bqgs_rate']/100));
//            $row['all_cost_infact'] = $row['all_bqcb'] + $row['gf'] + $row['bqfcjs'] + $row['zzfcjs'];
//            $row['income'] = $row['all_money_infact'] - $row['all_cost_infact'];

            $row['kfpjs'] =  ($row['kfpjs'] - $row['content_kouchu']) * 0.6;

            //    $row['bqfcjs'] = ($row['kfpjs'] - $row['gf']) * ($row['bqgs_rate']/100) * (1-($row['zzfbl']/100));
            //新版权分成
            $row['bqfcjs'] = ((($row['gptfcjssj']*($row['rate2']/100))-$row['gf']-$row['zf'] + $row['hzfmx']) - (($row['gptfcjssj']-$row['gf']-$row['zf'] + $row['hzfmx'])*($row['zzfbl']/100))) * ($row['bqgs_rate']/100);
            if ($row['mode'] == 2) {
                $row['bqfcjs'] = ($row['gptfcjssj']*($row['rate2']/100) + $row['hzfmx'])*($row['bqgs_rate']/100);
            }

            $row['zzfcjs'] = ((($row['gptfcjssj']*($row['rate5']/100) + $row['bdsr1']*($row['rate4']/100))-($row['rate3']>0 ? 0 : $row['all_bqcb'])*($row['gf']>0 ? 0 : 1)-$row['gf']-$row['zf']-$row['content_kouchu']) - (($row['gptfcjssj']-$row['gf']-$row['zf'])*($row['bqgs_rate']/100))) * ($row['zzfbl']/100);
            if ($row['mode2'] == 2) {
                $row['zzfcjs'] = ((($row['gptfcjssj']*($row['rate5']/100) + $row['bdsr1']*($row['rate4']/100))) - (($row['gptfcjssj']+$row['bdsr1'])*($row['rate3']/100))) * ($row['zzfbl']/100);
            }

            $row['all_cost_infact'] = ($row['rate3']>0 ? 0 : $row['all_bqcb']) + $row['gf'] + ($row['bqfcjs']<0 ? 0 : $row['bqfcjs']) + ($row['zzfcjs']<0 ? 0 : $row['zzfcjs']);
            $row['income'] = $row['all_money_infact'] - $row['all_cost_infact'];
            $row['income2'] = $row['all_money_infact'] - $row['gf'];

        }

        /* 查询-计算数据e */


        if($is_expoter && Request::get('_export_') == 'all') {

            return $rows;
        }

        $total = count($rows);
        $perPage = Request::get('per_page', 20);

        $page = Request::get('page', 1);
        $new_rows = array_chunk($rows,$perPage);
        $new_rows = $new_rows[$page-1] ?? [];

        $totalRows = [
            'month' => '总计',
            'cartoon_name' => '',
            'all_money_infact' => 0,
            'all_cost_infact' => 0,
            'income2' => 0,
            'income' => 0,
            'gptfcjssj' => 0,
            'kfpjs' => 0,
            'bdsr' => 0,
            'bqgs' => '',
            'all_bqcb' => 0,
            'bqfcjs' => 0,
            'bqgs_time' => '',
            'bqgs_rate' => '',
            'status1' => '',
            'zzgs' => '',
            'zzfcjs' => 0,
            'zzfbl' => '',
            'gf' => 0,
            'zf' => 0,
            'content_kouchu' => 0,
            'status2' => '',
            'dl'=>'',
            'dlfc'=>'',
        ];
        foreach ($new_rows as $single) {

            $totalRows['all_money_infact'] += $single['all_money_infact'];
            $totalRows['all_cost_infact'] += $single['all_cost_infact'];
            $totalRows['income2'] += $single['income2'];
            $totalRows['income'] += $single['income'];
            $totalRows['gptfcjssj'] += $single['gptfcjssj'];
            $totalRows['kfpjs'] += $single['kfpjs'];
            $totalRows['bdsr'] += $single['bdsr'];
            $totalRows['all_bqcb'] += $single['all_bqcb'];
            $totalRows['bqfcjs'] += $single['bqfcjs'];
            $totalRows['zzfcjs'] += $single['zzfcjs'];
            $totalRows['gf'] += $single['gf'];
            $totalRows['zf'] += $single['zf'];
            $totalRows['content_kouchu'] += $single['content_kouchu'];
        }

        $new_rows[] = $totalRows;

        if($is_expoter) {
            return $new_rows;
        }

        $movies = static::hydrate($new_rows);

        $paginator = new LengthAwarePaginator($movies, $total, $perPage);

        $paginator->setPath(url()->current());

        return $paginator;
    }

    private function isSameCartoon($cartoon_name, $month, $data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if ($value['cartoon_name'] == $cartoon_name && $value['month'] == $month) {
                    return $key;
                }
            }
        }
        return false;
    }

}
