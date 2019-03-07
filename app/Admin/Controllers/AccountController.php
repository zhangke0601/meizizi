<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\BalanceButton;
use App\Admin\Extensions\CheckRow;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Copyright;
use App\Models\PartnerDetail;
use App\Models\Producer;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    use HasResourceActions;

    /**
     * 合作方结算列表
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('合作方结算列表')
            ->description('description')
            ->body($this->grid());
    }

    /**
     * 合作方申请结算
     * 数据来源 App\Models\Account::ajax_paginate()
     *
     * @see Account::ajax_paginate()
     * @param Content $content
     * @return Content
     */
    public function make(Content $content)
    {
        $content->header('已经可以结算')->description('对金额无异议的话请寄给我们结算单及发票，如果有异议，请速与我们联系。
我们会在收到发票后付款完成结算');
        $content->body($this->makeGridNew());
        return $content;
    }

    protected function makeGridNew()
    {
        $grid = new Grid(new Bill);
        $whereOr1 = $whereOr2 = [];

        $grid->model()->where('month', '=', date('Y-m'));
        if (Admin::user()->isRole('版权方')) {
            $copyright = Copyright::query()->where('admin_user_id', '=', Admin::user()->id)->pluck('id')->toArray();
            $whereOr1 = [
                ['type', '=', 1],
                ['r_id', '=', $copyright[0]]
            ];
        }

        if (Admin::user()->isRole('制作方')) {
            $producer = Producer::query()->where('admin_user_id', '=', Admin::user()->id)->pluck('id')->toArray();
            $whereOr2 = [
                ['type', '=', 2],
                ['r_id', '=', $producer[0]]
            ];
        }

        $grid->model()->where(function ($query) use ($whereOr1, $whereOr2) {
            $query->where($whereOr1)
                ->orWhere($whereOr2);
        });

        $grid->disableFilter();

        $grid->model()->with(['copyright', 'producer'])->orderby('now_bills', 'desc');

//        $grid->id('Id');
        $grid->month('时间');
        $grid->type('类型')->display(function ($type) {
            if ($type == 1) {
                return '版权方';
            } else {
                return '制作方';
            }
        });
        $grid->r_id('合作方')->display(function ($r_id) {
            if ($this->type == 1) {
                return $this->copyright->company_name;
            } else {
                return $this->producer->producer_name;
            }
        });
        $grid->should_bills('应结算（总和）');
        $grid->have_bills('已经结算');
        $grid->doing_bills('结算中');
//        $grid->now_bills('当月应结算');
        $grid->last_quarter_bills('上季度及之前应结算');
//        $grid->created_at('Created at');
//        $grid->updated_at('计算时间');

        $grid->disableCreateButton();
        $grid->disableRowSelector();

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();

            if ($actions->row->last_quarter_bills > 0) {
                $actions->append(new BalanceButton($actions->row));
            }

        });
        $grid->disableExport();
//        $grid->exporter(new ExcelExpoter());

        return $grid;
    }

    /**
     * make的body部分
     *
     * @return Grid
     */
    protected function makeGrid()
    {
        $grid = new Grid(new Account);
        $grid->disableCreateButton();
        $grid->disableFilter();
        $grid->disableExport();
        $grid->disableRowSelector();

        $grid->month('年月份');
        $grid->name('合作方');
        $grid->money('结算金额');
        $grid->remark('备注');

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();

//            dump($actions->row->status);
            if ($actions->row->status == 1 || empty($actions->row->status)) {
                $actions->append(new CheckRow($actions->row));
            }
        });

        return $grid;
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
     * 编辑结算
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('编辑结算')
            ->description('description')
            ->body($this->formEdit($id)->edit($id));
    }

    /**
     * 创建结算
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('创建结算')
            ->description('描述')
            ->body($this->form());
    }

    /**
     * partner data——detail
     *
     * @param Content $content
     * @return Content
     */
    public function dataDetail(Content $content)
    {
        $content->header();

        $grid = new Grid(new PartnerDetail());
        $grid->disableCreateButton();
//        $grid->disableFilter();

        /*筛选条件*/
        $grid->filter(function ($filter) {

            // 去掉默认的id过滤器
            $filter->disableIdFilter();

            $filter->column(1 / 3, function ($filter) {
                $filter->equal('month', '起始月份')->datetime(['format' => 'YYYY-MM']);

            });
            $filter->column(1 / 3, function ($filter) {
                $filter->equal('month2', '结束月份')->datetime(['format' => 'YYYY-MM']);

            });

            $filter->column(1 / 3, function ($filter) {
                $whereOr1 = [];
                $whereOr2 = [];
                if (Admin::user()->isRole('版权方')) {
                    $copyright = Copyright::query()->where('admin_user_id', '=', Admin::user()->id)->pluck('id')->toArray();
                    $whereOr1[] = ['copyright_id', '=', $copyright[0]];
                }
                if (Admin::user()->isRole('制作方')) {
                    $producer = Producer::query()->where('admin_user_id', '=', Admin::user()->id)->pluck('id')->toArray();
                    $whereOr2[] = ['producer_id', '=', $producer[0]];
                }
                $options = DB::table('cartoons')
                    ->where(function ($query) use ($whereOr1, $whereOr2) {
                        $query->where($whereOr1)
                            ->orWhere($whereOr2);
                    })
                    ->get()->toArray();
                $selectOption = ['0' => '全部作品'];
                foreach ($options as $option) {
                    $selectOption[$option->id] = $option->cartoon_name;
                }
                $filter->equal('cartoon_id', '作品名称')->select($selectOption);
            });

        });


        $grid->column('month', '年月日期');
        $grid->column('cartoon_name', '作品名称');
        if (Admin::user()->isRole('版权方') || Admin::user()->isAdministrator()) {
            $grid->company_name('版权方');
        }
        if (Admin::user()->isRole('制作方') || Admin::user()->isAdministrator()) {
            $grid->producer_name('制作方');
        }

        $pf_id = \Illuminate\Support\Facades\Request::get('pf_id');
        $month1 = \Illuminate\Support\Facades\Request::get('month');
        $month2 = \Illuminate\Support\Facades\Request::get('month2');
        $cartoon_id = \Illuminate\Support\Facades\Request::get('cartoon_id');
        if ($pf_id) {
            $pfs = DB::table('pfs')->where('id', '=', $pf_id)->get()->toArray();
        } else {
            if (Admin::user()->isRole('版权方') || Admin::user()->isAdministrator()) {
                $pfs = DB::table('pfs')->where('type', '=', 1)->orWhere('type', '=', 3)->orderBy('type', 'asc')->get()->toArray();
            } else {
                $pfs = DB::table('pfs')->where('type', '=', 1)->orderBy('type', 'asc')->get()->toArray();
            }
        }
//        dump($pfs);
        foreach ($pfs as $pf) {
            if (in_array($pf->pfname, ['奇热', '看漫画', '多蕴', '微博动漫', '慢看（分成）'])) {
                continue;
            }
            $sum1 = 0;
            $sum2 = 0;
            $where = [];
            if ($month1) {
                $where[] = ['month', '>=', $month1];
            }
            if ($month2) {
                $where[] = ['month', '<=', $month2];
            }

            if ($cartoon_id) {
                $where[] = ['cartoon_id', '=', $cartoon_id];
            }
            if (Admin::user()->isRole('版权方') || Admin::user()->isAdministrator()) {
                $copyright = Copyright::query()->where('admin_user_id', '=', Admin::user()->id)->pluck('id')->toArray();
                $sum1 = DB::table('pfdatas')
                    ->where('pf_id', '=', $pf->id)
                    ->where('infact_money_status', '=', 3)
                    ->where('copyright_id', '=', $copyright[0] ?? 0)
                    ->where($where)
                    ->sum('infact_money');
            }


            if (Admin::user()->isRole('制作方') || Admin::user()->isAdministrator()) {
                $copyright = Producer::query()->where('admin_user_id', '=', Admin::user()->id)->pluck('id')->toArray();
                $sum2 = DB::table('pfdatas')
                    ->where('pf_id', '=', $pf->id)
                    ->where('infact_money_status', '=', 3)
                    ->where('producer_id', '=', $copyright[0] ?? 0)
                    ->where($where)
                    ->sum('infact_money');
            }

            if ($pf->type == 3) {
                $sum1 = 100;
                $sum2 = 100;
            }

            if ($sum1 < 50 && $sum2 < 50 && !Admin::user()->isAdministrator()) {
                continue;
            }

            $grid->column($pf->sortname, '渠道' . $pf->id)
                ->display(function ($money) use ($pf) {
                    if ($money['money'] < 50) {
                        return '';
                    }
                    return $money['money'];
                });
        }

        if (Admin::user()->isRole('制作方') || Admin::user()->isAdministrator()) {
            $grid->column('min_money', '其他')->display(function ($min_money) {
                return sprintf('%.2f', $min_money);
//                return $min_money;
            });
        }

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });
        $grid->disableExport();
        $grid->disableActions();
        $grid->disableRowSelector();

        $content->body($grid);
        return $content;
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Account);

        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();

            $filter->column(1 / 3, function ($filter) {
                $filter->equal('month', '年/月份')->datetime(['format' => 'YYYY-MM']);

            });
            $filter->column(1 / 3, function ($filter) {

                $selectOption = ['1' => '版权方', '2' => '制作方'];

                $filter->equal('type', '合作类型')->select($selectOption);

            });
            $filter->column(1 / 3, function ($filter) {

                $filter->equal('name', '合作方');
            });
        });

        $grid->id('Id');
        $grid->month('年月份');
        $grid->type('类型')->display(function ($type) {
            if ($type == 1) {
                return '版权方';
            } else {
                return '制作方';
            }
        });
        $grid->name('合作方');
        $grid->money('结算金额');
        $grid->is_comfirm('是否确认')->display(function ($is_comfirm) {
            if ($is_comfirm == 1) {
                return '是';
            } else {
                return '否';
            }
        });
        $grid->remark('备注');
//        $grid->created_at('创建时间');
//        $grid->updated_at('最后修改时间');

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
        $show = new Show(Account::findOrFail($id));

        $show->id('Id');
        $show->month('Month');
        $show->type('Type');
        $show->relation_id('Relation id');
        $show->money('Money');
        $show->is_comfirm('Is comfirm');
        $show->created_at('Created at');
        $show->updated_at('Updated at');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Account);

        $script = <<<EOT
$(document).off('change', ".relation_id");
$(document).on('change', ".relation_id", function () {
    var month = $(".month").val();
    var type = $(".type").val();
    var relation_id = $(".relation_id").val();
    $.get("/api/detail?month="+month+"&type="+type+"&rid="+relation_id, function (data) {
       console.log(data);
       swal('当前未结算金额为'+data.total, '', 'info');
       $(".money").val(data.total);
    });
    })

EOT;
        Admin::script($script);

        $form->saving(function (Form $form) {
            if ($form->is_comfirm == 'on') {
                if ($form->type == 1) {
                    DB::table('pfdatas')
                        ->where('month', '<=', $form->month)
                        ->where('copyright_id', '=', $form->relation_id)
                        ->where('infact_money_status', '=', 3)
                        ->update([
                            'bill_month' => $form->month,
                            'is_bill' => 1,
                        ]);
                } elseif ($form->type == 2) {
                    DB::table('pfdatas')
                        ->where('month', '<=', $form->month)
                        ->where('producer_id', '=', $form->relation_id)
                        ->where('infact_money_status', '=', 3)
                        ->update([
                            'bill_month2' => $form->month,
                            'is_bill2' => 1,
                        ]);
                }
            }
        });

        $form->date('month', '年月份')->format('YYYY-MM')->required();
        $form->select('type', '合作方类型')->options(['1' => '版权方', '2' => '制作方'])->load('relation_id', '/api/relation')->required()->setWidth(4);
        $form->select('relation_id', '版权方/制作方')->required()->help('请先选择合作方类型')->setWidth(4);

        $form->currency('money', '结算金额')->symbol('￥')->rules('numeric|min:0')->required();

        $state = [
            'on' => ['text' => '确认', 'value' => 1],
            'off' => ['text' => '不确认', 'value' => 0],
        ];
        $form->switch('is_comfirm', '是否确认')->states()->default(0)->help('一旦结算将无法修改金额');

        $form->textarea('remark', '备注');

        $form->footer(function ($footer) {

            // 去掉`查看`checkbox
            $footer->disableViewCheck();

            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();

        });

        return $form;
    }


    protected function formEdit($id)
    {
        $form = new Form(new Account);

        $form->setAction('/admin/account/edit');

        $account = Account::query()->find($id);

        $form->hidden('id', 'id')->default($id);

        $form->display('month', '年月份')->setWidth(1);
        $form->display('type', '合作方类型')->setWidth(2)->with(function ($type) {
            if ($type == 1) {
                return '版权方';
            } else {
                return '制作方';
            }
        });

        if ($account->type == 1) {
            $name = Copyright::query()->where('id', '=', $account->relation_id)->pluck('company_name')->toArray();
        } else {
            $name = Producer::query()->where('id', '=', $account->relation_id)->pluck('producer_name')->toArray();
        }

        $form->display('relation_id', '版权方/制作方')->setWidth(3)->with(function ($relation_id) use ($name) {
            return $name[0];
        });

        $form->currency('money', '结算金额')->symbol('￥')->rules('min:0')->required();

        $state = [
            'on' => ['text' => '确认', 'value' => 1],
            'off' => ['text' => '不确认', 'value' => 0],
        ];

        $form->switch('is_comfirm', '是否确认')->states()->default(0)->help('一旦结算将无法修改金额');

        $form->textarea('remark', '备注');

        $form->footer(function ($footer) {

            // 去掉`查看`checkbox
            $footer->disableViewCheck();

            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();

        });

        return $form;
    }

    public function editOne(Request $request)
    {
        DB::table('accounts')->where('id', $request->id)
            ->update([
                'money' => $request->money,
                'is_comfirm' => $request->is_comfirm === 'on' ? 1 : 0,
                'remark' => $request->remark,
            ]);

        admin_toastr('修改成功', 'success');
        return redirect('/admin/accounts');
    }
}
