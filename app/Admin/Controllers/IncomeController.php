<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\ExcelExpoter;
use App\Http\Controllers\Controller;
use App\Http\Requests\admin\IncomeRequest;
use App\Models\Pfdata;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\MessageBag;

class IncomeController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        Admin::css('css/table.css');
        //缓存query参数

//        if (!empty(Input::get())) {
        Cache::put('income_query_params', Input::get(), '600');
//        }

        return $content
            ->header('预估数据列表&&实际数据列表')
            ->description('（1）数据表头中绿色字体的渠道是保底渠道，如该渠道保底金完成后继续分成，则相应添加一个渠道名+分成 的单独渠道做标记；
            （2）“唯快不破”与冬漫社和美滋滋都签订了渠道合同，作品收入归属需要看合同，以合同为准；
            （3）“微信读书”与美滋滋签约的合同，所有收入归属到美滋滋平台					
            （4）紫色数据表示未上线，浅蓝数据表示未计费，蓝色数据表示改数据未保存')
            ->body('<h4 style="text-align: center">月预估数据</h4>')
            ->body($this->grid())
            ->body('<h4 style="text-align: center">月实际结算数据</h4>')
            ->body($this->grid2());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('Edit')
            ->description('description')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        if (Request::get('type', 0) == 1) {
            return $content
                ->header('数据添加页面')
                ->description('.')
                ->body($this->single_form());
        }
        return $content
            ->header('数据添加页面')
            ->description('.')
            ->body($this->form_add());
    }

    public function changes(Content $content)
    {
        $form = new \Encore\Admin\Widgets\Form();

        $form->action('/admin/income/changes');

        $pfdata = DB::table('pfdatas')->where([['cartoon_id', '=', Request::get('cid')], ['month', '=', Request::get('month')]])->first();

//        dump($pfdata);
        $form->currency('copyright_money', '版权成本')->symbol('￥')->default($pfdata->copyright_money);
        $form->currency('payment', '稿费')->symbol('￥')->default($pfdata->payment);
        $form->currency('pay_other', '杂费')->symbol('￥')->default($pfdata->pay_other);
//        $form->rate('rate1', '周边分成比例')->setWidth(3)->default($pfdata->rate1);
//        $form->rate('rate2', '版权成本2比例')->setWidth(3)->default($pfdata->rate2);
//        $form->rate('rate3', '内容使用成本比')->setWidth(3)->default($pfdata->rate3);
//        $form->rate('rate4', '制作稿费2比例')->setWidth(3)->default($pfdata->rate4);
        $form->hidden('cartoon_id')->default(Request::get('cid'));
        $form->hidden('month')->default(Request::get('month'));

        $cartoon = DB::table('cartoons')->where('id', Request::get('cid'))->first();
        $title = $cartoon->cartoon_name . '的' . Request::get('month') . '的版权/制作方数据';
        $box = new Box($title, $form->render());

//        $box->removable();
//        $box->collapsable();
        $box->style('success');

        $box->solid();
//        $box->class = 'box box-info box-solid collapsed-box';

        return $content
            ->header('修改稿费/版权成本')
            ->description('.')
            ->body($box);
    }

    public function changep(\Illuminate\Http\Request $request)
    {
        $this->validate($request, [
            'payment' => 'required',
            'copyright_money' => 'required',
        ], [
            'payment.required' => '版权成本必填',
            'copyright_money.required' => '稿费必填',
        ]);
        if (!isset($_POST['copyright_money']) || !isset($_POST['payment'])) {
            $error = new MessageBag([
                'title' => '提示信息',
                'message' => '数据不能为空',
            ]);

            return redirect('admin/incomes')->with(compact('error'));
        }
        DB::table('pfdatas')
            ->where([['cartoon_id', '=', $_POST['cartoon_id']], ['month', '=', Request::post('month')]])
            ->update([
                'copyright_money' => $_POST['copyright_money'] ?? 0,
                'payment' => $_POST['payment'] ?? 0,
                'pay_other' => $_POST['pay_other'] ?? 0,
//                'rate1' => $_POST['rate1'] ?? 1,
//                'rate2' => $_POST['rate2'] ?? 0,
//                'rate3' => $_POST['rate3'] ?? 0,
//                'rate4' => $_POST['rate4'] ?? 0,
            ]);

        $success = new MessageBag([
            'title' => '提示信息',
            'message' => '修改成功',
        ]);

        if (Cache::has('income_query_params')) {
            return redirect('admin/incomes?' . http_build_query(Cache::get('income_query_params')))->with(compact('success'));
        }

        return redirect('admin/incomes')->with(compact('success'));
    }

    /**
     * 单个数据添加post
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function one(\Illuminate\Http\Request $request)
    {
        $pfdata = new Pfdata();

        $pfdata->pf_id = $request->pf_id;
        $pfdata->cartoon_id = $request->cartoon_id;
        $cartoon = DB::table('cartoons')->where('id', $request->cartoon_id)->select('copyright_id', 'producer_id', 'producer_rate')->first();

        $where = [
            ['pf_id', '=', $request->pf_id],
            ['cartoon_id', '=', $request->cartoon_id],
            ['start', '<=', $request->month . '-31'],
            ['end', '>=', $request->month . '-01'],
        ];

        $is_min = DB::table('mins')->where($where)->first();

        if ($is_min) {
            $pfdata->per_min_money = sprintf('%.2f', $is_min->total_money / $is_min->count);
        }

        $pfdata->copyright_id = $cartoon->copyright_id;
        $pfdata->producer_id = $cartoon->producer_id;
        $pfdata->producer_rate = $cartoon->producer_rate;
        $pfdata->month = $request->month;
        $pfdata->copyright_money = $request->copyright_money ?? 0;
        $pfdata->plan_money = $request->plan_money ?? 0;
        $pfdata->plan_money_type = $request->plan_money_type;
        $pfdata->plan_money_status = $request->plan_money_status;
        $pfdata->infact_money = $request->infact_money ?? 0;
        $pfdata->infact_money_type = $request->infact_money_type;
        $pfdata->infact_money_status = $request->infact_money_status;
        $pfdata->min_money = $request->min_money ?? 0;
        $pfdata->payment = $request->payment ?? 0;
        $pfdata->pay_other = $request->pay_other ?? 0;
        $pfdata->rate1 = $request->rate1 ?? 0;
        $pfdata->rate2 = $request->rate2 ?? 0;
        $pfdata->rate3 = $request->rate3 ?? 0;
        $pfdata->rate4 = $request->rate4 ?? 0;

        if ($request->is_line === 'on' || $request->is_line == 1) {
            $pfdata->is_line = 1;
        } else {
            $pfdata->is_line = 0;
        }

        if ($request->is_nopay === 'on' || $request->is_nopay === 0) {
            $pfdata->is_nopay = 0;
        } else {
            $pfdata->is_nopay = 1;
        }

        $where = [
            ['month', '=', $request->month],
            ['cartoon_id', '=', $request->cartoon_id],
            ['pf_id', '=', $request->pf_id],
        ];
        $exists = DB::table('pfdatas')->where($where)->first();

        if ($exists) {
            $error = new MessageBag([
                'title' => '提示信息',
                'message' => '请勿重复添加数据，如要修改数据，请点击对应金额修改即可！',
            ]);
            return back()->with(compact('error'));
        }

        $pfdata->save();

        $success = new MessageBag([
            'title' => '提示信息',
            'message' => '添加成功',
        ]);

//        return redirect('admin/incomes')->with(compact('success'));
        if (Cache::has('income_query_params')) {
            return redirect('admin/incomes?' . http_build_query(Cache::get('income_query_params')))->with(compact('success'));
        }
        return redirect('admin/incomes')->with(compact('success'));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Pfdata);

        /*筛选条件*/
        $grid->filter(function ($filter) {

            // 去掉默认的id过滤器
            $filter->disableIdFilter();

            $filter->column(1 / 3, function ($filter) {
                $filter->equal('month', '开始月份')->datetime(['format' => 'YYYY-MM']);

                $options = DB::table('cartoons')->get()->toArray();
                $selectOption = ['0' => '全部作品'];
                foreach ($options as $option) {
                    $selectOption[$option->id] = $option->cartoon_name;
                }
                $filter->equal('cartoon_id', '作品名称')->select($selectOption);
            });

            $filter->column(1 / 3, function ($filter) {
                $filter->equal('month2', '结束月份')->datetime(['format' => 'YYYY-MM']);

                $options = DB::table('pfs')->get()->toArray();
                $selectOption = ['0' => '全部渠道'];
                foreach ($options as $option) {
                    $selectOption[$option->id] = $option->pfname;
                }
                $filter->equal('pf_id', '渠道')->select($selectOption);
            });


            $filter->column(1 / 3, function ($filter) {
                $options = DB::table('copyrights')->get()->toArray();
                $selectOption = ['0' => '全部公司'];
                foreach ($options as $option) {
                    $selectOption[$option->id] = $option->company_name;
                }
                $filter->equal('copyright_id', '版权公司')->select($selectOption);

//                $filter->equal('type', '数据类型')->select(['2'=>'确认数据','1'=>'预估数据'])->default('1');
                $filter->equal('together', '是否合并')->select(['2' => '合并', '1' => '不合并'])->default('1');
            });

        });

        $grid->column('month', '年月日期');
        $grid->column('cartoon_name', '作品名称')->display(function ($cartoon_name) {
            return '<a href="income/edits?cid=' . $this->cartoon_id . '&month=' . $this->month . '"><span style="color:black">' . $cartoon_name . '</span></a>';
        });
        $grid->company_name('版权公司');
        $grid->column('money_count', '分成收入汇总');
//        $grid->column('min_money','保底汇总');
        $grid->column('min_money', '保底汇总')->display(function ($min_money) {//dump($min_money);
            return sprintf('%.2f', $min_money);
        });
        $grid->column('copyright_money', '版权成本')->display(function ($copyright_money) {
            $copyright_money = $copyright_money ?? 0;
            if ($this->month == '总计') {
                return '<span style="color:black">' . $copyright_money . '</span>';
            }
            return '<a href="income/changes?cid=' . $this->cartoon_id . '&month=' . $this->month . '"><span style="color:black">' . $copyright_money . '</span></a>';
        });
        $grid->column('payment', '稿费')->display(function ($payment) {
            $payment = $payment ?? 0;
            if ($this->month == '总计') {
                return '<span style="color:black">' . $payment . '</span>';
            }
            return '<a href="income/changes?cid=' . $this->cartoon_id . '&month=' . $this->month . '"><span style="color:black">' . $payment . '</span></a>';
        });
        $grid->column('pay_other', '杂费')->display(function ($pay_other) {
            $pay_other = $pay_other ?? 0;
            if ($this->month == '总计') {
                return '<span style="color:black">' . $pay_other . '</span>';
            }
            return '<a href="income/changes?cid=' . $this->cartoon_id . '&month=' . $this->month . '"><span style="color:black">' . $pay_other . '</span></a>';
        });

        $pf_id = Request::get('pf_id');
        if ($pf_id) {
            $pfs = DB::table('pfs')->where('id', '=', $pf_id)->get()->toArray();
        } else {
            $pfs = DB::table('pfs')->orderBy('type', 'asc')->get()->toArray();
        }
        foreach ($pfs as $pf) {
            if ($pf->type == 1) {
//                $grid->column($pf->sortname, '<a href="incomes/create?type=1&pfid='.$pf->id.'">'.$pf->pfname.'</a>')
                $grid->column($pf->sortname, '<a href="#">' . $pf->pfname . '</a>')
                    ->display(function ($money) use ($pf) {
                        if ($this->month == '总计') {
                            return '<span style="color:black">' . $money['money'] . '</span>';
                        }
                        if ($money['money'] == 0) {
                            if (!empty($money['id'])) {
                                if ($money['is_line'] == 0) {
                                    return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:#dd7fdb">' . $money['money'] . '</span></a>';
                                }
                                if ($money['is_nopay'] == 1) {
                                    return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:#50e9e0">' . $money['money'] . '</span></a>';
                                }
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:#ddd">' . $money['money'] . '</span></a>';
                            } else {
                                return '<a href="incomes/create?type=1&pfid=' . $pf->id . '"><span style="color:#5e5cdd">0.00</span></a>';
                            }
                        }
                        switch ($money['type']) {
                            case 1:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:black">' . $money['money'] . '</span></a>';
                                break;
                            case 2:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:red">' . $money['money'] . '</span></a>';
                                break;
                            case 3:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:green">' . $money['money'] . '</span></a>';
                                break;
                            default:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:black">' . $money['money'] . '</span></a>';
                                break;
                        }
                    });
            } elseif ($pf->type == 2) {
//                $grid->column($pf->sortname, '<a href="incomes/create?type=1&pfid='.$pf->id. '" style="color:#4f9158">' .$pf->pfname.'</a>')
                $grid->column($pf->sortname, '<a href="#" style="color:#4f9158">' . $pf->pfname . '</a>')
                    ->display(function ($money) use ($pf) {
                        if ($this->month == '总计') {
                            return '<span style="color:black">' . $money['money'] . '</span>';
                        }
                        if ($money['money'] == 0 || $money['money'] == '' || $money['money'] == null) {
//                            return '<a href="incomes/'.$money['id'].'/edit"><span style="color:#ddd">'.$money['money'].'</span></a>';
                            if (!empty($money['id'])) {
                                if ($money['is_line'] == 0) {
                                    return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:#dd7fdb">' . $money['money'] . '</span></a>';
                                }
                                if ($money['is_nopay'] == 1) {
                                    return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:#50e9e0">' . $money['money'] . '</span></a>';
                                }
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:#ddd">' . $money['money'] . '</span></a>';
                            } else {
                                return '<a href="incomes/create?type=1&pfid=' . $pf->id . '"><span style="color:#5e5cdd">0.00</span></a>';
                            }
                        }
                        switch ($money['type']) {
                            case 1:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:black">' . $money['money'] . '</span></a>';
                                break;
                            case 2:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:red">' . $money['money'] . '</span></a>';
                                break;
                            case 3:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:green">' . $money['money'] . '</span></a>';
                                break;
                            default:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:black">' . $money['money'] . '</span></a>';
                                break;
                        }
                    });
            } elseif ($pf->type == 3) { //合作方明细渠道

                $grid->column($pf->sortname, '<a href="#" style="color:#913b8d">' . $pf->pfname . '</a>')
                    ->display(function ($money) use ($pf) {
                        if ($this->month == '总计') {
                            return '<span style="color:black">' . $money['money'] . '</span>';
                        }
                        if ($money['money'] == 0 || $money['money'] == '' || $money['money'] == null) {
                            if (!empty($money['id'])) {
                                if ($money['is_line'] == 0) {
                                    return '<a href="#"><span style="color:#dd7fdb">' . $money['money'] . '</span></a>';
                                }
                                if ($money['is_nopay'] == 1) {
                                    return '<a href="#"><span style="color:#50e9e0">' . $money['money'] . '</span></a>';
                                }
                                return '<a href="#"><span style="color:#ddd">' . $money['money'] . '</span></a>';
                            } else {
                                return '<a href="#"><span style="color:#5e5cdd">0.00</span></a>';
                            }
                        }
                        switch ($money['type']) {
                            case 1:
                                return '<a href="#"><span style="color:black">' . $money['money'] . '</span></a>';
                                break;
                            case 2:
                                return '<a href="#"><span style="color:red">' . $money['money'] . '</span></a>';
                                break;
                            case 3:
                                return '<a href="#"><span style="color:green">' . $money['money'] . '</span></a>';
                                break;
                            default:
                                return '<a href="#"><span style="color:black">' . $money['money'] . '</span></a>';
                                break;
                        }
                    });
            }

        }

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });

//        $grid->disableExport();
        $grid->exporter(new ExcelExpoter());
        $grid->disableActions();
        $grid->disableRowSelector();

        return $grid;
    }


    protected function grid2()
    {
        $model = new Pfdata();
        $model->type = 2;
        $grid = new Grid($model);
        $grid->disablePagination();
        $grid->disableCreateButton();
        $grid->disableFilter();

        /*筛选条件*/
        $grid->filter(function ($filter) {

            // 去掉默认的id过滤器
            $filter->disableIdFilter();

            $filter->column(1 / 3, function ($filter) {
                $filter->equal('month', '年/月份')->datetime(['format' => 'YYYY-MM']);

                $options = DB::table('cartoons')->get()->toArray();
                $selectOption = ['0' => '全部作品'];
                foreach ($options as $option) {
                    $selectOption[$option->id] = $option->cartoon_name;
                }
                $filter->equal('cartoon_id', '作品名称')->select($selectOption);
            });

            $filter->column(1 / 3, function ($filter) {
                $options = DB::table('copyrights')->get()->toArray();
                $selectOption = ['0' => '全部公司'];
                foreach ($options as $option) {
                    $selectOption[$option->id] = $option->company_name;
                }
                $filter->equal('copyright_id', '版权公司')->select($selectOption);

                $options = DB::table('pfs')->get()->toArray();
                $selectOption = ['0' => '全部渠道'];
                foreach ($options as $option) {
                    $selectOption[$option->id] = $option->pfname;
                }
                $filter->equal('pf_id', '渠道')->select($selectOption);
            });


            $filter->column(1 / 3, function ($filter) {
                $filter->equal('type', '数据类型')->select(['2' => '确认数据', '1' => '预估数据'])->default('1');
            });

        });

        $grid->column('month', '年月日期');
//        $grid->column('cartoon_name', '作品名称');
        $grid->column('cartoon_name', '作品名称')->display(function ($cartoon_name) {
            return '<a href="income/edits?cid=' . $this->cartoon_id . '&month=' . $this->month . '"><span style="color:black">' . $cartoon_name . '</span></a>';
        });
        $grid->company_name('版权公司');
        $grid->column('money_count', '分成收入汇总');
        $grid->column('min_money', '保底汇总')->display(function ($min_money) {
            return sprintf('%.2f', $min_money);
        });
//        $grid->column('payment','稿费');
        $grid->column('copyright_money', '版权成本')->display(function ($copyright_money) {
            $copyright_money = $copyright_money ?? 0;
            if ($this->month == '总计') {
                return '<span style="color:black">' . $copyright_money . '</span>';
            }
            return '<a href="income/changes?cid=' . $this->cartoon_id . '&month=' . $this->month . '"><span style="color:black">' . $copyright_money . '</span></a>';
        });
        $grid->column('payment', '稿费')->display(function ($payment) {
            $payment = $payment ?? 0;
            if ($this->month == '总计') {
                return '<span style="color:black">' . $payment . '</span>';
            }
            return '<a href="income/changes?cid=' . $this->cartoon_id . '&month=' . $this->month . '"><span style="color:black">' . $payment . '</span></a>';
        });

        $grid->column('pay_other', '杂费')->display(function ($pay_other) {
            $pay_other = $pay_other ?? 0;
            if ($this->month == '总计') {
                return '<span style="color:black">' . $pay_other . '</span>';
            }
            return '<a href="income/changes?cid=' . $this->cartoon_id . '&month=' . $this->month . '"><span style="color:black">' . $pay_other . '</span></a>';
        });

        $pf_id = Request::get('pf_id');
        if ($pf_id) {
            $pfs = DB::table('pfs')->where('id', '=', $pf_id)->get()->toArray();
        } else {
            $pfs = DB::table('pfs')->orderBy('type', 'asc')->get()->toArray();
        }
        foreach ($pfs as $pf) {
            if ($pf->type == 1) {
//                $grid->column($pf->sortname, '<a href="incomes/create?type=1&pfid='.$pf->id.'">'.$pf->pfname.'</a>')
                $grid->column($pf->sortname, '<a href="#">' . $pf->pfname . '</a>')
                    ->display(function ($money) use ($pf) {
                        if ($this->month == '总计') {
                            return '<span style="color:black">' . $money['money'] . '</span>';
                        }
                        if ($money['money'] == 0) {
//                            return '<a href="incomes/'.$money['id'].'/edit"><span style="color:#ddd">'.$money['money'].'</span></a>';
                            if (!empty($money['id'])) {
                                if ($money['is_line'] == 0) {
                                    return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:#dd7fdb">' . $money['money'] . '</span></a>';
                                }
                                if ($money['is_nopay'] == 1) {
                                    return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:#50e9e0">' . $money['money'] . '</span></a>';
                                }
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:#ddd">' . $money['money'] . '</span></a>';
                            } else {
                                return '<a href="incomes/create?type=1&pfid=' . $pf->id . '"><span style="color:#5e5cdd">0.00</span></a>';
                            }
                        }
                        switch ($money['type']) {
                            case 1:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:black">' . $money['money'] . '</span></a>';
                                break;
                            case 2:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:red">' . $money['money'] . '</span></a>';
                                break;
                            case 3:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:green">' . $money['money'] . '</span></a>';
                                break;
                            case 4:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:#acaa30">' . $money['money'] . '</span></a>';
                                break;
                            default:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:black">' . $money['money'] . '</span></a>';
                                break;
                        }
                    });
            } elseif ($pf->type == 2) {
//                $grid->column($pf->sortname, '<a href="incomes/create?type=1&pfid='.$pf->id.'" style="color:#4f9158">'.$pf->pfname.'</a>')
                $grid->column($pf->sortname, '<a href="#" style="color:#4f9158">' . $pf->pfname . '</a>')
                    ->display(function ($money) use ($pf) {
                        if ($this->month == '总计') {
                            return '<span style="color:black">' . $money['money'] . '</span>';
                        }
                        if ($money['money'] == 0) {
//                            return '<a href="incomes/'.$money['id'].'/edit"><span style="color:#ddd">'.$money['money'].'</span></a>';
                            if (!empty($money['id'])) {
                                if ($money['is_line'] == 0) {
                                    return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:#dd7fdb">' . $money['money'] . '</span></a>';
                                }
                                if ($money['is_nopay'] == 1) {
                                    return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:#50e9e0">' . $money['money'] . '</span></a>';
                                }
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:#ddd">' . $money['money'] . '</span></a>';
                            } else {
                                return '<a href="incomes/create?type=1&pfid=' . $pf->id . '"><span style="color:#5e5cdd">0.00</span></a>';
                            }
                        }

                        switch ($money['type']) {
                            case 1:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:black">' . $money['money'] . '</span></a>';
                                break;
                            case 2:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:red">' . $money['money'] . '</span></a>';
                                break;
                            case 3:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:green">' . $money['money'] . '</span></a>';
                                break;
                            case 4:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:#acaa30">' . $money['money'] . '</span></a>';
                            default:
                                return '<a href="incomes/' . $money['id'] . '/edit"><span style="color:black">' . $money['money'] . '</span></a>';
                                break;
                        }
                    });
            } elseif ($pf->type == 3) { //合作方明细渠道

                $grid->column($pf->sortname, '<a href="#" style="color:#913b8d">' . $pf->pfname . '</a>')
                    ->display(function ($money) use ($pf) {
                        if ($this->month == '总计') {
                            return '<span style="color:black">' . $money['money'] . '</span>';
                        }
                        if ($money['money'] == 0 || $money['money'] == '' || $money['money'] == null) {
                            if (!empty($money['id'])) {
                                if ($money['is_line'] == 0) {
                                    return '<a href="#"><span style="color:#dd7fdb">' . $money['money'] . '</span></a>';
                                }
                                if ($money['is_nopay'] == 1) {
                                    return '<a href="#"><span style="color:#50e9e0">' . $money['money'] . '</span></a>';
                                }
                                return '<a href="#"><span style="color:#ddd">' . $money['money'] . '</span></a>';
                            } else {
                                return '<a href="#"><span style="color:#5e5cdd">0.00</span></a>';
                            }
                        }
                        switch ($money['type']) {
                            case 1:
                                return '<a href="#"><span style="color:black">' . $money['money'] . '</span></a>';
                                break;
                            case 2:
                                return '<a href="#"><span style="color:red">' . $money['money'] . '</span></a>';
                                break;
                            case 3:
                                return '<a href="#"><span style="color:green">' . $money['money'] . '</span></a>';
                                break;
                            default:
                                return '<a href="#"><span style="color:black">' . $money['money'] . '</span></a>';
                                break;
                        }
                    });
            }

        }

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });

        $grid->disableExport();
        $grid->disableActions();
        $grid->disableRowSelector();

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Pfdata::findOrFail($id));

        return $show;
    }

    public function all(IncomeRequest $request)
    {
        $cartoon_id = $request->cartoon_id;
        $cartoon = DB::table('cartoons')->where('id', $cartoon_id)->select('copyright_id', 'producer_id', 'producer_rate')->first();
        $pf_ids = $request->pf_id;

        foreach ($pf_ids as $pf_id) {
            $pfdata = new Pfdata();

            $where = [
                ['pf_id', '=', $pf_id],
                ['cartoon_id', '=', $cartoon_id],
                ['start', '<=', $request->month . '-31'],
                ['end', '>=', $request->month . '-01'],
            ];
            $is_min = DB::table('mins')->where($where)->first();
            if ($is_min) {
                $pfdata->per_min_money = sprintf('%.2f', $is_min->total_money / $is_min->count);
            }

            $pfdata->pf_id = $pf_id;
            $pfdata->cartoon_id = $cartoon_id;
            $pfdata->copyright_id = $cartoon->copyright_id;
            $pfdata->producer_id = $cartoon->producer_id;
            $pfdata->producer_rate = $cartoon->producer_rate;
            $pfdata->month = $request->month;
            $pfdata->copyright_money = $request->copyright_money ?? 0;
            $pfdata->plan_money = $request->plan_money[$pf_id] ?? 0;
            $pfdata->plan_money_type = $request->plan_money_type[$pf_id];
            $pfdata->plan_money_status = $request->plan_money_status[$pf_id];
            $pfdata->infact_money = $request->infact_money[$pf_id] ?? 0;
            $pfdata->infact_money_type = $request->infact_money_type[$pf_id];
            $pfdata->infact_money_status = $request->infact_money_status[$pf_id];
            $pfdata->min_money = $request->min_money[$pf_id] ?? 0;
            $pfdata->payment = $request->payment ?? 0;
            $pfdata->pay_other = $request->pay_other ?? 0;
            $pfdata->is_line = \in_array($pf_id, $request->is_line) ? 0 : 1;
            $pfdata->is_nopay = \in_array($pf_id, $request->is_nopay) ? 1 : 0;
            $pfdata->rate1 = $request->rate1;
            $pfdata->rate2 = $request->rate2;
            $pfdata->rate3 = $request->rate3;
            $pfdata->rate4 = $request->rate4;
            $pfdata->rate5 = $request->rate5;
            $pfdata->rate_sure = $request->rate_sure;

            $where = [
                ['month', '=', $request->month],
                ['cartoon_id', '=', $request->cartoon_id],
                ['pf_id', '=', $pf_id],
            ];
            $exists = DB::table('pfdatas')->where($where)->first();

            if ($exists) {
                DB::table('pfdatas')
                    ->where('id', $exists->id)
                    ->update([
                        'pf_id' => $pf_id,
                        'cartoon_id' => $cartoon_id,
                        'copyright_id' => $cartoon->copyright_id,
                        'producer_id' => $cartoon->copyright_id,
                        'producer_rate' => $cartoon->producer_rate,
                        'month' => $request->month,
                        'copyright_money' => $request->copyright_money ?? 0,
                        'plan_money' => $request->plan_money[$pf_id] ?? 0,
                        'plan_money_type' => $request->plan_money_type[$pf_id],
                        'plan_money_status' => $request->plan_money_status[$pf_id],
                        'infact_money' => $request->infact_money[$pf_id] ?? 0,
                        'infact_money_type' => $request->infact_money_type[$pf_id],
                        'infact_money_status' => $request->infact_money_status[$pf_id],
                        'min_money' => $request->min_money[$pf_id] ?? 0,
                        'payment' => $request->payment ?? 0,
                        'pay_other' => $request->pay_other ?? 0,
                        'is_line' => \in_array($pf_id, $request->is_line) ? 0 : 1,
                        'is_nopay' => \in_array($pf_id, $request->is_nopay) ? 1 : 0,
                        'rate1' => $request->rate1,
                        'rate2' => $request->rate2,
                        'rate3' => $request->rate3,
                        'rate4' => $request->rate4,
                        'rate5' => $request->rate5,
                        'rate_sure' => $request->rate_sure,
                    ]);
            } else {
                $pfdata->save();
            }


        }

        $success = new MessageBag([
            'title' => '提示信息',
            'message' => '批量添加成功',
        ]);

        if (Cache::has('income_query_params')) {
            return redirect('admin/incomes?' . http_build_query(Cache::get('income_query_params')))->with(compact('success'));
        }
        return redirect('admin/incomes')->with(compact('success'));

    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Pfdata);

        //保存数据前回调
        $form->saving(function (Form $form) {
//            dump($form->id);die;
            $cartoon_id = $form->cartoon_id;

            $cartoon = DB::table('cartoons')->where('id', $cartoon_id)->select('copyright_id', 'producer_id', 'producer_rate')->first();
            $form->copyright_id = $cartoon->copyright_id;
            $form->producer_id = $cartoon->producer_id;
            $form->producer_rate = $cartoon->producer_rate;

            $where = [
                ['month', '=', $form->month],
                ['cartoon_id', '=', $form->cartoon_id],
                ['pf_id', '=', $form->pf_id],
            ];
            $exists = DB::table('pfdatas')->where($where)->first();
            if ($exists) {
//                dump(1);die;
//                $error = new MessageBag([
//                    'title'   => '提示信息',
//                    'message' => '请勿重复添加数据，如要修改数据，请点击对应金额修改即可！',
//                ]);
//                return redirect('/admin/incomes')->with(compact('error'));

//                DB::table('pfdatas')
//                    ->where('id', $exists->id)
//                    ->update([
//                        'pf_id' => $form->pf_id,
//                        'cartoon_id' => $form->cartoon_id,
//                        'copyright_id' => $form->copyright_id,
//                        'producer_id' => $form->producer_id,
//                        'producer_rate' => $form->producer_rate,
//                        'month' => $form->month,
//                        'copyright_money' => $form->copyright_money,
//                        'plan_money' => $form->plan_money,
//                        'plan_money_type' => $form->plan_money_type,
//                        'plan_money_status' => $form->plan_money_status,
//                        'infact_money' => $form->infact_money,
//                        'infact_money_type' => $form->infact_money_type,
//                        'infact_money_status' => $form->infact_money_status,
//                        'min_money' => $form->min_money,
//                    ]);
//                return redirect('/admin/incomes');
            }

        });


        $form->tab('基本信息', function ($form) {
            $options = DB::table('pfs')->select('id', 'pfname')->get();
            $selectOption = [];
            foreach ($options as $option) {
                $selectOption[$option->id] = $option->pfname;
            }

            $pf_id = Request::get('pfid', '');
//        $form->select('pf_id','渠道方')->options($selectOption)->default($pf_id)->setWidth(2)->required();
            $form->display('pf_id', '渠道方')->setWidth(2)->with(function ($value) use ($selectOption) {
                return $selectOption[$value];
            });

            $options = DB::table('cartoons')->select('id', 'cartoon_name')->get();
            $selectOption = [];
            foreach ($options as $option) {
                $selectOption[$option->id] = $option->cartoon_name;
            }
            $form->display('cartoon_id', '作品名称')->setWidth(3)->with(function ($value) use ($selectOption) {
                return $selectOption[$value];
            });

            $form->hidden('copyright_id');
            $form->hidden('cartoon_id');
            $form->hidden('producer_id');
            $form->hidden('producer_rate');

            $form->display('month', '年/月份')->setWidth(1);

            $form->currency('plan_money', '<span style="color: #85202d">预估分成金额</span>')->symbol('￥');
            $form->radio('plan_money_type', '预估分成金额类型')
                ->options([1 => '预估数据', '3' => '已确认按月分摊数据/已确认数据'])->default('3');
            $form->radio('plan_money_status', '预估分成金额状态')
                ->options([1 => '不可结算', 2 => '等待到账', '3' => '已到账可结算'])->default('1');
            $form->currency('infact_money', '<span style="color: #4d8545">实际确认金额</span>')->symbol('￥');
            $form->radio('infact_money_type', '实际确认金额类型')
                ->options([1 => '预估数据', 2 => '已确认按月分摊数据', '3' => ' 已确认可结算数据'])->default('3');
//                ->options(['3' => ' 已确认可结算数据'])->default('3');
            $form->radio('infact_money_status', '实际确认金额状态')
                ->options([1 => '不可结算', 2 => '等待到账', '3' => '已到账可结算'])->default('1');
            $form->currency('min_money', '保底金额')->symbol('￥');


            $states = [
                'on' => ['value' => 1, 'text' => '上线', 'color' => 'success'],
                'off' => ['value' => 0, 'text' => '下线', 'color' => 'danger'],
            ];

            $form->switch('is_line', '是否上线')->states($states);

            $states = [
                'on' => ['value' => 0, 'text' => '计费', 'color' => 'success'],
                'off' => ['value' => 1, 'text' => '未计费', 'color' => 'danger'],
            ];

            $form->switch('is_nopay', '是否计费')->states($states);
        });

        $form->tab('版权方/制作方', function ($form) {
            $form->html('<span style="color:#ff756b;font-size: 18px">版权方信息</span>');
            $form->currency('copyright_money', '版权成本')->symbol('￥');

            $form->rate('rate1', '周边分成比例')->setWidth(3);
//            $form->rate('crate2', '默认版权成本2比例')->setWidth(3)->display()->help('请在作品列表中编辑设置');
            $form->rate('rate2', '当月版权成本2比例')->setWidth(3);

            $form->divide();
            $form->html('<span style="color:#6effe6;font-size: 18px">制作方信息</span>');
            $form->currency('payment', '制作方稿费')->symbol('￥');
            $form->rate('rate3', '内容使用成本比')->setWidth(3);
            $form->rate('rate4', '当月制作稿费2比例')->setWidth(3);
        });

        $form->footer(function ($footer) {

            // 去掉`查看`checkbox
            $footer->disableViewCheck();

            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();

        });

        return $form;

    }

    protected function form_add()
    {
        Admin::css('css/table.css');
        $form = new Form(new Pfdata);

        $form->setAction('/admin/incomes/all');

        $form->tab('基本信息', function ($form) {

            $options = DB::table('cartoons')->select('id', 'cartoon_name')->get();
            $selectOption = [];
            foreach ($options as $option) {
                $selectOption[$option->id] = $option->cartoon_name;
            }
            $form->select('cartoon_id', '作品名称')->options($selectOption)->setWidth(3)->required()->loading('q', '/api/last-month')->help('先选择日期，再选择作品，可自动读取上个月的上线和计费情况');

            $form->hidden('copyright_id');
            $form->hidden('producer_id');
            $form->hidden('producer_rate');

            $form->time('month', '年份月份')->format('YYYY-MM');


            $options = DB::table('pfs')->get()->toArray();
            $selectOption = [];
            foreach ($options as $option) {
                $selectOption[$option->id] = $option->pfname;
            }
//            $form->checkbox('is_line', '未上线渠道')->options($selectOption);
            $form->listbox('is_line', '未上线渠道（右边为未上线）')->options($selectOption)->checked();

//            $form->checkbox('is_nopay', '未计费渠道')->options($selectOption);
            $form->listbox('is_nopay', '未计费渠道（右边为未计费）')->options($selectOption);
        });

        $form->tab('版权/制作方', function ($form) {
            $form->html('<span style="color:#ff756b;font-size: 18px">版权方信息</span>');
            $form->currency('copyright_money', '版权成本')->symbol('￥');

            $form->rate('rate1', '周边分成比例')->setWidth(3);
            $form->rate('crate2', '默认版权成本2比例')->setWidth(3)->help('请在作品列表中编辑设置，此处修改无效')->disable();
//            $form->display('crate2', '默认版权成本2比例')->setWidth(3)->help('请在作品列表中编辑设置')->with(function () {
//                return '';
//            });
            $form->rate('rate2', '当月版权成本2比例')->setWidth(3);

            $form->divide();
            $form->html('<span style="color:#6effe6;font-size: 18px">制作方信息</span>');

            $form->currency('payment', '制作方稿费')->symbol('￥');
            $form->currency('pay_other', '制作杂费')->symbol('￥');
            $form->rate('rate3', '内容使用成本比')->setWidth(3);
            $form->rate('crate4', '默认保底分成2比例')->setWidth(3)->help('请在作品列表中编辑设置，此处修改无效')->disable();
            $form->rate('rate4', '当月保底分成2比例')->setWidth(3);
            $form->rate('crate5', '默认制作分成2比例')->setWidth(3)->help('请在作品列表中编辑设置，此处修改无效')->disable();
            $form->rate('rate5', '当月制作分成2比例')->setWidth(3);
            $form->radio('rate_sure', '比例确认状态')->options(['0' => '预计', '1' => '确认'])->help('确认状态下的数据才显示给合作方');
        });

        //找出所有的渠道方，循环添加表单
        $pfs = DB::table('pfs')->get()->toArray();

        foreach ($pfs as $pf) {
            if ($pf->type == 3) {
                $form->tab($pf->pfname, function ($form) use ($pf) {
                    $form->hidden('pf_id[' . $pf->id . ']')->value($pf->id);

                    $form->currency('min_money[' . $pf->id . ']', '合作方明细金额')->symbol('￥')->help('该金额只用于版权方分成计算，只展示给版权方，不展示给制作方以及不参与总收入的计算');
                    $form->hidden('plan_money_type[' . $pf->id . ']')->value(1);
                    $form->hidden('plan_money_status[' . $pf->id . ']')->value(1);
                    $form->hidden('infact_money_type[' . $pf->id . ']')->value(1);
                    $form->hidden('infact_money_status[' . $pf->id . ']')->value(1);

                });
            } else {
                $form->tab($pf->pfname, function ($form) use ($pf) {
                    $form->hidden('pf_id[' . $pf->id . ']')->value($pf->id);
                    $form->currency('plan_money[' . $pf->id . ']', '<span style="color: #85202d">预估分成金额</span>')->symbol('￥')->help('保底渠道建议不填写此项');
                    $form->radio('plan_money_type[' . $pf->id . ']', '预估分成金额类型')
//                    ->options([1 => '预估数据', 2 => '已确认按月分摊数据', '3' => ' 已确认可结算数据'])->default('1');
                        ->options([1 => '预估数据', '3' => '已确认按月分摊数据/已确认数据'])->default('1');
                    $form->radio('plan_money_status[' . $pf->id . ']', '预估分成金额状态')
                        ->options([1 => '不可结算', 2 => '等待到账', '3' => '已到账可结算'])->default('1');
                    $form->currency('infact_money[' . $pf->id . ']', '<span style="color: #4d8545">实际确认金额</span>')->symbol('￥')->help('保底渠道建议不填写此项');
                    $form->radio('infact_money_type[' . $pf->id . ']', '实际确认金额类型')
                        ->options([1 => '预估数据', 2 => '已确认按月分摊数据', '3' => ' 已确认可结算数据'])->default('1');
                    $form->radio('infact_money_status[' . $pf->id . ']', '实际确认金额状态')
                        ->options([1 => '不可结算', 2 => '等待到账', '3' => '已到账可结算'])->default('1');
                    $form->currency('min_money[' . $pf->id . ']', '保底金额')->symbol('￥')->help('<span style="color:red">这里只能输入总金额，若需要添加均分金额，请到"保底均分"添加</span>');

                });
            }
        }


        $form->footer(function ($footer) {
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
        });

        return $form;
    }


    /**
     * 添加单个数据
     * @return Form
     */
    protected function single_form()
    {
        $form = new Form(new Pfdata);

        $form->setAction('/admin/incomes/one');


        $form->tab('渠道信息', function ($form) {
            $options = DB::table('pfs')->select('id', 'pfname')->get();
            $selectOption = [];
            foreach ($options as $option) {
                $selectOption[$option->id] = $option->pfname;
            }

            $pf_id = Request::get('pfid', '');
            $form->select('pf_id', '渠道方')->options($selectOption)->default($pf_id)->setWidth(2)->required();

            $options = DB::table('cartoons')->select('id', 'cartoon_name')->get();
            $selectOption = [];
            foreach ($options as $option) {
                $selectOption[$option->id] = $option->cartoon_name;
            }
            $form->select('cartoon_id', '作品名称')->options($selectOption)->setWidth(3)->loading('q', '/api/xx')->required();

            $form->hidden('copyright_id');
            $form->hidden('producer_id');
            $form->hidden('producer_rate');


            $form->time('month', '年份月份')->format('YYYY-MM')->required();


            $form->currency('plan_money', '<span style="color: #85202d">预估分成金额</span>')->symbol('￥')->help('保底渠道建议不填写此项');
            $form->radio('plan_money_type', '预估分成金额类型')
                ->options([1 => '预估数据', '3' => '已确认按月分摊数据/已确认数据'])->default('1');
            $form->radio('plan_money_status', '预估分成金额状态')
                ->options([1 => '不可结算', 2 => '等待到账', '3' => '已到账可结算'])->default('1');
            $form->currency('infact_money', '<span style="color: #4d8545">实际确认金额</span>')->symbol('￥')->help('保底渠道建议不填写此项');
            $form->radio('infact_money_type', '实际确认金额类型')
                ->options([1 => '预估数据', 2 => '已确认按月分摊数据', '3' => ' 已确认可结算数据'])->default('1');
            $form->radio('infact_money_status', '实际确认金额状态')
                ->options([1 => '不可结算', 2 => '等待到账', '3' => '已到账可结算'])->default('1');
            $form->currency('min_money', '保底金额')->symbol('￥')->default(0);


            $states = [
                'on' => ['value' => 1, 'text' => '上线', 'color' => 'success'],
                'off' => ['value' => 0, 'text' => '下线', 'color' => 'danger'],
            ];

            $form->switch('is_line', '是否上线')->states($states);

            $states = [
                'on' => ['value' => 0, 'text' => '计费', 'color' => 'success'],
                'off' => ['value' => 1, 'text' => '未计费', 'color' => 'danger'],
            ];

            $form->switch('is_nopay', '是否计费')->states($states);

        });

        $form->tab('版权/制作方相关', function ($form) {
            $form->html('<span style="color:#ff756b;font-size: 18px">版权方信息</span>');
            $form->currency('copyright_money', '版权成本')->symbol('￥');
            $form->rate('rate1', '周边分成比例')->setWidth(3);
            $form->rate('crate2', '默认版权成本2比例')->setWidth(3)->help('请在作品列表中编辑设置，此处修改无效')->disable();
            $form->rate('rate2', '当月版权成本2比例')->setWidth(3);

            $form->divide();
            $form->html('<span style="color:#6effe6;font-size: 18px">制作方信息</span>');
            $form->rate('rate3', '内容使用成本比')->setWidth(3);
            $form->rate('crate4', '默认制作稿费2比例')->setWidth(3)->help('请在作品列表中编辑设置，此处修改无效')->disable();
            $form->rate('rate4', '当月制作稿费2比例')->setWidth(3);
            $form->currency('payment', '制作方稿费')->symbol('￥')->default(0);
            $form->currency('pay_other', '制作方稿费')->symbol('￥')->default(0);
        });


        $form->footer(function ($footer) {

            // 去掉`查看`checkbox
            $footer->disableViewCheck();

            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();

        });

        return $form;
    }

    public function editAll(Content $content)
    {
        Admin::css('css/table.css');

        $form = new Form(new Pfdata);

        $form->setAction('/admin/income/edits');

        $form->tools(function (Form\Tools $tools) {

            // 去掉`列表`按钮
            $tools->disableList();

            // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
            $tools->append('<a href="/admin/incomes" class="btn btn-sm btn-default" title=""><i class="fa fa-list"></i><span class="hidden-xs">&nbsp;列表</span></a>');
        });

        $month = Request::get('month', '');
        $cartoon_id = Request::get('cid', '');

        $pfdatas = DB::table('pfdatas')
            ->where('month', '=', $month)
            ->where('cartoon_id', '=', $cartoon_id)
            ->get()->toArray();
        $datas = [];
        foreach ($pfdatas as $pfdata) {
            $datas[$pfdata->pf_id] = $pfdata;
        }
//        dump($datas);

        $form->setTitle('批量修改');

        $form->tab('基本信息', function ($form) use ($pfdatas) {

            $options = DB::table('cartoons')->select('id', 'cartoon_name')->get();
            $selectOption = [];
            foreach ($options as $option) {
                $selectOption[$option->id] = $option->cartoon_name;
            }
//            $form->select('cartoon_id','作品名称')->options($selectOption)->setWidth(3)->required()->default(Request::get('cid',''));
            $form->display('cartoon_id', '作品名称')->setWidth(3)->with(function () use ($selectOption) {
                return $selectOption[Request::get('cid', '')];
            });


            $form->hidden('copyright_id');
            $form->hidden('producer_id');
            $form->hidden('producer_rate');
            $form->hidden('month')->default(Request::get('month', ''));
            $form->hidden('cartoon_id')->default(Request::get('cid', ''));

//            $form->time('month','年份月份')->format('YYYY-MM');
            $form->display('month', '年/月份')->setWidth(1)->with(function () {
                return Request::get('month', '');
            });


            $options = DB::table('pfs')->get()->toArray();
            $selectOption = [];
            foreach ($options as $option) {
                $selectOption[$option->id] = $option->pfname;
            }
            $checked = [];
            $checked2 = [];
            foreach ($pfdatas as $pfdata) {
                if ($pfdata->is_line == 0) {
                    array_push($checked, $pfdata->pf_id);
                }
                if ($pfdata->is_nopay == 1) {
                    array_push($checked2, $pfdata->pf_id);
                }
            }

//            $form->checkbox('is_line', '未上线渠道')->options($selectOption)->checked($checked);
            $form->listbox('is_line', '未上线渠道（右边为未上线）')->options($selectOption)->checked($checked);

//            $form->checkbox('is_nopay', '未计费渠道')->options($selectOption)->checked($checked2);
            $form->listbox('is_nopay', '未计费渠道（右边为未计费）')->options($selectOption)->checked($checked2);
        });

        $form->tab('制作方/版权方', function ($form) use ($pfdatas) {
            $form->html('<span style="color:#ff756b;font-size: 18px">版权方信息</span>');
            $form->currency('copyright_money', '版权成本')->symbol('￥')->default($pfdatas[0]->copyright_money);


            $cartoon_info = Db::table('cartoons')->find($pfdatas[0]->cartoon_id);
            $form->rate('rate1', '周边分成比例')->setWidth(3)->default($pfdatas[0]->rate1);

            $form->rate('crate2', '默认版权成本2比例')->setWidth(3)->default($cartoon_info->crate2)->disable()->help('请在作品列表中编辑设置，此处修改无效');


            if (Admin::user()->id == 1 || $pfdatas[0]->rate_sure == 0) {
                $form->rate('rate2', '当月版权成本2比例')->setWidth(3)->default($pfdatas[0]->rate2);
            } else {
                $form->rate('rate2', '当月版权成本2比例')->setWidth(3)->default($pfdatas[0]->rate2)->disable();
            }
            $form->divide();
            $form->html('<span style="color:#6effe6;font-size: 18px">制作方信息</span>');
            $form->currency('payment', '制作方稿费')->symbol('￥')->default($pfdatas[0]->payment);
            $form->currency('pay_other', '制作杂费')->symbol('￥')->default($pfdatas[0]->pay_other);
            if (Admin::user()->id == 1 || $pfdatas[0]->rate_sure == 0) {
                $form->rate('rate3', '内容使用成本比')->setWidth(3)->default($pfdatas[0]->rate3);
            } else {
                $form->rate('rate3', '内容使用成本比')->setWidth(3)->default($pfdatas[0]->rate3)->disable();
            }

            $form->rate('crate4', '默认保底分成2比例')->setWidth(3)->default($cartoon_info->crate4)->disable()->help('请在作品列表中编辑设置，此处修改无效');
            if (Admin::user()->id == 1 || $pfdatas[0]->rate_sure == 0) {
                $form->rate('rate4', '当月保底分成2比例')->setWidth(3)->default($pfdatas[0]->rate4);
            } else {
                $form->rate('rate4', '当月保底分成2比例')->setWidth(3)->default($pfdatas[0]->rate4)->disable();
            }

            $form->rate('crate5', '默认制作分成2比例')->setWidth(3)->default($cartoon_info->crate5)->disable()->help('请在作品列表中编辑设置，此处修改无效');
            if (Admin::user()->id == 1 || $pfdatas[0]->rate_sure == 0) {
                $form->rate('rate5', '当月制作分成2比例')->setWidth(3)->default($pfdatas[0]->rate5);
            } else {
                $form->rate('rate5', '当月制作分成2比例')->setWidth(3)->default($pfdatas[0]->rate5)->disable();
            }

            if (Admin::user()->id == 1 || $pfdatas[0]->rate_sure == 0) {
                $form->radio('rate_sure', '比例确认状态')->options(['0' => '预计', '1' => '确认'])->default($pfdatas[0]->rate_sure)->help('确认状态下的数据才显示给合作方');
            } else {
                $form->radio('rate_sure', '比例确认状态')->options(['0' => '预计', '1' => '确认'])->default($pfdatas[0]->rate_sure)->help('确认状态下的数据才显示给合作方')->disable();
            }

        });

        //找出所有的渠道方，循环添加表单
        $pfs = DB::table('pfs')->get()->toArray();

        foreach ($pfs as $pf) {
            if ($pf->type == 3) {
                $form->tab($pf->pfname, function ($form) use ($pf, $datas) {
                    if (array_key_exists($pf->id, $datas)) {
                        $data = $datas[$pf->id];
                        $form->hidden('pf_id[' . $pf->id . ']')->value($pf->id);
                        $form->currency('min_money[' . $pf->id . ']', '合作方明细金额')->symbol('￥')->default($data->min_money);
                        $form->hidden('plan_money_type[' . $pf->id . ']')->value(1);
                        $form->hidden('plan_money_status[' . $pf->id . ']')->value(1);
                        $form->hidden('infact_money_type[' . $pf->id . ']')->value(1);
                        $form->hidden('infact_money_status[' . $pf->id . ']')->value(1);

                    } else {
                        $form->hidden('pf_id[' . $pf->id . ']')->value($pf->id);
                        $form->currency('min_money[' . $pf->id . ']', '合作方明细金额')->symbol('￥');
                        $form->hidden('plan_money_type[' . $pf->id . ']')->value(1);
                        $form->hidden('plan_money_status[' . $pf->id . ']')->value(1);
                        $form->hidden('infact_money_type[' . $pf->id . ']')->value(1);
                        $form->hidden('infact_money_status[' . $pf->id . ']')->value(1);
                    }
                });

            } else {
                $form->tab($pf->pfname, function ($form) use ($pf, $datas) {
                    if (array_key_exists($pf->id, $datas)) {
                        $data = $datas[$pf->id];
                        $form->hidden('pf_id[' . $pf->id . ']')->value($pf->id);
                        $form->currency('plan_money[' . $pf->id . ']', '<span style="color: #85202d">预估分成金额</span>')->symbol('￥')->default($data->plan_money)->help('保底渠道建议不填写此项');
                        $form->radio('plan_money_type[' . $pf->id . ']', '预估分成金额类型')
                            ->options([1 => '预估数据', '3' => '已确认按月分摊数据/已确认数据'])->default($data->plan_money_type);
                        $form->radio('plan_money_status[' . $pf->id . ']', '预估分成金额状态')
                            ->options([1 => '不可结算', 2 => '等待到账', '3' => '已到账可结算'])->default($data->plan_money_status);
                        $form->currency('infact_money[' . $pf->id . ']', '<span style="color: #4d8545">实际确认金额</span>')->symbol('￥')->default($data->infact_money)->help('保底渠道建议不填写此项');
                        $form->radio('infact_money_type[' . $pf->id . ']', '实际确认金额类型')
                            ->options([1 => '预估数据', 2 => '已确认按月分摊数据', '3' => ' 已确认可结算数据'])->default($data->infact_money_type);
                        $form->radio('infact_money_status[' . $pf->id . ']', '实际确认金额状态')
                            ->options([1 => '不可结算', 2 => '等待到账', '3' => '已到账可结算'])->default($data->infact_money_status);
                        $form->currency('min_money[' . $pf->id . ']', '保底金额')->symbol('￥')->default($data->min_money);
                        if ($pf->type == 2) {
//                        $form->display('per_min_money['.$pf->id.']', '保底均分/月')->setWidth(1);
                            $form->display('per_min_money[' . $pf->id . ']', '保底均分/月')->setWidth(1)->with(function () use ($data) {
                                return $data->per_min_money;
                            });
                        }
                    } else {
                        $form->hidden('pf_id[' . $pf->id . ']')->value($pf->id);
                        $form->currency('plan_money[' . $pf->id . ']', '<span style="color: #85202d">预估分成金额</span>')->symbol('￥')->help('保底渠道建议不填写此项');
                        $form->radio('plan_money_type[' . $pf->id . ']', '预估分成金额类型')
//                    ->options([1 => '预估数据', 2 => '已确认按月分摊数据', '3' => ' 已确认可结算数据'])->default('1');
                            ->options([1 => '预估数据', '3' => '已确认按月分摊数据/已确认数据'])->default('1');
                        $form->radio('plan_money_status[' . $pf->id . ']', '预估分成金额状态')
                            ->options([1 => '不可结算', 2 => '等待到账', '3' => '已到账可结算'])->default('1');
                        $form->currency('infact_money[' . $pf->id . ']', '<span style="color: #4d8545">实际确认金额</span>')->symbol('￥')->help('保底渠道建议不填写此项');
                        $form->radio('infact_money_type[' . $pf->id . ']', '实际确认金额类型')
                            ->options([1 => '预估数据', 2 => '已确认按月分摊数据', '3' => ' 已确认可结算数据'])->default('1');
                        $form->radio('infact_money_status[' . $pf->id . ']', '实际确认金额状态')
                            ->options([1 => '不可结算', 2 => '等待到账', '3' => '已到账可结算'])->default('1');
                        $form->currency('min_money[' . $pf->id . ']', '保底金额')->symbol('￥');

                        if ($pf->type == 2) {
                            $form->display('per_min_money[' . $pf->id . ']', '保底均分/月')->setWidth(1)->with(function () {
                                return 0;
                            });
                        }
                    }

                });
            }
        }


        $form->footer(function ($footer) {
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
        });

        return $content
            ->header('修改')
            ->description('.')
            ->body($form);
    }

    public function storeAll(\Illuminate\Http\Request $request)
    {
        $cartoon_id = $request->cartoon_id;
        $cartoon = DB::table('cartoons')->where('id', $cartoon_id)->select('copyright_id', 'producer_id', 'producer_rate')->first();
        $pf_ids = $request->pf_id;
        foreach ($pf_ids as $pf_id) {
            $pfdata = new Pfdata();

            $pfdata->pf_id = $pf_id;
            $pfdata->cartoon_id = $cartoon_id;
            $pfdata->copyright_id = $cartoon->copyright_id;
            $pfdata->producer_id = $cartoon->producer_id;
            $pfdata->producer_rate = $cartoon->producer_rate;
            $pfdata->month = $request->month;
            $pfdata->copyright_money = $request->copyright_money ?? 0;
            $pfdata->plan_money = $request->plan_money[$pf_id] ?? 0;
            $pfdata->plan_money_type = $request->plan_money_type[$pf_id];
            $pfdata->plan_money_status = $request->plan_money_status[$pf_id];
            $pfdata->infact_money = $request->infact_money[$pf_id] ?? 0;
            $pfdata->infact_money_type = $request->infact_money_type[$pf_id];
            $pfdata->infact_money_status = $request->infact_money_status[$pf_id];
            $pfdata->min_money = $request->min_money[$pf_id] ?? 0;
            $pfdata->payment = $request->payment ?? 0;
            $pfdata->pay_other = $request->pay_other ?? 0;
            $pfdata->pay_other = $request->pay_other ?? 0;
            $pfdata->is_line = \in_array($pf_id, $request->is_line) ? 0 : 1;
            $pfdata->is_nopay = \in_array($pf_id, $request->is_nopay) ? 1 : 0;
            $pfdata->rate1 = $request->rate1 ?? 0;
            $pfdata->rate2 = $request->rate2 ?? 0;
            $pfdata->rate3 = $request->rate3 ?? 0;
            $pfdata->rate4 = $request->rate4 ?? 0;
            $pfdata->rate5 = $request->rate5 ?? 0;
            $pfdata->rate_sure = $request->rate_sure ?? 1;

            $where = [
                ['month', '=', $request->month],
                ['cartoon_id', '=', $request->cartoon_id],
                ['pf_id', '=', $pf_id],
            ];
            $exists = DB::table('pfdatas')->where($where)->first();

            if ($exists) {
                if (Admin::user()->id == 1 || $exists->rate_sure == 0) {
                    DB::table('pfdatas')
                        ->where('id', $exists->id)
                        ->update([
                            'pf_id' => $pf_id,
                            'cartoon_id' => $cartoon_id,
                            'copyright_id' => $cartoon->copyright_id,
                            'producer_id' => $cartoon->producer_id,
                            'producer_rate' => $cartoon->producer_rate,
                            'month' => $request->month,
                            'copyright_money' => $request->copyright_money ?? 0,
                            'plan_money' => $request->plan_money[$pf_id] ?? 0,
                            'plan_money_type' => $request->plan_money_type[$pf_id],
                            'plan_money_status' => $request->plan_money_status[$pf_id],
                            'infact_money' => $request->infact_money[$pf_id] ?? 0,
                            'infact_money_type' => $request->infact_money_type[$pf_id],
                            'infact_money_status' => $request->infact_money_status[$pf_id],
                            'min_money' => $request->min_money[$pf_id] ?? 0,
                            'payment' => $request->payment ?? 0,
                            'pay_other' => $request->pay_other ?? 0,
                            'is_line' => \in_array($pf_id, $request->is_line) ? 0 : 1,
                            'is_nopay' => \in_array($pf_id, $request->is_nopay) ? 1 : 0,
                            'rate1' => $request->rate1 ?? 0,
                            'rate2' => $request->rate2 ?? 0,
                            'rate3' => $request->rate3 ?? 0,
                            'rate4' => $request->rate4 ?? 0,
                            'rate5' => $request->rate5 ?? 0,
                            'rate_sure' => $request->rate_sure ?? 1,

                        ]);
                } else {
                    DB::table('pfdatas')
                        ->where('id', $exists->id)
                        ->update([
                            'pf_id' => $pf_id,
                            'cartoon_id' => $cartoon_id,
                            'copyright_id' => $cartoon->copyright_id,
                            'producer_id' => $cartoon->producer_id,
                            'producer_rate' => $cartoon->producer_rate,
                            'month' => $request->month,
                            'copyright_money' => $request->copyright_money ?? 0,
                            'plan_money' => $request->plan_money[$pf_id] ?? 0,
                            'plan_money_type' => $request->plan_money_type[$pf_id],
                            'plan_money_status' => $request->plan_money_status[$pf_id],
                            'infact_money' => $request->infact_money[$pf_id] ?? 0,
                            'infact_money_type' => $request->infact_money_type[$pf_id],
                            'infact_money_status' => $request->infact_money_status[$pf_id],
                            'min_money' => $request->min_money[$pf_id] ?? 0,
                            'payment' => $request->payment ?? 0,
                            'pay_other' => $request->pay_other ?? 0,
                            'is_line' => \in_array($pf_id, $request->is_line) ? 0 : 1,
                            'is_nopay' => \in_array($pf_id, $request->is_nopay) ? 1 : 0,
                            'rate1' => $request->rate1 ?? 0,
//                            'rate2' => $request->rate2 ?? 0,
//                            'rate3' => $request->rate3 ?? 0,
//                            'rate4' => $request->rate4 ?? 0,
//                            'rate5' => $request->rate5 ?? 0,
//                            'rate_sure' => $request->rate_sure ?? 1,

                        ]);
                }

            } else {
                $pfdata->save();
            }


        }

        $success = new MessageBag([
            'title' => '提示信息',
            'message' => '批量修改成功',
        ]);

        if (Cache::has('income_query_params')) {
            return redirect('admin/incomes?' . http_build_query(Cache::get('income_query_params')))->with(compact('success'));
        }
        return redirect('admin/incomes')->with(compact('success'));
    }
}
