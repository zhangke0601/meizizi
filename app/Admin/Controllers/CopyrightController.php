<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\ExcelExpoter;
use App\Models\Copyright;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CopyrightController extends Controller
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
            ->header('版权方列表')
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
        $grid = new Grid(new Copyright);

        $grid->id('版权方Id');
        $grid->company_name('版权公司');
        $grid->expire_time('授权期限')->display(function ($expire_time) {
            return $expire_time.' - '.$this->expire_time2;
        });
        $grid->adminUser()->name('对应管理员');
        $grid->copyright_rate('授权比例(大部分情况下所有作品比例相同)')->display(function ($copyright_rate){
            return $copyright_rate.'%';
        });

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
        $show = new Show(Copyright::findOrFail($id));

        $show->id('Id');
        $show->company_name('Company name');
        $show->expire_time('Expire time');
        $show->copyright_rate('Copyright rate');
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
        $form = new Form(new Copyright);

        $form->text('company_name', '版权公司名称')->required()->rules(function ($form) {

            // 如果不是编辑状态，则添加字段唯一验证
            if (!$id = $form->model()->id) {
                return 'unique:copyrights';
            } else {
                return 'unique:copyrights,company_name,'.$form->model()->id;
            }
        },[
            'unique' => '版权公司名称已存在'
        ]);
        $form->dateRange('expire_time', 'expire_time2', '授权日期')->options(['format'=>'YYYY:MM:DD'])->required();
        $options = DB::table('admin_users')->select('id','name')->get();
        $selectOption = [];
        foreach ($options as $option) {
            $selectOption[$option->id] = $option->name;
        }
        $form->select('admin_user_id','对应管理员')->options($selectOption)->setWidth(3)->rules(function ($form) {
            // 如果不是编辑状态，则添加字段唯一验证
            if (!$form->model()->id) {
                return 'nullable|unique:copyrights';
//                return Rule::unique('copyrights')->where(function ($query) {
//                    $query->where('admin_user_id', '>', 0);
//                });
            } else {
                return 'nullable|unique:copyrights,admin_user_id,'.$form->model()->id;
//                return Rule::unique('copyrights,admin_user_id,'.$form->model()->id)->where(function ($query) {
//                    $query->where('admin_user_id', '>', 0);
//                });
            }
        }, [
            'unique' => '不同合作方账号密码不能重复',
            ]
        );

        $form->rate('copyright_rate', '版权比例')->required();

        return $form;
    }
}
