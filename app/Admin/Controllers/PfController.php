<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\ExcelExpoter;
use App\Models\Pf;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class PfController extends Controller
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
        return $content
            ->header('渠道方列表')
            ->description('.')
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
        $grid = new Grid(new Pf);

        $grid->id('渠道方id');
        $grid->pfname('渠道方简称');
        $grid->full_name('渠道方全称');
        $grid->remark('备注');
        $grid->type('渠道方类型')->display(function($type){
            switch ($type) {
                case 1:
                    return '分成渠道';
                    break;
                case 2:
                    return '保底渠道';
                    break;
                case 3:
                    return '合作方结算渠道';
                    break;
                default:
                    return '未知类型';
                    break;
            }
        });
//        $grid->sortname('渠道方简称');


        $grid->exporter(new ExcelExpoter());
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
        $show = new Show(Pf::findOrFail($id));

        $show->id('Id');
        $show->pfname('平台名称');
        $show->添加时间('Created at');
        $show->修改时间('Updated at');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Pf);

        $form->saving(function (Form $form) {
            $form->sortname = str_random(5);
        });

        $form->text('pfname', '渠道方简称')->required();
        $form->text('full_name', '渠道方全称')->required();
        $form->radio('type', '渠道方类型')->options(['1'=>'分成渠道','2'=>'保底渠道','3'=>'合作方结算渠道'])->default('1')->required();
        $form->text('remark', '备注');
//        $form->text('sortname', '渠道方简称')->required()->help('可以用平台中文首字母组合，不要使用中文');
        $form->hidden('sortname', '渠道方简称');

        return $form;
    }
}
