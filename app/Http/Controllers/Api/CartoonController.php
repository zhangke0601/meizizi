<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cartoon;
use App\Models\Copyright;
use App\Models\Pf;
use App\Models\Pfdata;
use App\Models\Producer;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartoonController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index(Request $request)
    {
        $type = $request->get('q');

        if ($type == 1) {
            return Cartoon::query()->get(['id', 'novel_name as text']);
        }
        return Cartoon::query()->get(['id', 'cartoon_name as text']);
    }

    public function relation(Request $request)
    {
        $type = $request->get('q');

        if ($type == 1) {
            return Copyright::query()->get(['id', 'company_name as text']);
        } else {
            return Producer::query()->get(['id', 'producer_name as text']);
        }
    }

    public function lastMonth(Request $request)
    {
        $cartoon_id = $request->get('q');
        $month = $request->get('p');

        $last_month = date('Y-m', strtotime($month.' -1 month'));

        $where = [
            ['cartoon_id', '=', $cartoon_id],
            ['month', '=', $last_month]
        ];

        return Pfdata::query()->where($where)->get(['pf_id', 'is_line', 'is_nopay']);
    }

    public function detail(Request $request)
    {
        $month = $request->get('month');
        $type = $request->get('type');
        $rid = $request->get('rid');

        $total = 0;
        switch ($type) {
            case 1:  //版权方
//                $datas = DB::table('pfdatas')
//                    ->where('copyright_id', '=', $rid)->where('infact_money_status', '=', 3)->where('month', '<=', $month)
//                    ->get()->toArray();

                $datas = DB::table('bills')
                    ->where('r_id','=',$rid)->where('type','=',1)->where('month', '=', $month)->first();
                $total = $datas->now_bills ?? 0;
//                foreach ($datas as $data) {
//                    $total += $data->infact_money;
//                }
                break;

            case 2:   //制作方
//                $datas = DB::table('pfdatas')
//                    ->where('producer_id', '=', $rid)->where('infact_money_status', '=', 3)->where('month', '<=', $month)
//                    ->get()->toArray();
//
//                foreach ($datas as $data) {
//                    $total += $data->infact_money;
//                }
                $datas = DB::table('bills')
                    ->where('r_id','=',$rid)->where('type','=',2)->where('month', '=', $month)->first();
                $total = $datas->now_bills ?? 0;
                break;
        }

        return ['total' => round($total, 2)];
    }

    public function rate(Request $request)
    {
        $q = $request->get('q');
        $month = $request->get('p');
        $last_month = date('Y-m', strtotime($month.' -1 month'));

        $where = [
            ['cartoon_id', '=', $q],
            ['month', '=', $last_month]
        ];

        $last = Pfdata::query()->where($where)->select('rate2', 'rate4')->first();

        $cartoon = Cartoon::query()->select(['id', 'crate2', 'crate4'])->find($q)->toArray();

        return [
            'rate2' => $last['rate2'] ?? '',
            'rate4' => $last['rate4'] ?? '',
            'id' => $cartoon['id'],
            'crate2' => $cartoon['crate2'],
            'crate4' => $cartoon['crate4'],
        ];
    }

    public function syncCp()
    {
        set_time_limit(0);
        $datas = Pfdata::query()->get()->toArray();
//        dump($datas);die;
        foreach ($datas as $data) {
            $cartoon_id = $data['cartoon_id'];
            $cartoon = Cartoon::query()->select(['id', 'copyright_id', 'producer_id'])->find($cartoon_id)->toArray();
            Pfdata::where('id', $data['id'])
                ->update([
                    'copyright_id' => $cartoon['copyright_id'],
                    'producer_id' => $cartoon['producer_id'],
                ]);
        }
        echo 'success';die;
    }
}
