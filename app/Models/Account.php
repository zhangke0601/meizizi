<?php

namespace App\Models;

use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class Account extends Model
{
    protected $table = 'accounts';

    public function copyright()
    {
        $result1 = [];
        if (Admin::user()->isRole('版权方')) {
            $copyright = Copyright::query()->where('admin_user_id','=', Admin::user()->id)->pluck('id')->toArray();

            if (!$copyright) {
                $result1 = [];
            } else {
                $result1 = DB::table('accounts')
                    ->leftJoin('copyrights', 'copyrights.id', '=', 'accounts.relation_id')
                    ->leftJoin('copyrightoutcomes', 'copyrightoutcomes.account_id', '=', 'accounts.id')
                    ->select('accounts.*','copyrights.company_name as name','copyrightoutcomes.status')
//                    ->select('accounts.*', 'copyrights.company_name as name')
                    ->where('accounts.type', '=', '1')
                    ->where('accounts.relation_id', '=', $copyright[0])
                    ->get()
                    ->toArray();
            }

        }

        $result2 = [];
        if (Admin::user()->isRole('制作方')) {
            $copyright = Producer::query()->where('admin_user_id','=', Admin::user()->id)->pluck('id')->toArray();

            if (!$copyright) {
                $result2 = [];
            } else {
                $result2 = DB::table('accounts')
                    ->leftJoin('producers', 'producers.id', '=', 'accounts.relation_id')
                    ->leftJoin('copyrightoutcomes', 'copyrightoutcomes.account_id', '=', 'accounts.id')
                    ->select('accounts.*','producers.producer_name as name','copyrightoutcomes.status')
//                    ->select('accounts.*', 'producers.producer_name as name')
                    ->where('accounts.type', '=', '2')
                    ->where('accounts.relation_id', '=', $copyright[0])
                    ->get()
                    ->toArray();
            }

        }

//        dump($result1);
//        dump($result2);
        $result = array_merge($result1, $result2);

        $total = count($result);
        $perPage = Request::get('per_page', 20);

        $page = Request::get('page', 1);


        $movies = static::hydrate($result);

        $paginator = new LengthAwarePaginator($movies, $total, $perPage);

        $paginator->setPath(url()->current());

        return $paginator;
    }

    /**
     * @return LengthAwarePaginator
     */
    public function ajax_paginate()
    {
        $type = Request::get('type', 0);
        $q = Request::get('q', '');
        $q = 0;
        if ($q == 0 && !Admin::user()->isAdministrator()) {
            return $this->copyright();
        }

        $where1 = [];
        $where2 = [];
        if (!empty(Request::get('month'))) {
            $where1[] = ['accounts.month','=',Request::get('month')];
            $where2[] = ['accounts.month','=',Request::get('month')];
        }
        if (!empty(Request::get('name'))) {
            $where1[] = ['copyrights.company_name','like','%'.Request::get('name').'%'];
            $where2[] = ['producers.producer_name','like','%'.Request::get('name').'%'];
        }

        $type = $type ?? 0;


        if ($type == 0) {
            $result = DB::table('accounts')
                ->leftJoin('copyrights', 'copyrights.id', '=', 'accounts.relation_id')
                ->leftJoin('copyrightoutcomes', 'copyrightoutcomes.account_id', '=', 'accounts.id')
                ->select('accounts.*','copyrights.company_name as name','copyrightoutcomes.status')
                ->where('accounts.type', '=', '1')
                ->where($where1)
                ->get()
                ->toArray();

            $result2 = DB::table('accounts')
                ->leftJoin('producers', 'producers.id', '=', 'accounts.relation_id')
                ->leftJoin('copyrightoutcomes', 'copyrightoutcomes.account_id', '=', 'accounts.id')
                ->select('accounts.*','producers.producer_name as name','copyrightoutcomes.status')
                ->where('accounts.type', '=', '2')
                ->where($where2)
                ->get()
                ->toArray();

            $result = array_merge($result,$result2);

        } elseif($type == 1) {
            $result = DB::table('accounts')
                ->leftJoin('copyrights', 'copyrights.id', '=', 'accounts.relation_id')
                ->leftJoin('copyrightoutcomes', 'copyrightoutcomes.account_id', '=', 'accounts.id')
                ->select('accounts.*','copyrights.company_name as name','copyrightoutcomes.status')
                ->where('accounts.type', '=', '1')
                ->where($where1)
                ->get()
                ->toArray();
        } else {
            $result = DB::table('accounts')
                ->leftJoin('producers', 'producers.id', '=', 'accounts.relation_id')
                ->leftJoin('copyrightoutcomes', 'copyrightoutcomes.account_id', '=', 'accounts.id')
                ->select('accounts.*','producers.producer_name as name','copyrightoutcomes.status')
                ->where('accounts.type', '=', '2')
                ->where($where2)
                ->get()
                ->toArray();
        }


        $total = count($result);
        $perPage = Request::get('per_page', 20);

        $page = Request::get('page', 1);


        $movies = static::hydrate($result);

        $paginator = new LengthAwarePaginator($movies, $total, $perPage);

        $paginator->setPath(url()->current());

        return $paginator;

    }

    public static function with($relations)
    {
        return new static;
    }
}
