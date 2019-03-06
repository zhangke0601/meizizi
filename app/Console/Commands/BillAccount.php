<?php
/**
 * 计算版权、制作方的计算相关数据
 * @desc php artisan bill:account
 * @author 1520683535@qq.com
 */

namespace App\Console\Commands;

use App\Models\Statistics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BillAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bill:account';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate settlement data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $copytights = DB::table('copyrights')->get(['id', 'company_name'])->toArray();
        $producers = DB::table('producers')->get(['id', 'producer_name'])->toArray();
        $now = date('Y-m', time());
//        $now = '2018-11';
        $model = new Statistics();
        $result = $model->statistics_paginate(true);
        $last_quarter = $this->lastQuarter();

        $params1 = [];
        foreach ($copytights as $key => $copytight) {
            $params1[$key] = [
                'month' => $now,
                'type' => 1,
                'r_id' => $copytight->id,
                'should_bills' => 0,
                'have_bills' => 0,
                'now_bills' => 0,
                'last_quarter_bills' => 0,
            ];
            foreach ($result as $item) {
                if ($copytight->company_name == $item['bqgs'] && strtotime($item['month']) <= strtotime($now)) {
                    $params1[$key]['should_bills'] += $item['bqfcjs'];
                    if (strtotime($item['month']) <= strtotime($last_quarter)) {
                        $params1[$key]['last_quarter_bills'] += $item['bqfcjs'];
                    }
                }
            }
        }

        $params2 = [];
        foreach ($producers as $key => $producer) {
            $params2[$key] = [
                'month' => $now,
                'type' => 2,
                'r_id' => $producer->id,
                'should_bills' => 0,
                'have_bills' => 0,
                'now_bills' => 0,
                'last_quarter_bills' => 0,
            ];
            foreach ($result as $item) {
                if ($producer->producer_name == $item['zzgs'] && strtotime($item['month']) <= strtotime($now)) {
                    $params2[$key]['should_bills'] += $item['zzfcjs'];
                    if (strtotime($item['month']) <= strtotime($last_quarter)) {
                        $params2[$key]['last_quarter_bills'] += $item['zzfcjs'];
                    }
                }
            }
        }
//        var_dump($params2);die;


        foreach ($params1 as $key => $param) {
            $money = DB::table('accounts')
//                ->leftJoin('copyrightoutcomes', 'copyrightoutcomes.account_id', '=', 'accounts.id')
                ->where('type', '=', 1)
                ->where('month', '<=', $now)
                ->where('relation_id', '=', $param['r_id'])
                ->sum('money');
            $money2 = DB::table('accounts')
                ->leftJoin('copyrightoutcomes', 'copyrightoutcomes.account_id', '=', 'accounts.id')
                ->where('accounts.type', '=', 1)
                ->where('accounts.month', '<=', $now)
                ->where('accounts.relation_id', '=', $param['r_id'])
                ->where('copyrightoutcomes.status', '=', 3)
                ->sum('copyrightoutcomes.money');
            $param['doing_bills'] = $money - $money2;
            $param['have_bills'] = $money2;
            $param['now_bills'] = $param['should_bills'] - $param['have_bills'] - $param['doing_bills'];
            $param['last_quarter_bills'] = $param['last_quarter_bills'] - $money;

            $where = [
                ['month', '=', $param['month']],
                ['type', '=', 1],
                ['r_id', '=', $param['r_id']],
            ];
            $exists = DB::table('bills')->where($where)->first();
            if ($exists) {
                $param['updated_at'] = date('Y-m-d H:i:s');
                DB::table('bills')->where('id', $exists->id)->update($param);
            } else {
                $param['created_at'] = date('Y-m-d H:i:s');
                DB::table('bills')->insert($param);
            }
        }


        foreach ($params2 as $key => $param) {
            $money = DB::table('accounts')->where('type', '=', 2)
                ->where('month', '<=', $now)
                ->where('relation_id', '=', $param['r_id'])
                ->sum('money');
            $money2 = DB::table('accounts')
                ->leftJoin('copyrightoutcomes', 'copyrightoutcomes.account_id', '=', 'accounts.id')
                ->where('accounts.type', '=', 2)
                ->where('accounts.month', '<=', $now)
                ->where('accounts.relation_id', '=', $param['r_id'])
                ->where('copyrightoutcomes.status', '=', 3)
                ->sum('copyrightoutcomes.money');

            $param['doing_bills'] = $money - $money2;
            $param['have_bills'] = $money2;
            $param['now_bills'] = $param['should_bills'] - $param['have_bills'] - $param['doing_bills'];
            $param['last_quarter_bills'] = $param['last_quarter_bills'] - $money;

            $where = [
                ['month', '=', $param['month']],
                ['type', '=', 2],
                ['r_id', '=', $param['r_id']],
            ];
            $exists = DB::table('bills')->where($where)->first();
            if ($exists) {
                $param['updated_at'] = date('Y-m-d H:i:s');
                DB::table('bills')->where('id', $exists->id)->update($param);
            } else {
                $param['created_at'] = date('Y-m-d H:i:s');
                DB::table('bills')->insert($param);
            }
        }

        $this->info('success');
    }

    /**
     * 返回上个季度初始月份
     *
     * @return string
     */
    private function lastQuarter()
    {
        $month = date('m', time());
        $year = date('Y');

        if ($month <= 3) {
            return date('Y', strtotime('-1 year')).'-12';
        } elseif ($month <= 6) {
            return $year.'-03';
        } elseif ($month <= 9) {
            return $year.'-06';
        } else {
            return $year.'-09';
        }
    }
}
