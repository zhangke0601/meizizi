<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\ExcelExpoter;
use App\Models\Contract;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    use HasResourceActions;

    /**
     * 合同列表
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
//        $datas = DB::table('contracts')->get()->toArray();
//        $type = ['1'=>'小说作品','2'=>'漫画作品'];
//        $myCompany = [
//            '1' => '成都漫悦科技有限公司',
//            '2' => '成都美滋滋联盟科技有限公司',
//        ];
//        foreach ($datas as $data) {
//            $arr = array_keys($myCompany, $data->my_company);
//            DB::table('contracts')->where('id',$data->id)
//                ->update(
//                    ['my_company' => $arr[0] ?? '']
//                );
//        }
//        die;
        Admin::css('css/table.css');
        return $content
            ->header('合同列表')
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
            ->header('合同编辑')
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
     * 合同列表builder
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Contract);

        $grid->filter(function($filter){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();

            $filter->column(1/3, function ($filter) {
                $filter->like('cartoon_id','作品');
                $contractType = [
                    '1' => '版权采购',
                    '2' => '自有签约',
                    '3' => '作品代理',
                    '4' => '渠道发行',
                    '5' => '漫画改编',
                    '6' => '影视开发',
                    '7' => '周边开发',
                    '8' => '其他开发',
                ];
                $filter->equal('contract_type','合同类型')->select($contractType);
            });

            $filter->column(1/3, function ($filter) {
//                $filter->like('contract_company','合作方');

                $contract_companys = [];
                $infos = DB::table('contracts')->groupBy('contract_company')
                    ->select('contract_company')
                    ->get()->toArray();
                foreach ($infos as $key => $info) {
                    $contract_companys[$info->contract_company] = $info->contract_company;
                }

                $filter->equal('contract_company','合作方')->select($contract_companys);

                $myCompany = [
                    '1' => '成都漫悦',
                    '2' => '成美滋滋',
                ];
                $filter->equal('my_company','我方公司')->select($myCompany);
            });

            $filter->column(1/3, function ($filter) {

                $filter->equal('type','作品类型')->select(['1'=>'小说','2'=>'漫画']);
            });

        });

        $grid->contract_sn('合同编号');
        $grid->cartoon_id('作品名称');

        $grid->type('类型')->display(function($type){
            if($type == 1) {
                return '小说';
            }else{
                return '漫画';
            }
        });
        $contractType = [
            '1' => '版权采购',
            '2' => '自有签约',
            '3' => '作品代理',
            '4' => '渠道发行',
            '5' => '漫画改编',
            '6' => '影视开发',
            '7' => '周边开发',
            '8' => '其他开发',
        ];

        $grid->contract_type('合同类型')->display(function($type) use($contractType){
            return $contractType[$type] ?? '';
        });

        $contractMode = [
            '1' => '保底分成',
            '2' => '买断',
            '3' => '分成',
        ];
        $grid->contract_mode('合作模式')->display(function($mode) use($contractMode){
            return $contractMode[$mode] ?? '';
        });

        $authType = [
            '1' => '独家',
            '2' => '非独家',
        ];
        $grid->auth_type('授权类型')->display(function($auth) use($authType){
            return $authType[$auth] ?? '';
        });
        $grid->auth_area('授权范围');
        $myCompany = [
            '1' => '成都漫悦',
            '2' => '成美滋滋',
        ];
        $grid->my_company('我方公司')->display(function($company) use($myCompany){
            return $myCompany[$company] ?? '';
        });
        $grid->contract_company('合作方');
        $grid->start_time('合同开始时间');
        $grid->end_time('合同结束时间');
        $grid->my_rate('我方比例');
        $grid->contract_rate('合作方比例');
        $grid->mins('保底');
        $grid->other_detail('其他描述');
        $grid->my_contacts('我方联系人');
        $grid->address('地址');
        $grid->contract_contacts('合作方联系人');
        $grid->contract_mobile('电话');

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
        $show = new Show(Contract::findOrFail($id));

        $show->id('Id');
        $show->cartoon_id('Cartoon id');
        $show->type('Type');
        $show->contract_type('Contract type');
        $show->contract_mode('Contract mode');
        $show->auth_type('Auth type');
        $show->auth_area('Auth area');
        $show->my_company('My company');
        $show->contract_company('Contract company');
        $show->start_time('Start time');
        $show->end_time('End time');
        $show->my_rate('My rate');
        $show->contract_rate('Contract rate');
        $show->mins('Mins');
        $show->other_detail('Other detail');
        $show->my_contacts('My contacts');
        $show->address('Address');
        $show->contract_contacts('Contract contacts');
        $show->contract_mobile('Contract mobile');
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
        $form = new Form(new Contract);

        $form->tab('上一页', function ($form) {

            $form->text('contract_sn', '合同编号')->required()->rules('min:1');
            $form->select('type', '作品类型')->options(['1'=>'小说','2'=>'漫画'])->required()->setWidth(4);
            //->load('cartoon_id', '/api/cartoon')
            $form->text('cartoon_id', '授权作品')->required();

            $contractType = [
                '1' => '版权采购',
                '2' => '自有签约',
                '3' => '作品代理',
                '4' => '渠道发行',
                '5' => '漫画改编',
                '6' => '影视开发',
                '7' => '周边开发',
                '8' => '其他开发',
            ];
            $form->select('contract_type', '合同类型')->options($contractType)->required()->setWidth(2);

            $contractMode = [
                '1' => '保底分成',
                '2' => '买断',
                '3' => '分成',
            ];
            $form->select('contract_mode', '合作模式')->options($contractMode)->required()->setWidth(2);

            $authType = [
                '1' => '独家',
                '2' => '非独家',
            ];
            $form->select('auth_type', '授权类型')->options($authType)->required()->setWidth(2);
            $form->text('auth_area', '授权范围');
            $myCompany = [
                '1' => '成都漫悦',
                '2' => '成美滋滋',
            ];
            $form->select('my_company', '我方公司')->options($myCompany)->required()->setWidth(2);
            $form->text('contract_company', '合作方公司');

            $form->dateRange('start_time', 'end_time', '合同时间范围')->options(['format' => 'YYYY/MM/DD']);

        })->tab('下一页', function ($form) {

            $form->rate('my_rate', '我方比例')->setWidth(3)->rules('max:100');
            $form->rate('contract_rate', '合作方比例')->setWidth(3)->rules('max:100');
            $form->currency('mins', '保底金额')->symbol('￥');
            $form->text('other_detail', '其他合作细节');
            $form->text('my_contacts', '我方联系人');
            $form->text('address', '合作方地址');
            $form->text('contract_contacts', '合作方联系人');
            $form->mobile('contract_mobile', '联系电话');
        });

        return $form;
    }
}
