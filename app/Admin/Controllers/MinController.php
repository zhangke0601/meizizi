<?php

namespace App\Admin\Controllers;

use App\Models\Min;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class MinController extends Controller
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
            ->header('保底均分')
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
        return $content
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Min);

        $grid->filter(function($filter) {
            $filter->disableIdFilter();

            $filter->column(1/2, function ($filter) {
                $options = DB::table('cartoons')->get()->toArray();

                foreach ($options as $option) {
                    $selectOption[$option->id] = $option->cartoon_name;
                }
                $filter->equal('cartoon_id','作品名称')->select($selectOption);
            });
            $filter->column(1/2, function ($filter) {
                $options = DB::table('pfs')->where('type','=',2)->get()->toArray();

                foreach ($options as $option) {
                    $selectOption[$option->id] = $option->pfname;
                }
                $filter->equal('pf_id','保底渠道')->select($selectOption);
            });
        });

        $grid->id('Id');
        $grid->cartoon()->cartoon_name('作品名称');
        $grid->pf()->pfname('渠道');
        $grid->total_money('总金额');
        $grid->start('开始日期');
        $grid->end('结束日期');
        $grid->count('月份')->display(function($count){
            return '共'.$count.'个月';
        });
//        $grid->created_at('Created at');
//        $grid->updated_at('Updated at');

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
        $show = new Show(Min::findOrFail($id));

        $show->id('Id');
        $show->cartoon_id('Cartoon id');
        $show->pf_id('Pf id');
        $show->total_money('Total money');
        $show->start('Start');
        $show->end('End');
        $show->count('Count');
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
        $form = new Form(new Min);

        $form->saved(function(Form $form){
            $where = [
                ['month', '>=', substr($form->start,0,7)],
                ['month', '<=', substr($form->end,0,7)],
                ['pf_id', '=', $form->pf_id],
                ['cartoon_id', '=', $form->cartoon_id],
            ];
            DB::table('pfdatas')->where($where)->update(
                [
                    'per_min_money' => sprintf('%.2f', $form->total_money/$form->count),
                ]
            );
        });

        $options = DB::table('cartoons')->select('id','cartoon_name')->get();
        $selectOption = [];
        foreach ($options as $option) {
            $selectOption[$option->id] = $option->cartoon_name;
        }
        $form->select('cartoon_id','作品名称')->options($selectOption)->setWidth(3)->required();

        $options = DB::table('pfs')->select('id','pfname')->where('type','=',2)->get();
        $selectOption = [];
        foreach ($options as $option) {
            $selectOption[$option->id] = $option->pfname;
        }
        $form->select('pf_id','渠道名称')->options($selectOption)->setWidth(3)->required();

        $form->decimal('total_money', '总金额 money')->default(0.00);

        $form->dateRange('start', 'end','日期范围');

        $form->number('count', '月份总数');

        return $form;
    }
}
