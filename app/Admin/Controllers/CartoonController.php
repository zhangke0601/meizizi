<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\ExcelExpoter;
use App\Http\Controllers\Controller;
use App\Models\Cartoon;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class CartoonController extends Controller
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
            ->header('漫画')
            ->description('列表')
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
            ->header('添加')
            ->description('作品')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Cartoon);

        $grid->filter(function ($filter) {

            // 去掉默认的id过滤器
            $filter->disableIdFilter();

            $filter->column(1 / 2, function ($filter) {
                $filter->like('novel_name', '小说名称');

            });

            $filter->column(1 / 2, function ($filter) {
                $filter->like('cartoon_name', '漫画名称');
            });


        });

        $grid->id('作品Id');
        $grid->novel_name('小说名称');
        $grid->cartoon_name('漫画名称');
        $grid->copyright()->company_name('版权公司');
        $grid->mode('分成模式')->display(function ($mode) {
            if ($mode == 1) {
                return '利润模式';
            } elseif ($mode == 2) {
                return '流水模式';
            }
        });
        $grid->copyright_rate('版权方分成比例');
        $grid->crate1('周边分成比例');
        $grid->crate2('版权分成2比例');
        $grid->producer()->producer_name('制作公司');
        $grid->mode2('制作分成模式')->display(function ($mode2) {
            if ($mode2 == 1) {
                return '利润模式';
            } elseif ($mode2 == 2) {
                return '流水模式';
            }
        });
        $grid->producer_rate('制作方分成比例');
        $grid->crate3('内容使用2比例');
        $grid->crate4('保底分成2比例');
        $grid->crate5('制作分成2比例');
        $grid->partner()->partner_name('代理合作方');
        $grid->partner_rate('代理方分成比例');

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
        $show = new Show(Cartoon::findOrFail($id));

        $show->id('Id');
        $show->cartoon_name('Cartoon name');
        $show->copyright_id('Copyright id');
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
        $form = new Form(new Cartoon);

        $form->text('novel_name', '小说名称')->required()->rules(function ($form) {

            // 如果不是编辑状态，则添加字段唯一验证
            if (!$id = $form->model()->id) {
                return 'unique:cartoons';
            } else {
                return 'unique:cartoons,novel_name,' . $form->model()->id;
            }

        }, [
            'unique' => '小说名称已存在'
        ]);
        $form->text('cartoon_name', '漫画名称')->required()->rules(function ($form) {

            // 如果不是编辑状态，则添加字段唯一验证
            if (!$id = $form->model()->id) {
                return 'unique:cartoons';
            } else {
                return 'unique:cartoons,cartoon_name,' . $form->model()->id;
            }
        }, [
            'unique' => '漫画名称已存在'
        ]);

        //版权方信息
        $options = DB::table('copyrights')->select('id', 'company_name')->get();
        $selectOption = [];
        foreach ($options as $option) {
            $selectOption[$option->id] = $option->company_name;
        }
        $form->select('copyright_id', '版权方')->options($selectOption)->setWidth(5);
        $form->radio('mode', '分成模式')->options([1 => '利润模式', 2 => '流水模式'])->help('买断模式需要版权分成比例设置为0');
        $form->rate('copyright_rate', '版权方分成比例')->setWidth(3);
        $form->rate('crate1', '周边分成比例')->setWidth(3);
        $form->rate('crate2', '版权成本2比例')->setWidth(3)->default(100);

        $form->divider();
        //制作方信息
        $options = DB::table('producers')->select('id', 'producer_name')->get();
        $selectOption = [];
        foreach ($options as $option) {
            $selectOption[$option->id] = $option->producer_name;
        }
        $form->select('producer_id', '制作方')->options($selectOption)->setWidth(5);
        $form->radio('mode2', '制作分成模式')->options([1 => '利润模式', 2 => '流水模式']);
        $form->rate('producer_rate', '制作方分成比例')->setWidth(3);
        $form->rate('crate3', '内容使用比例')->setWidth(3);
        $form->rate('crate4', '保底分成2比例')->setWidth(3)->default(100);
        $form->rate('crate5', '制作分成2比例')->setWidth(3)->default(100);

        $form->divider();

        $options = DB::table('partners')->select('id', 'partner_name')->get();
        $selectOption = [];
        foreach ($options as $option) {
            $selectOption[$option->id] = $option->partner_name;
        }
        $form->select('partner_id', '代理方')->options($selectOption)->setWidth(5);
        $form->rate('partner_rate', '代理方分成比例')->setWidth(3);

//        $form->rate($column[, $label]);

        return $form;
    }
}
