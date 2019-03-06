<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\ExcelExpoter;
use App\Models\Account;
use App\Models\Bill;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Collapse;
use Encore\Admin\Widgets\InfoBox;
use Encore\Admin\Widgets\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Input;

class BillController extends Controller
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
        Cache::put('bills_query_params', Input::get(), '600');
        return $content
            ->header('结算明细')
            ->description('description')
            ->body($this->grid());
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
        if(Cache::has('bills_query_params')){
            $content->breadcrumb(
                ['text' => '列表页', 'url' => '/bills?'.http_build_query(Cache::get('bills_query_params'))],
                ['text' => '详情']
            );
        }else{
            $content->breadcrumb(
                ['text' => '列表页', 'url' => '/bills'],
                ['text' => '详情']
            );
        }
        return $content
            ->header('结算详情')
            ->description('description')
            ->body($this->detail($id))
            ->body($this->detail3($id))
            ->body($this->detail2($id));
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
        return $content
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    public function pfDetail(Content $content)
    {
        $content->breadcrumb(
            ['text' => '列表页', 'url' => '/bills'],
            ['text' => '渠道结算历史']
        );
        $account_id = \Illuminate\Support\Facades\Request::get('aid');
        $info = Account::findOrFail($account_id);
        if ($info->type == 1) {
            $results = DB::table('pfdatas')
                ->leftJoin('pfs', 'pfdatas.pf_id', '=', 'pfs.id')
                ->leftJoin('cartoons', 'pfdatas.cartoon_id', '=', 'cartoons.id')
                ->where('pfdatas.bill_month','=',$info->month)
                ->where('pfdatas.copyright_id','=',$info->relation_id)
                ->get()->toArray();

            $rows = [];
            if (!empty($results)) {
                foreach ($results as $result) {
                    $rows[] = [
                        $result->month, $result->cartoon_name, $result->pfname, $result->infact_money, $result->min_money, $result->bill_month
                    ];
                }
            }
        } elseif ($info->type == 2) {
            $results = DB::table('pfdatas')
                ->leftJoin('pfs', 'pfdatas.pf_id', '=', 'pfs.id')
                ->leftJoin('cartoons', 'pfdatas.cartoon_id', '=', 'cartoons.id')
                ->where('pfdatas.bill_month2','=',$info->month)
                ->where('pfdatas.producer_id','=',$info->relation_id)
                ->get()->toArray();

            $rows = [];
            if (!empty($results)) {
                foreach ($results as $result) {
                    $rows[] = [
                        $result->month, $result->cartoon_name, $result->pfname, $result->infact_money, $result->min_money, $result->bill_month
                    ];
                }
            }
        }


        $headers = ['时间', '结算作品', '渠道方', '渠道结算金额', '渠道保底金额', '结算日期'];

        $table = new Table($headers, $rows);

        $box = new Box('历史结算明细', $table->render());

        $box->removable();

        $box->collapsable();

        $box->style('info');

        $box->solid();

        $content->body($box);

        return $content;
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Bill);

        $grid->model()->where('month', '=', date('Y-m'));

        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();

            $filter->column(1/3, function ($filter) {
                $filter->equal('month','开始月份')->datetime(['format' => 'YYYY-MM']);
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('type','合作方')->select(['1'=>'版权方','2'=>'制作方']);
            });
        });

        $grid->model()->with(['copyright','producer'])->orderby('now_bills','desc');

//        $grid->id('Id');
        $grid->month('时间');
        $grid->type('类型')->display(function($type) {
            if ($type == 1) {
                return '版权方';
            } else {
                return '制作方';
            }
        });
        $grid->r_id('合作方')->display(function($r_id) {
            if ($this->type == 1) {
                return $this->copyright->company_name;
            } else {
                return $this->producer->producer_name;
            }
        });
//        $grid->copyright()->company_name('合作方');
        $grid->should_bills('应结算（总和）');
        $grid->have_bills('已经结算');
        $grid->doing_bills('结算中');
//        $grid->now_bills('当月应结算');
        $grid->last_quarter_bills('上季度及之前应结算');
//        $grid->created_at('Created at');
        $grid->updated_at('计算时间');

        $grid->disableCreateButton();
        $grid->disableRowSelector();

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();

        });

        $grid->exporter(new ExcelExpoter());

        return $grid;
    }

    protected function detail3($id)
    {
        $info = Bill::findOrFail($id);
        $headers = ['结算时间', '合作方', '结算金额', '结算状态', '操作'];
        $rows = [];
        if ($info->type == 1) {
            $results = DB::table('accounts')
                ->leftJoin('copyrights', 'accounts.relation_id', '=', 'copyrights.id')
                ->leftJoin('copyrightoutcomes', 'accounts.id', '=', 'copyrightoutcomes.account_id')
                ->where([
                    ['accounts.type', '=', 1],
                    ['accounts.relation_id', '=', $info->r_id],
                    ['accounts.month', '<=', $info->month],
                ])
                ->select('accounts.*', 'copyrights.company_name', 'copyrightoutcomes.name','copyrightoutcomes.status')
                ->get()->toArray();
            if (!empty($results)) {
                foreach ($results as $result) {
                    switch ($result->status) {
                        case 1:
                            $status = '未结算';
                            break;
                        case 2:
                            $status = '申请中';
                            break;
                        case 3:
                            $status = '已结算';
                            break;
                        default:
                            $status = '未申请';
                            break;
                    }
                    $rows[] = [
                        $result->month, $result->company_name, $result->money,$status, '<a href="/admin/bill?aid='.$result->id.'">查看具体渠道明细</a>'
                    ];
                }
            }
        } else {
            $results = DB::table('accounts')
                ->leftJoin('producers', 'accounts.relation_id', '=', 'producers.id')
                ->leftJoin('copyrightoutcomes', 'accounts.id', '=', 'copyrightoutcomes.account_id')
                ->where([
                    ['accounts.type', '=', 1],
                    ['accounts.relation_id', '=', $info->r_id],
                    ['accounts.month', '<=', $info->month],
                ])
                ->select('accounts.*', 'producers.producer_name', 'copyrightoutcomes.name','copyrightoutcomes.status')
                ->get()->toArray();
            if (!empty($results)) {
                foreach ($results as $result) {
                    switch ($result->status) {
                        case 1:
                            $status = '未结算';
                            break;
                        case 2:
                            $status = '申请中';
                            break;
                        case 3:
                            $status = '已结算';
                            break;
                        default:
                            $status = '未申请';
                            break;
                    }
                    $rows[] = [
                        $result->month, $result->producer_name, $result->money,$status, '<a href="/admin/bill?aid='.$result->id.'">查看具体渠道明细</a>'
                    ];
                }
            }
        }

        $table = new Table($headers, $rows);

        $box = new Box('历史结算明细', $table->render());

        $box->removable();

        $box->collapsable();

        $box->style('info');

        $box->solid();

        return $box;
    }

    protected function detail2($id)
    {
        $info = Bill::findOrFail($id);
        $headers = ['时间', '结算作品', '渠道方', '渠道结算金额', '渠道保底金额', '结算日期'];
        $rows = [];
//        dump($info);
        if ($info->type == 1) {
            $results = DB::table('pfdatas')
                ->leftJoin('pfs', 'pfdatas.pf_id', '=', 'pfs.id')
                ->leftJoin('cartoons', 'pfdatas.cartoon_id', '=', 'cartoons.id')
                ->where('pfdatas.copyright_id', '=', $info->r_id)
                ->where('pfdatas.month', '<=', $info->month)
//                ->where('pfdatas.infact_money_status', '=', 3)
                ->orderby('pfdatas.is_bill','asc')
                ->orderby('pfdatas.month','desc')
                ->get()->toArray();
            if (!empty($results)) {
                foreach ($results as $result) {
                    if ($result->infact_money_status == 3 || $result->min_money > 0) {
                        $rows[] = [
                            $result->month, $result->cartoon_name, $result->pfname, $result->infact_money_status==3?$result->infact_money:0, $result->min_money, $result->bill_month
                        ];
                    }
                }
            }
        } else {
            $results = DB::table('pfdatas')
                ->leftJoin('pfs', 'pfdatas.pf_id', '=', 'pfs.id')
                ->leftJoin('cartoons', 'pfdatas.cartoon_id', '=', 'cartoons.id')
                ->where('pfdatas.producer_id', '=', $info->r_id)
                ->where('pfdatas.month', '<=', $info->month)
//                ->where('pfdatas.infact_money_status', '=', 3)
                ->orderby('pfdatas.is_bill2','asc')
                ->orderby('pfdatas.month','desc')
                ->get()->toArray();
//            dump($info);
            if (!empty($results)) {
                foreach ($results as $result) {
//                    $rows[] = [
//                        $result->month, $result->cartoon_name, $result->pfname, $result->infact_money, $result->min_money, $result->bill_month2
//                    ];
                    if ($result->infact_money_status == 3 || $result->min_money > 0) {
                        $rows[] = [
                            $result->month, $result->cartoon_name, $result->pfname, $result->infact_money_status==3?$result->infact_money:0, $result->min_money, $result->bill_month
                        ];
                    }
                }
            }
        }

        $table = new Table($headers, $rows);

        $box = new Box('应结算金额明细', $table->render());

        $box->removable();

        $box->collapsable();

        $box->style('info');

        $box->solid();

        return $box;
    }

    /**
     * @param $id
     * @return string
     */
    protected function detail($id)
    {
        $info = Bill::findOrFail($id);
        $collapse = new Collapse();

        $content = '该合作方'.$info->month.'之前的总应结算金额为￥'.$info->should_bills.',之前已结算金额为￥'.$info->have_bills.',所以本次结算金额为￥'.$info->now_bills;
        $collapse->add('结算简介', $content);

        return $collapse->render();
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Bill);

        $form->text('month', 'Month');
        $form->switch('type', 'Type')->default(1);
        $form->number('r_id', 'R id');
        $form->decimal('should_bills', 'Should bills');
        $form->decimal('have_bills', 'Have bills');
        $form->decimal('now_bills', 'Now bills');

        return $form;
    }
}
