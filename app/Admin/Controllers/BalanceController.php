<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\CheckRow;
use App\Admin\Extensions\ExcelExpoter;
use App\Admin\Extensions\ProducerRow;
use App\Admin\Extensions\Tools\MyButton;
use App\Console\Commands\BillAccount;
use App\Models\Balance;
use App\Http\Controllers\Controller;
use App\Models\Copyright;
use App\Models\Makeoutcome;
use App\Models\Producer;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\MessageBag;

class BalanceController extends Controller
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
        return $content
            ->header('版权方结算页面')
            ->description('.')
            ->body($this->grid());
    }

    public function approval()
    {
        $id = Request::get('id');
        $bill_info = DB::table('bills')->where('id', '=', $id)->first();
        if ($bill_info->type == 1) {
            $info = Copyright::query()->where('id','=', $bill_info->r_id)->first();
            $name = $info->company_name;
        } else {
            $info = Producer::query()->where('id','=', $bill_info->r_id)->first();
            $name = $info->producer_name;
        }

        $account_data = [
            'month' => $bill_info->month,
            'type' => $bill_info->type,
            'relation_id' => $bill_info->r_id,
            'money' => $bill_info->last_quarter_bills,
            'is_comfirm' => 1,
            'remark' => '申请结算',
            'created_at' => date('Y-m-d H:i:s',time()),
        ];

        $account_id = DB::table('accounts')->insertGetId($account_data);

        $data = [
            'month' => $bill_info->month,
            'money' => $bill_info->last_quarter_bills,
            'status' => 2,
            'type' => $bill_info->r_id,
            'name' => $name,
            'account_id' => $account_id,
            'created_at' => date('Y-m-d H:i:s',time()),
        ];

        DB::table('copyrightoutcomes')->insert($data);

        DB::table('bills')->where('id', '=', $id)->update([
            'last_quarter_bills' => 0,
            'doing_bills' => $bill_info->doing_bills + $bill_info->last_quarter_bills,
            'now_bills' => $bill_info->now_bills - $bill_info->last_quarter_bills,
        ]);


        return [
            'code' => 1,
        ];
    }

    //申请结算
    public function pro_approval()
    {
        $data = [
            'month' => Request::get('month'),
            'cartoon_id' => Request::get('cartoon_id'),
            'money' => Request::get('bqfcsj'),
            'status' => 2,
            'type' => 2,
            'name' => Request::get('name'),
            'created_at' => date('Y-m-d H:i:s',time()),
        ];

        if (date('Y-m',time()) <= Request::get('month') || (date('d',time()) > 10)) {
            return [
                'code' => -1,
            ];
        }

        DB::table('copyrightoutcomes')->insert($data);

        return [
            'code' => 1,
        ];
    }


    /**
     * 制作方结算页面
     * @param Content $content
     * @return Content
     */
    public function index2(Content $content)
    {
        return $content
            ->header('制作方结算页面')
            ->description('.')
            ->body($this->makeGrid());
    }

    protected function makeGrid()
    {
        $grid = new Grid(new Makeoutcome);

        /*筛选条件*/
        $grid->filter(function($filter){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();

            $filter->column(1/3, function ($filter) {
                $filter->equal('month','年/月份')->datetime(['format' => 'YYYY-MM']);


            });

            $filter->column(1/3, function ($filter) {

                $options = DB::table('cartoons')->get()->toArray();
                $selectOption = ['0'=>'全部作品'];
                foreach ($options as $option) {
                    $selectOption[$option->id] = $option->cartoon_name;
                }
                $filter->equal('cartoon_id','作品名称')->select($selectOption);
            });

            $filter->column(1/3, function ($filter) {
                $options = DB::table('producers')->get()->toArray();
                $selectOption = ['0'=>'全部公司'];
                foreach ($options as $option) {
                    $selectOption[$option->id] = $option->producer_name;
                }
                $filter->equal('producer_id','制作方')->select($selectOption);
            });

        });

        $grid->column('month','年月日期');
        $grid->column('cartoon_name', '作品名称');
        $grid->producer_name('制作方公司');
        $grid->column('bqfcsj', '可结算分成');
        $grid->column('status', '结算状态')->display(function($status){
            if($status == 1 || empty($status)) {
                return '未结算';
            }elseif($status == 2){
                return '申请中';
            }elseif($status == 3){
                return '已结算';
            }
        });

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();


            if ($actions->row->status == 1 || empty($actions->row->status)) {
                $actions->append(new ProducerRow($actions->row));
            }

        });

        $grid->exporter(new ExcelExpoter());
        $grid->disableCreateButton();
//        $grid->disableActions();
        $grid->disableRowSelector();

        return $grid;
    }



    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Balance);

//        dump(Admin::user()->isAdministrator());
//        dump(Admin::user()->roles[0]->name);
//        dump(Admin::user()->inRoles(['administrator', 'developer']));
        if (Admin::user()->inRoles(['版权方'])) {
                $grid->filter(function($filter){

                // 去掉默认的id过滤器
                $filter->disableIdFilter();

                $filter->column(1/2, function ($filter) {
                    $filter->equal('month','年/月份')->datetime(['format' => 'YYYY-MM']);


                });

                $filter->column(1/2, function ($filter) {

                    $options = DB::table('cartoons')->get()->toArray();
                    $selectOption = ['0'=>'全部作品'];
                    foreach ($options as $option) {
                        $selectOption[$option->id] = $option->cartoon_name;
                    }
                    $filter->equal('cartoon_id','作品名称')->select($selectOption);
                });

            });

            $grid->column('month','年月日期');
            $grid->column('cartoon_name', '作品名称');
            $grid->company_name('版权公司');
            $grid->column('bqfcsj', '可结算分成');
            $grid->column('status', '结算状态')->display(function($status){
                if($status == 1 || empty($status)) {
                    return '未结算';
                }elseif($status == 2){
                    return '申请中';
                }elseif($status == 3){
                    return '已结算';
                }
            });

            $grid->actions(function ($actions) {
                $actions->disableDelete();
                $actions->disableEdit();
                $actions->disableView();

                if ($actions->row->status == 1 || empty($actions->row->status)) {
                    $actions->append(new CheckRow($actions->row));
                }
            });

            return $grid;


        }

        /*筛选条件*/
        $grid->filter(function($filter){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();

            $filter->column(1/3, function ($filter) {
                $filter->equal('month','年/月份')->datetime(['format' => 'YYYY-MM']);


            });

            $filter->column(1/3, function ($filter) {

                $options = DB::table('cartoons')->get()->toArray();
                $selectOption = ['0'=>'全部作品'];
                foreach ($options as $option) {
                    $selectOption[$option->id] = $option->cartoon_name;
                }
                $filter->equal('cartoon_id','作品名称')->select($selectOption);
            });

            $filter->column(1/3, function ($filter) {
                $options = DB::table('copyrights')->get()->toArray();
                $selectOption = ['0'=>'全部公司'];
                foreach ($options as $option) {
                    $selectOption[$option->id] = $option->company_name;
                }
                $filter->equal('copyright_id','版权公司')->select($selectOption);
            });

        });

        $grid->column('month','年月日期');
        $grid->column('cartoon_name', '作品名称');
        $grid->company_name('版权公司');
        $grid->column('bqfcsj', '可结算分成');
        $grid->column('status', '结算状态')->display(function($status){
            if($status == 1 || empty($status)) {
                return '未结算';
            }elseif($status == 2){
                return '申请中';
            }elseif($status == 3){
                return '已结算';
            }
        });

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();

            if ($actions->row->status == 1 || empty($actions->row->status)) {
                $actions->append(new CheckRow($actions->row));
            }
        });

        $grid->exporter(new ExcelExpoter());
        $grid->disableCreateButton();
//        $grid->disableActions();
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
            $cartoon_id = $form->cartoon_id;

            $cartoon = DB::table('cartoons')->where('id',$cartoon_id)->select('copyright_id')->first();
            $form->copyright_id = $cartoon->copyright_id;

            $where = [
                ['month', '=', $form->month],
                ['cartoon_id', '=', $form->cartoon_id],
                ['pf_id', '=', $form->pf_id],
            ];
            $exists = DB::table('pfdatas')->where($where)->first();
            if ($exists) {
                $error = new MessageBag([
                    'title'   => '提示信息',
                    'message' => '该漫画下同日期同渠道已存在数据，不能继续添加',
                ]);

                DB::table('pfdatas')
                    ->where('id', $exists->id)
                    ->update([
                        'pf_id' => $form->pf_id,
                        'cartoon_id' => $form->cartoon_id,
                        'copyright_id' => $form->copyright_id,
                        'month' => $form->month,
                        'copyright_money' => $form->copyright_money,
                        'plan_money' => $form->plan_money,
                        'plan_money_type' => $form->plan_money_type,
                        'plan_money_status' => $form->plan_money_status,
                        'infact_money' => $form->infact_money,
                        'infact_money_type' => $form->infact_money_type,
                        'infact_money_status' => $form->infact_money_status,
                        'min_money' => $form->min_money,
                    ]);
                return redirect('/admin/incomes');
            }

        });

        $options = DB::table('pfs')->select('id','pfname')->get();
        $selectOption = [];
        foreach ($options as $option) {
            $selectOption[$option->id] = $option->pfname;
        }

        $pf_id = Request::get('pfid','');
        $form->select('pf_id','渠道方')->options($selectOption)->default($pf_id)->setWidth(2)->required();

        $options = DB::table('cartoons')->select('id','cartoon_name')->get();
        $selectOption = [];
        foreach ($options as $option) {
            $selectOption[$option->id] = $option->cartoon_name;
        }
        $form->select('cartoon_id','作品名称')->options($selectOption)->setWidth(3)->required();

        $form->hidden('copyright_id');


        $form->time('month','年份月份')->format('YYYY-MM');
        $form->currency('copyright_money','版权成本')->symbol('￥');
        $form->currency('plan_money','预估分成金额')->symbol('￥');
        $form->radio('plan_money_type','预估分成金额类型')
            ->options([1 => '预估数据', 2 => '已确认按月分摊数据', '3' => ' 已确认可结算数据'])->default('1');
        $form->radio('plan_money_status','预估分成金额状态')
            ->options([1 => '不可结算', 2 => '等待到账', '3' => '已到账可结算'])->default('1');
        $form->currency('infact_money','实际确认金额')->symbol('￥');
        $form->radio('infact_money_type','实际确认金额类型')
            ->options([1 => '预估数据', 2 => '已确认按月分摊数据', '3' => ' 已确认可结算数据'])->default('1');
        $form->radio('infact_money_status','实际确认金额状态')
            ->options([1 => '不可结算', 2 => '等待到账', '3' => '已到账可结算'])->default('1');
        $form->currency('min_money','保底金额')->symbol('￥');

        return $form;
    }
}
