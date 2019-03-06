<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Facades\Admin;

class HomeController extends Controller
{
    /**
     * 默认路由
     * 非最高管理员重定向至 合作方结算   最高管理员重定向至 渠道结算明细
     *
     * @param Content $content
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function index(Content $content)
    {
        if (!Admin::user()->isAdministrator()) {
            return redirect('/admin/account/make');
        }
        return redirect('/admin/incomes');

    }
}
