<?php

namespace App\Admin\Controllers;

use App\Models\Copyrightoutcome;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class OutcomeController extends Controller
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
            ->header('合作方结算列表')
            ->description('一些描述')
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
            ->header('编辑')
            ->description('description')
            ->body($this->form($id)->edit($id));
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
        $grid = new Grid(new Copyrightoutcome);
        $grid->disableCreateButton();
//        $grid->id('Id');
        $grid->type('类型')->display(function($type){
            switch ($type) {
                case 1:
                    return '版权方';
                    break;
                case 2:
                    return '合作方';
                    break;
                default :
                    return '未知';
                    break;
            }
        });
        $grid->name('合作方名称');
        $grid->month('年/月份');
        $grid->money('申请结算金额');
        $grid->status('结算状态')->display(function($status){
            switch ($status) {
                case 1:
                    return '未结算';
                    break;
                case 2:
                    return '申请结算';
                    break;
                case 3:
                    return '已结算';
                    break;
                default:
                    return '未知状态';
                    break;
            }
        });
        $grid->created_at('申请时间');

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
        $show = new Show(Copyrightoutcome::findOrFail($id));

        $show->id('Id');
        $show->type('Type');
        $show->month('Month');
//        $show->cartoon_id('Cartoon id');
        $show->money('Money');
        $show->status('Status');
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
        $form = new Form(new Copyrightoutcome);

//        $id = request()->route()->parameters()['outcome'];
//        $copyrightoutcome = $form->model()->where('id',$id)->first();
//        $form->select('type', '合租房类型')->options(['1'=>'版权方','2'=>'制作方'])->setWidth(3);
        $options = ['1'=>'版权方','2'=>'制作方'];
        $form->display('type', '合租房类型')->setWidth(3)->with(function ($value) use ($options) {
            return $options[$value];
        });

        $form->display('month', '年/月份')->setWidth(1);
//        $form->datetime('month', '年/月份')->format('YYYY-MM');
        $options = DB::table('cartoons')->select('id','cartoon_name')->get();
        $selectOption = [];
        foreach ($options as $option) {
            $selectOption[$option->id] = $option->cartoon_name;
        }
//        $form->select('cartoon_id','作品名称')->options($selectOption)->setWidth(3)->required()->readOnly();


        $form->display('money','申请金额')->setWidth(3);
//        $form->currency('money','申请金额')->symbol('￥');
//        $form->switch('status', 'Status')->default(1);
        $form->radio('status','状态')
            ->options([1 => '未结算', 2 => '申请结算', '3' => '已结算']);

//        $form->ignore(['type', 'month', 'cartoon_id', 'money']);

        $form->footer(function ($footer) {

            // 去掉`重置`按钮
            $footer->disableReset();

            // 去掉`提交`按钮
//            $footer->disableSubmit();

            // 去掉`查看`checkbox
            $footer->disableViewCheck();

            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();

            // 去掉`继续创建`checkbox
//            $footer->disableCreatingCheck();

        });

        return $form;
    }
}
