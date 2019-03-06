<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\ExcelExpoter;
use App\Http\Controllers\Controller;
use App\Models\Statistics;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
//use Encore\Admin\Grid\Row;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class StatisticsController extends Controller
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
        // 选填
        $content->header('实际结算模式');

//        // 选填
        $content->description('利润分成模式：版权分成 = ((分成流水*分成结算2比例+保底流水*保底结算2比例)-稿费-版权成本)-((分成流水+保底流水)*制作方比例)))*版权方比例<br />制作方分成 = ((分成流水*分成结算2比例+保底流水*保底结算2比例)-稿费-版权成本-制作方内容扣除)-((分成流水+保底流水)*版权方比例)))*制作方比例<br /> 流水分成模式： 版权分成 = (分成流水*分成结算2比例+保底流水*保底结算2比例)*版权方比例<br />制作方分成 =((分成流水*分成结算2比例+保底流水*保底结算2比例)-稿费-杂费-版权成本-制作方内容扣除)-((分成流水)*版权方比例)))*制作方比例<br />保底流水：均指的是保底流水（分）');

        $content->body($this->grid());

        return $content;

    }

    public function prefer(Content $content)
    {
        Admin::css('css/table.css');
        $content->header('预估结算模式');

        // 选填
        $content->description('利润分成模式：版权分成 = ((分成流水*分成结算2比例+保底流水*保底结算2比例)-稿费-版权成本)-((分成流水+保底流水)*制作方比例)))*版权方比例<br />制作方分成 = ((分成流水*分成结算2比例+保底流水*保底结算2比例)-稿费-版权成本-制作方内容扣除)-((分成流水+保底流水)*版权方比例)))*制作方比例<br /> 流水分成模式： 版权分成 = (分成流水*分成结算2比例+保底流水*保底结算2比例)*版权方比例<br />制作方分成 =((分成流水*分成结算2比例+保底流水*保底结算2比例)-稿费-杂费-版权成本-制作方内容扣除)-((分成流水)*版权方比例)))*制作方比例<br />保底流水：均指的是保底流水（分）');

        $content->body($this->grid());

        return $content;

        $form = new \Encore\Admin\Widgets\Form();

        $form->action('prefer');
        $form->method('get');
        $form->date('month', '月份')->format('YYYY-MM')->default(Request::get('month',''));

        $options = DB::table('cartoons')->select('id','cartoon_name')->get();
        $selectOption = [];
        foreach ($options as $option) {
            $selectOption[$option->id] = $option->cartoon_name;
        }

        $form->select('cartoon_id', '作品名称')->options($selectOption)->setWidth(4)->default(Request::get('cartoon_id',''));
        $form->text('company', '合作公司')->setWidth(4)->default(Request::get('company',''));

//        $content->body($form->render());

        $box = new Box('筛选条件', $form->render());

        $box->removable();
        $box->collapsable();
        $box->style('info');
        $box->solid();
//        $box->class = 'box box-info box-solid collapsed-box';
        $content->body($box);

        $export = trans('admin.export');
        $all = trans('admin.all');
        $currentPage = trans('admin.current_page');
        $paramter = $_GET;
        $paramter['_export_'] = 'all';
        $allUrl = url()->current() .'?'. http_build_query($paramter);

        $paramter['_export_'] = 'now';
        $currentPageUrl = url()->current() .'?'. http_build_query($paramter);

        $page = request('page', 1);

        $exportBtn = <<<EOT

<div class="btn-group pull-right" style="margin-right: 10px">
    <a class="btn btn-sm btn-twitter" title="{$export}"><i class="fa fa-download"></i><span class="hidden-xs"> {$export}</span></a>
    <button type="button" class="btn btn-sm btn-twitter dropdown-toggle" data-toggle="dropdown">
        <span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu" role="menu">
        <li><a href="{$allUrl}" target="_blank">{$all}</a></li>
        <li><a href="{$currentPageUrl}" target="_blank">{$currentPage}</a></li>
        
    </ul>
</div>
EOT;

        $content->row(function(Row $row) use ($exportBtn){
            $row->column(2, '<span style="color:red">基本信息</span>');
            $row->column(4, '月营收情况汇总');
            $row->column(2, '作品各平台收入');
            $row->column(2, '版权方');
            $row->column(1, '制作方');
            $row->column(1, $exportBtn);
        });

        // table 1

        $headers = ['<span style="font-size: 14px">月份</span>', '<span style="font-size: 14px">作品名称</span>', '<span style="font-size:14px">总收入</span>', '<span style="font-size: 14px">成本总额</span>','<span style="font-size: 14px">版权成本</span>','<span style="font-size:14px">版权方分成结算</span>','<span style="font-size: 14px">制作方分成结算</span>','<span style="font-size:14px">毛利润</span>','<span style="font-size: 14px">各平台分成结算</span>','<span style="font-size: 14px">可分配结算</span>','<span style="font-size: 14px">保底收入</span>','<span style="font-size: 14px">版权公司</span>','<span style="font-size: 14px">授权期限</span>','<span style="font-size:14px">版权公司比例</span>','<span style="font-size:14px">结算情况</span>','<span style="font-size: 14px">制作公司</span>','<span style="font-size: 14px">制作方分成比例</span>','<span style="font-size: 14px">制作方稿费</span>','<span style="font-size: 14px">结算情况</span>','<span style="font-size: 14px">代理方</span>','<span style="font-size: 14px">代理分成</span>'];

        /* 查询-计算数据s */
        $where = [];
        $where1 = [];
        $where2 = [];
        if (!empty(Request::get('month',''))) {
            $where[] = ['pfdatas.month', '=', Request::get('month','')];
        }
        if (!empty(Request::get('cartoon_id',''))) {
            $where[] = ['pfdatas.cartoon_id', '=', Request::get('cartoon_id','')];
        }
        if (!empty(Request::get('company',''))) {
//            $where[] = ['copyrights.company_name', 'like', '%'.Request::get('company','').'%'];
            $where1[] = ['copyrights.company_name', 'like', '%'.Request::get('company','').'%'];
            $where2[] = ['producers.producer_name', 'like', '%'.Request::get('company','').'%'];
        }
        $pfdatas = DB::table('pfdatas')
            ->leftJoin('cartoons', 'pfdatas.cartoon_id', '=', 'cartoons.id')
            ->leftJoin('partners', 'cartoons.partner_id', '=', 'partners.id')
            ->leftJoin('producers', 'cartoons.producer_id', '=', 'producers.id')
            ->leftJoin('copyrights', 'pfdatas.copyright_id', '=', 'copyrights.id')
            ->leftJoin('copyrightoutcomes', function ($join) {
                $join->on('copyrightoutcomes.month', '=', 'pfdatas.month')->on('copyrightoutcomes.cartoon_id', '=', 'pfdatas.cartoon_id')->where('copyrightoutcomes.type','=',1);
            })
            ->leftJoin('copyrightoutcomes as cs', function ($join) {
                $join->on('cs.month', '=', 'pfdatas.month')->on('cs.cartoon_id', '=', 'pfdatas.cartoon_id')->where('cs.type','=',2);
            })
            ->select('pfdatas.*','cartoons.cartoon_name','copyrights.company_name', 'copyrights.expire_time','copyrights.expire_time2','copyrights.copyright_rate','producers.producer_name', 'copyrightoutcomes.status','cs.status as status2','partners.partner_name','cartoons.partner_rate')
            ->where($where)
            ->where(function ($query) use($where1, $where2) {
                $query->where($where1)
                    ->orWhere($where2);
            })
            ->orderBy('pfdatas.month', 'desc')
            ->get();

        $rows = [];
        foreach ($pfdatas as $pfdata) {
            $key = $this->isSameCartoon($pfdata->cartoon_name, $pfdata->month, $rows);

            if ($key !== false) {
                $rows[$key]['all_money_infact'] += ($pfdata->plan_money+$pfdata->min_money);
                $rows[$key]['dlfc'] += ($pfdata->plan_money) * ($pfdata->partner_rate/100);
                $rows[$key]['gptfcjssj'] += ($pfdata->plan_money);
                $rows[$key]['kfpjs'] += ($pfdata->plan_money * 0.3);
                $rows[$key]['bdsr'] += ($pfdata->min_money);
            } else {
                $month = $pfdata->month;    //月份
                $cartoon_name = $pfdata->cartoon_name;    //作品名称
                $all_money_infact = $pfdata->plan_money+$pfdata->min_money;    //总收入实际

                $all_bqcb = $pfdata->copyright_money;    //版权成本
                $bqffcjs = '';//版权方分成结算
                $zzffejs = '';//制作方分成结算

                $gptfcjssj = $pfdata->plan_money;   //个平台分成结算实际
                $kfpjs = $gptfcjssj * 0.3;  //可分配结算实际
                $bdsr = $pfdata->min_money;  //保底收入
                $bqgs = $pfdata->company_name; //版权公司
                $bqgs_time = ($pfdata->expire_time??'-').'-'.($pfdata->expire_time2??'-'); //版权公司授权期限
                $bqgs_rate = $pfdata->copyright_rate; //版权公司授权比例50
                $gf = $pfdata->payment;
                //版权分成结算
                $bqfcjs = 0;
                $zzgs = $pfdata->producer_name;
                $zzfbl = $pfdata->producer_rate;

                $zzfcjs = ($kfpjs - $all_bqcb - ($bqgs_rate/100)*($kfpjs - $all_bqcb) + $gf*$bqgs_rate/100) * ($zzfbl/100) / (1-$bqgs_rate/100*$zzfbl/100);

                $all_cost_infact = $all_bqcb + $gf + $bqfcjs + $zzfcjs;    //成本总额实际
                $income = $all_money_infact - $all_cost_infact;//毛利润

                $dl = $pfdata->partner_name;//代理
                $dlfc = $gptfcjssj * $pfdata->partner_rate /100;

                $status1 = $pfdata->status;
                switch ($status1) {
                    case 1:
                        $status1 = '未结算';
                        break;
                    case 2:
                        $status1 = '结算中';
                        break;
                    case 3:
                        $status1 = '已结算';
                        break;
                    default:
                        $status1 = '未结算';
                        break;
                }

                $status2 = $pfdata->status2;
                switch ($status2) {
                    case 1:
                        $status2 = '未结算';
                        break;
                    case 2:
                        $status2 = '结算中';
                        break;
                    case 3:
                        $status2 = '已结算';
                        break;
                    default:
                        $status2 = '未结算';
                        break;
                }

//                $rows[] = [
//                    $month, $cartoon_name, 'all_money_infact'=>$all_money_infact, 'all_cost_infact'=>$all_cost_infact, 'all_bqcb'=>$all_bqcb, 'bqfcjs'=>$bqfcjs, 'zzfcjs'=>$zzfcjs, 'income'=>$income, 'gptfcjssj'=>$gptfcjssj, 'kfpjs'=>$kfpjs, 'bdsr'=>$bdsr, $bqgs, $bqgs_time, 'bqgs_rate'=>$bqgs_rate, 'bqfcjs2'=>$bqfcjs, 'status1'=>$status1, $zzgs, 'zzfbl'=>$zzfbl, 'gf'=>$gf, 'zzfcjs2'=>$zzfcjs, 'status2'=>$status2
//                ];

                $rows[] = [
                    $month, $cartoon_name, 'all_money_infact'=>$all_money_infact, 'all_cost_infact'=>$all_cost_infact, 'all_bqcb'=>$all_bqcb, 'bqfcjs'=>$bqfcjs, 'zzfcjs'=>$zzfcjs, 'income'=>$income, 'gptfcjssj'=>$gptfcjssj, 'kfpjs'=>$kfpjs, 'bdsr'=>$bdsr, $bqgs, $bqgs_time, 'bqgs_rate'=>$bqgs_rate, 'status1'=>$status1, $zzgs, 'zzfbl'=>$zzfbl, 'gf'=>$gf, 'status2'=>$status2, 'dl'=>$dl,'dlfc'=>$dlfc
                ];
            }

        }

        foreach ($rows as $key => &$row) {
            $row['bqfcjs'] = round((($row['kfpjs'] - $row['all_bqcb'] - $row['gf'] - (($row['zzfbl']/100)*($row['kfpjs'] - $row['all_bqcb'])))*($row['bqgs_rate']/100))/(1-($row['bqgs_rate']/100)*($row['zzfbl']/100)),2);
            $row['zzfcjs'] = round(($row['kfpjs'] - $row['all_bqcb'] - ($row['bqgs_rate']/100)*($row['kfpjs'] - $row['all_bqcb']) + $row['gf']*($row['bqgs_rate']/100)) * ($row['zzfbl']/100) / (1-($row['zzfbl']/100)*($row['bqgs_rate']/100)),2);
            $row['all_cost_infact'] = $row['all_bqcb'] + $row['gf'] + $row['bqfcjs'] + $row['zzfcjs'];
            $row['income'] = $row['all_money_infact'] - $row['all_cost_infact'];
//            $row['bqfcjs2'] = $row['bqfcjs'];
//            $row['zzfcjs2'] = $row['zzfcjs'];
        }


        if(Request::get('_export_','') == 'all'){
            $export = new ExcelExpoter();
            $export->datas = $rows;
            $export->export();
        }elseif(Request::get('_export_','') == 'now'){
            $export = new ExcelExpoter();
            $page = Request::get('page',1);
            $new_rows = array_chunk($rows,20);
            $export->datas = $new_rows[$page-1]??[];
            $export->export();
        }

        foreach ($rows as $key1=>$item1) {
            foreach ($item1 as $key2=> $item2) {
                if(!$item2){
                    $rows[$key1][$key2] = '<span style="color:#ddd">0</span>';
                }
            }
        }
//        dump($rows);
        /* 查询-计算数据e */
        //数据分页
        $page = Request::get('page',1);
        $new_rows = array_chunk($rows,20);
        $table = new Table($headers, $new_rows[$page-1]??[], true);

        $box = new Box('数据', $table->render());
//        $box->removable();
//        $box->collapsable();
        $box->style('default');
        $box->solid();
        $content->body($box);

        $paginator = new LengthAwarePaginator($rows, count($rows), 20);

        $paginator->setPath(url()->current());

        $content->body(view('admin.tools.button',compact('pfdatas','paginator')));

        return $content;
    }

    private function isSameCartoon($cartoon_name, $month, $data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if ($value[1] == $cartoon_name && $value[0] == $month) {
                    return $key;
                }
            }
        }
        return false;
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
//        $copytights = DB::table('copyrights')->get(['id', 'company_name'])->toArray();
//        dump($copytights);
        $grid = new Grid(new Statistics());

        /*筛选条件*/
        $grid->filter(function($filter){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();

            $filter->column(1/3, function ($filter) {
                $filter->equal('month','开始月份')->datetime(['format' => 'YYYY-MM']);
                $selectOption = ['0'=>'全部公司'];
                $options = DB::table('copyrights')->get()->toArray();
                foreach ($options as $option) {
                    $selectOption[$option->id] = $option->company_name;
                }
                $filter->equal('copyright_id','版权公司')->select($selectOption);

            });

            $filter->column(1/3, function ($filter) {
                $filter->equal('month2','结束月份')->datetime(['format' => 'YYYY-MM']);

                $options = DB::table('producers')->get()->toArray();
                $selectOption = ['0'=>'全部公司'];
                foreach ($options as $option) {
                    $selectOption[$option->id] = $option->producer_name;
                }
                $filter->equal('producer_id', '制作方')->select($selectOption);
            });


            $filter->column(1/3, function ($filter) {
                $options = DB::table('cartoons')->get()->toArray();
                $selectOption = ['0'=>'全部作品'];
                foreach ($options as $option) {
                    $selectOption[$option->id] = $option->cartoon_name;
                }
                $filter->equal('cartoon_id','作品名称')->select($selectOption);
//                $filter->equal('together', '是否合并')->select(['2'=>'合并','1'=>'不合并'])->default('1');
            });

        });

        $grid->column('month','日期');
        $grid->column('cartoon_name','作品名称');
        $grid->column('all_money_infact','总收入')->display(function($val){
            return $val ? $val : '<span style="color:#ddd">0</span>';
        });
        $grid->column('all_cost_infact','总成本')->display(function($val){
            return $val ? sprintf('%.2f', $val) : '<span style="color:#ddd">0</span>';
        });


        $grid->column('income','纯利润')->display(function($val){
            return $val ? $val : '<span style="color:#ddd">0</span>';
        });
        $grid->column('income2','毛利润')->display(function($val){
            return $val ? $val : '<span style="color:#ddd">0</span>';
        });
        $grid->column('gptfcjssj','分成流水')->display(function($val){
            return $val ? $val : '<span style="color:#ddd">0</span>';
        });
//        $grid->column('kfpjs','可分配流水')->display(function($val){
//            return $val ? sprintf('%.2f', $val) : '<span style="color:#ddd">0</span>';
//        });
        $grid->column('bdsr','保底流水(分)')->display(function($val){
            return $val ? $val : '<span style="color:#ddd">0</span>';
        });
        $grid->column('bdsr1','保底流水(总)')->display(function($val){
            return $val ? $val : '<span style="color:#ddd">0</span>';
        });

        //版权公司情况
        $grid->column('bqgs','<span style="color: green">版权公司</span>');
        $grid->column('all_bqcb','<span style="color: green">版权成本</span>')->display(function($val){
            return $val ? $val : '<span style="color:#ddd">0</span>';
        });
        $grid->column('bqfcjs','<span style="color: green">版权分成</span>')->display(function($val){
            return $val ? sprintf('%.2f', $val) : '<span style="color:#ddd">0</span>';
        });
        $grid->column('bqgs_time','<span style="color: green">授权期限</span>');
        $grid->column('bqgs_rate','<span style="color: green">版权公司比例</span>')->display(function($val){
            return $val ? $val : '<span style="color:#ddd">0</span>';
        });
        $grid->column('status1','<span style="color: green">结算情况</span>');


        //制作方情况
        $grid->column('zzgs', '<span style="color: #803969">制作公司</span>');
        $grid->column('zzfcjs','<span style="color: #803969">制作分成</span>')->display(function($val){
            return $val ? sprintf('%.2f', $val) : '<span style="color:#ddd">0</span>';
        });
        $grid->column('zzfbl','<span style="color: #803969">制作方分成比例</span>')->display(function($val){
            return $val ? $val : '<span style="color:#ddd">0</span>';
        });
        $grid->column('gf','<span style="color: #803969">制作方稿费</span>')->display(function($val){
            return $val ? $val : '<span style="color:#ddd">0</span>';
        });
        $grid->column('zf','<span style="color: #803969">制作杂费</span>')->display(function($val){
            return $val ? $val : '<span style="color:#ddd">0</span>';
        });
        $grid->column('content_kouchu','<span style="color: #803969">内容扣除</span>');
        $grid->column('status2','<span style="color: #803969">结算情况</span>');


        $grid->column('dl','代理方');
        $grid->column('dlfc','代理分成')->display(function($val){
            return $val ? $val : '<span style="color:#ddd">0</span>';
        });

        $grid->column('rate1','周边比例');
        $grid->column('rate2','分成显示比例');
        $grid->column('rate3','内容比例');
        $grid->column('rate4','保底分成显示比例');
        $grid->column('rate5','制作分成2比例');


        $grid->exporter(new ExcelExpoter());
        $grid->disableActions();
        $grid->disableRowSelector();
        $grid->disableCreation();
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
        $show->pfname('Pfname');
        $show->sortname('Sortname');
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
        $form = new Form(new Statistics);

        $form->text('pfname', 'Pfname');
        $form->text('sortname', 'Sortname');

        return $form;
    }

}
