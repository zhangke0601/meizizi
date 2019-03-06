<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\ExcelExpoter;
use App\Models\Producer;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class ProducerController extends Controller
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
            ->header('制作方列表')
            ->description('相关描述')
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
        $grid = new Grid(new Producer);

        $grid->id('制作方Id');
        $grid->producer_name('制作方名称');
        $grid->adminUser()->name('对应管理员');
        $grid->producer_rate('制作方分成比例');

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
        $show = new Show(Producer::findOrFail($id));

        $show->id('Id');
        $show->producer_name('Producer name');
        $show->producer_rate('Producer rate');
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
        $form = new Form(new Producer);

        $form->text('producer_name', '制作方名称')->required()->rules(function ($form) {

            // 如果不是编辑状态，则添加字段唯一验证
            if (!$id = $form->model()->id) {
                return 'unique:producers';
            } else {
                return 'unique:producers,producer_name,'.$form->model()->id;
            }
        },[
            'unique' => '制作方名称已存在'
        ]);

        $options = DB::table('admin_users')->select('id','name')->get();
        $selectOption = [];
        foreach ($options as $option) {
            $selectOption[$option->id] = $option->name;
        }
        $form->select('admin_user_id','对应管理员')->options($selectOption)->setWidth(3)->rules(function ($form) {
            // 如果不是编辑状态，则添加字段唯一验证
            if (!$form->model()->id) {
                return 'nullable|unique:producers';
//                return Rule::unique('copyrights')->where(function ($query) {
//                    $query->where('admin_user_id', '>', 0);
//                });
            } else {
                return 'nullable|unique:producers,admin_user_id,'.$form->model()->id;
//                return Rule::unique('copyrights,admin_user_id,'.$form->model()->id)->where(function ($query) {
//                    $query->where('admin_user_id', '>', 0);
//                });
            }
        }, [
                'unique' => '不同合作方账号密码不能重复',
            ]
        );

        $form->decimal('producer_rate', '制作方比例');

        return $form;
    }
}
