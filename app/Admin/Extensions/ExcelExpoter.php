<?php

namespace App\Admin\Extensions;

use App\Models\Balance;
use App\Models\Makeoutcome;
use App\Models\Pfdata;
use App\Models\Statistics;
use Encore\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ExcelExpoter extends AbstractExporter
{
    protected $cellLetter = [
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q',
        'R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD',
        'AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN',
        'AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'
    ];

    public $datas = [];

    /**
     * @return mixed|void
     */
    public function export()
    {
        $route = \Request::route()->getName();
        switch ($route) {
            case 'incomes.index':
                $this->incomeExport();
                break;
            case 'contracts.index':
                $this->contractExport();
                break;
            case 'cartoons.index':
                $this->cartoonExport();
                break;
            case 'pfs.index':
                $this->pfExport();
                break;
            case 'statistics.index':
                $this->statisticsExport();
                break;
            case 'statistics.prefer':
                $this->preferExport();
                break;
            case 'balance.index':
                $this->balanceExport();
                break;
            case 'balance.index2':
                $this->forecastExport();
                break;
            case 'copyrights.index':
                $this->copyrightExport();
                break;
            case 'producers.index':
                $this->producerExport();
                break;
            case 'bills.index':
                $this->billExport();
        }
    }

    /**
     * 结算明细
     */
    public function billExport()
    {
        Excel::create('结算明细', function ($excel) {
            $excel->sheet('结算明细', function ($sheet) {
                $datas = $this->getData();
//                dump($datas);die;
                $title = ['时间', '类型', '合作方', '应结算总和', '已经结算', '当月应结算', '计算时间'];

                $sheet->prependRow($title);
                $column = count($title);
                $rows = count($datas);
                $cellData = [];

                //设置整体样式
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 12,
                        'bold' => false,
                    ]
                ])->cell('A1:' . $this->cellLetter[$column - 1] . '1', function ($cells) {

                    $cells->setBackground('#AAAAFF');
                });

                foreach ($datas as $data) {
                    $cellData[] = [
                        'month' => $data['month'],
                        'type' => $data['type'] == 1 ? '版权方' : '制作方',
                        'name' => $data['type'] == 1 ? ($data['copyright']['company_name'] ?? '') : ($data['producer']['producer_name'] ?? ''),
                        'should_bills' => $data['should_bills'],
                        'have_bills' => $data['have_bills'],
                        'now_bills' => $data['now_bills'],
                        'updated_at' => $data['updated_at'],
                    ];
                }

                $sheet->rows($cellData);


                $sheet->row(0, function ($row) {

                    $row->setFont(array(   //设置标题的样式
                        'family' => 'Calibri',
                        'size' => '16',
                        'bold' => true
                    ));
                });

                //循环设置宽高
                for ($i = 1; $i <= $column; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
                for ($j = 1; $j <= $rows + 1; $j++) {
                    $sheet->setHeight($j, 20);
                    $sheet->row($j, function ($row) {
                        $row->setAlignment('center');
                        $row->setValignment('center');
                    });
                }

            });
        })->export('xls');
    }

    /**
     * 制作方结算
     */
    public function forecastExport()
    {
        Excel::create('制作方结算', function ($excel) {
            $excel->sheet('制作方结算', function ($sheet) {
                $datas = (new Makeoutcome())->balance_paginate(true);
//                dump($datas);die;
                $title = ['年月份', '作品名称', '制作公司', '可结算分成', '结算状态'];

                $sheet->prependRow($title);
                $column = count($title);
                $rows = count($datas);
                $cellData = [];

                //设置整体样式
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 12,
                        'bold' => false,
                    ]
                ])->cell('A1:' . $this->cellLetter[$column - 1] . '1', function ($cells) {

                    $cells->setBackground('#AAAAFF');
                });

                foreach ($datas as $data) {
                    if($data['status'] == 1 || empty($data['status'])) {
                        $status = '未结算';
                    }elseif($data['status'] == 2){
                        $status = '申请中';
                    }elseif($data['status'] == 3){
                        $status = '已结算';
                    }
                    $cellData[] = [
                        'month' => $data['month'],
                        'cartoon_name' => $data['cartoon_name'],
                        'producer_name' => $data['producer_name'],
                        'bqfcsj' => $data['bqfcsj'],
                        'status' => $status,
                    ];
                }

                $sheet->rows($cellData);


                $sheet->row(0, function ($row) {

                    $row->setFont(array(   //设置标题的样式
                        'family' => 'Calibri',
                        'size' => '16',
                        'bold' => true
                    ));
                });

                //循环设置宽高
                for ($i = 1; $i <= $column; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
                for ($j = 1; $j <= $rows + 1; $j++) {
                    $sheet->setHeight($j, 20);
                    $sheet->row($j, function ($row) {
                        $row->setAlignment('center');
                        $row->setValignment('center');
                    });
                }

            });
        })->export('xls');
    }

    /**
     * 版权方结算
     */
    public function balanceExport()
    {
        Excel::create('版权方结算', function ($excel) {
            $excel->sheet('版权方结算', function ($sheet) {
                $datas = (new Balance())->balance_paginate(true);
//                dump($datas);die;
                $title = ['年月份', '作品名称', '版权公司', '可结算分成', '结算状态'];

                $sheet->prependRow($title);
                $column = count($title);
                $rows = count($datas);
                $cellData = [];

                //设置整体样式
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 12,
                        'bold' => false,
                    ]
                ])->cell('A1:' . $this->cellLetter[$column - 1] . '1', function ($cells) {

                    $cells->setBackground('#AAAAFF');
                });

                foreach ($datas as $data) {
                    if($data['status'] == 1 || empty($data['status'])) {
                        $status = '未结算';
                    }elseif($data['status'] == 2){
                        $status = '申请中';
                    }elseif($data['status'] == 3){
                        $status = '已结算';
                    }
                    $cellData[] = [
                        'month' => $data['month'],
                        'cartoon_name' => $data['cartoon_name'],
                        'company_name' => $data['company_name'],
                        'bqfcsj' => $data['bqfcsj'],
                        'status' => $status,
                    ];
                }

                $sheet->rows($cellData);


                $sheet->row(0, function ($row) {

                    $row->setFont(array(   //设置标题的样式
                        'family' => 'Calibri',
                        'size' => '16',
                        'bold' => true
                    ));
                });

                //循环设置宽高
                for ($i = 1; $i <= $column; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
                for ($j = 1; $j <= $rows + 1; $j++) {
                    $sheet->setHeight($j, 20);
                    $sheet->row($j, function ($row) {
                        $row->setAlignment('center');
                        $row->setValignment('center');
                    });
                }

            });
        })->export('xls');
    }

    /**
     * 预估数据导出
     */
    public function preferExport()
    {
        Excel::create('预估结算数据', function ($excel) {
            $excel->sheet('预估结算数据', function ($sheet) {
//                $datas = $this->datas;
                $datas = (new Statistics())->prefer_paginate(true);
                foreach ($datas as $key=>$data) {
                    unset($datas[$key]['rate1']);
                    unset($datas[$key]['rate2']);
                    unset($datas[$key]['rate3']);
                    unset($datas[$key]['rate4']);
                    unset($datas[$key]['rate5']);
                    unset($datas[$key]['mode']);
                    unset($datas[$key]['mode2']);
                    unset($datas[$key]['kfpjs']);
                }
//                $title = ['年月份', '作品名称', '总收入', '成本总额', '版权成本', '版权方结算', '制作方结算', '纯利润', '毛利润', '各平台分成结算', '可分配结算', '保底收入', '版权公司', '授权期限', '版权分成比例', '结算情况', '制作公司', '制作方分成比例', '制作方稿费', '内容扣除', '结算情况', '代理方', '代理分成'];
                $title = ['年月份', '作品名称', '总收入', '成本总额', '毛利润','纯利润', '各平台分成结算', '保底收入（分）','保底收入（总）', '版权公司','版权成本', '版权方结算','授权期限', '版权分成比例', '结算情况', '制作公司','制作方结算', '制作方分成比例', '制作方稿费', '制作杂费','内容扣除', '结算情况', '代理方', '代理分成'];

                $sheet->prependRow($title);
                $column = count($title);
                $rows = count($datas);
                $cellData = [];

                //设置整体样式
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 12,
                        'bold' => false,
                    ]
                ])->cell('A1:' . $this->cellLetter[$column - 1] . '1', function ($cells) {

                    $cells->setBackground('#AAAAFF');
                });

                $sheet->rows($datas);


                $sheet->row(0, function ($row) {

                    $row->setFont(array(   //设置标题的样式
                        'family' => 'Calibri',
                        'size' => '16',
                        'bold' => true
                    ));
                });

                //循环设置宽高
                for ($i = 1; $i <= $column; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
                for ($j = 1; $j <= $rows + 1; $j++) {
                    $sheet->setHeight($j, 20);
                    $sheet->row($j, function ($row) {
                        $row->setAlignment('center');
                        $row->setValignment('center');
                    });
                }

            });
        })->export('xls');
    }

    /**
     * 实际数据导出
     */
    public function statisticsExport()
    {
        Excel::create('实际结算数据', function ($excel) {
            $excel->sheet('实际结算数据', function ($sheet) {
//                $datas = $this->datas;
                $datas = (new Statistics())->statistics_paginate(true);
//                dump($datas);die;
                foreach ($datas as $key=>$data) {
//                    unset($datas[$key]['rate1']);
//                    unset($datas[$key]['rate2']);
//                    unset($datas[$key]['rate3']);
//                    unset($datas[$key]['rate4']);
//                    unset($datas[$key]['rate5']);
                    unset($datas[$key]['mode']);
                    unset($datas[$key]['mode2']);
                    unset($datas[$key]['kfpjs']);
                }
//                dump($datas);die;
                $title = ['年月份', '作品名称', '总收入', '成本总额', '毛利润','纯利润', '各平台分成结算',  '保底收入（分）','保底收入（总）', '版权公司','版权成本', '版权方结算','授权期限', '版权分成比例', '结算情况', '制作公司','制作方结算', '制作方分成比例', '制作方稿费','制作杂费','内容扣除', '结算情况', '代理方', '代理分成', '周边比例', '分成显示比例', '内容比例','保底分成显示比例','制作分成2比例'];

                $sheet->prependRow($title);
                $column = count($title);
                $rows = count($datas);
                $cellData = [];

                //设置整体样式
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 12,
                        'bold' => false,
                    ]
                ])->cell('A1:' . $this->cellLetter[$column - 1] . '1', function ($cells) {

                    $cells->setBackground('#AAAAFF');
                });

                $sheet->rows($datas);


                $sheet->row(0, function ($row) {

                    $row->setFont(array(   //设置标题的样式
                        'family' => 'Calibri',
                        'size' => '16',
                        'bold' => true
                    ));
                });

                //循环设置宽高
                for ($i = 1; $i <= $column; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
                for ($j = 1; $j <= $rows + 1; $j++) {
                    $sheet->setHeight($j, 20);
                    $sheet->row($j, function ($row) {
                        $row->setAlignment('center');
                        $row->setValignment('center');
                    });
                }

            });
        })->export('xls');
    }

    /**
     * 渠道结算导出计算
     */
    public function incomeExport()
    {
        $datas = (new Pfdata)->ajax_paginate(true);

        $infactDatas = (new Pfdata)->forecast(true);

        $pf_id = \Request::get('pf_id');
        if ($pf_id) {
            $pfs = DB::table('pfs')->where('id','=', $pf_id)->get()->toArray();
        } else {
            $pfs = DB::table('pfs')->orderBy('type','asc')->get()->toArray();
        }
        $cellData[0] = ['年月日期', '作品名称', '版权公司', '分成收入汇总', '保底汇总', '版权成本', '稿费', '杂费'];
        $cellData2[0] = ['年月日期', '作品名称', '版权公司', '分成收入汇总', '保底汇总', '版权成本', '稿费', '杂费'];
        foreach ($pfs as $pf) {
            array_push($cellData[0],$pf->pfname);
            array_push($cellData2[0],$pf->pfname);
        }

        $pfsortname = DB::table('pfs')->select('id','sortname','type')->get()->toArray();
        $pfsortnameArray = [];
//                $pfType = [];
        foreach ($pfsortname as $sortname) {
            $pfsortnameArray[$sortname->id] = $sortname->sortname;
//                    $pfType[$sortname->id] = $sortname->type;
        }

        $testData = [];
        foreach ($datas as $key => $data) {
            $cellData[$key+1]['month'] = $data['month'] ?? '';
            $cellData[$key+1]['cartoon_name'] = $data['cartoon_name'] ?? '';
            $cellData[$key+1]['company_name'] = $data['company_name'] ?? '';
            $cellData[$key+1]['money_count'] = $data['money_count'] ?? '';
            $cellData[$key+1]['min_money'] = $data['min_money'] ?? '';
            $cellData[$key+1]['copyright_money'] = $data['copyright_money'] ?? '';
            $cellData[$key+1]['payment'] = $data['payment'] ?? '';
            $cellData[$key+1]['pay_other'] = $data['pay_other'] ?? '';
//            foreach ($pfsortnameArray as $pf) {
//                $cellData[$key+1][$pf] = $data[$pf]['money'] ?? 0;
//            }
            foreach ($pfs as $pf) {
                $cellData[$key+1][$pf->sortname] = $data[$pf->sortname]['money'] ?? 0;
                $testData[$key][] = $data[$pf->sortname] ?? [];
            }
        }


        $testData2 = [];
        foreach ($infactDatas as $key => $data) {
            $cellData2[$key+1]['month'] = $data['month'] ?? '';
            $cellData2[$key+1]['cartoon_name'] = $data['cartoon_name'] ?? '';
            $cellData2[$key+1]['company_name'] = $data['company_name'] ?? '';
            $cellData2[$key+1]['money_count'] = $data['money_count'] ?? '';
            $cellData2[$key+1]['min_money'] = $data['min_money'] ?? '';
            $cellData2[$key+1]['copyright_money'] = $data['copyright_money'] ?? '';
            $cellData2[$key+1]['payment'] = $data['payment'] ?? '';
            $cellData2[$key+1]['pay_other'] = $data['pay_other'] ?? '';
//            foreach ($pfsortnameArray as $pf) {
//                $cellData2[$key+1][$pf] = $data[$pf]['money'] ?? 0;
//                $testData2[$key][] = $data[$pf] ?? [];
//            }
            foreach ($pfs as $pf) {
                $cellData2[$key+1][$pf->sortname] = $data[$pf->sortname]['money'] ?? 0;
                $testData2[$key][] = $data[$pf->sortname] ?? [];
            }
        }


        Excel::create('预估数据列表&&实际数据列表', function($excel) use ($cellData, $cellData2, $pfs, $testData,$testData2){

            $excel->sheet('预估数据表', function($sheet) use ($cellData, $pfs, $testData) {

                $column = $this->cellLetter[count($cellData[0]) - 1];

                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 12,
                        'bold' => false,
                    ]
                    ])
                    ->cell('A1:' . $column . '1', function ($cells) {

                        $cells->setBackground('#AAAAFF');
                    })->setHeight(1, 20)
                      ->setWidth('A',10)
                      ->setWidth('B',30)
                      ->setWidth('C',20)
                      ->setWidth('D',20);

                $rows = collect(array_values($cellData));

                $sheet->rows($rows);

                $sheet->row(0, function ($row) {

                    $row->setFont(array(   //设置标题的样式
                        'family' => 'Calibri',
                        'size' => '16',
                        'bold' => true
                    ));
                })->cell('A'.count($cellData).':' . $column . count($cellData), function ($cells) {

                    $cells->setBackground('#5786AE');
                });

                for ($i = 2; $i <= count($cellData)+1; $i++) {
                    $sheet->setHeight($i, 20);
//                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                    $sheet->row($i - 1, function ($row) {
                        $row->setAlignment('center');
                        $row->setValignment('center');
                    });
                }


                for ($i = 2; $i <= count($cellData) - 1; $i++) {
                    for ($j = 1; $j <= count($pfs); $j++){
                        $sheet->row($i)->cell($this->cellLetter[$j+6].$i, function($cells) use ($testData,$i,$j){
//                            判断数据
                            $money = $testData[$i-2][$j-1];
                            if(!isset($money['id'])){
                                $cells->setFontColor('#858585');
                            }elseif($money['money'] == 0){
                                if($money['is_line'] == 0){
                                    $cells->setFontColor('#902E90');
                                }elseif($money['is_nopay'] == 1){
                                    $cells->setFontColor('#6EFFFF');
                                }
                            }else{
                                if($money['type'] == 1){
                                    $cells->setFontColor('#858585');
                                }elseif($money['type'] == 2){
                                    $cells->setFontColor('#FB0000');
                                }elseif($money['type'] == 3){
                                    $cells->setFontColor('#00FF00');
                                }
                            }


                        });
                    }
                 }


            });
            $excel->sheet('实际数据表', function($sheet) use ($cellData2, $pfs, $testData2) {

                $column = $this->cellLetter[count($cellData2[0]) - 1];

                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 12,
                        'bold' => false,
                    ]
                ])
                    ->cell('A1:' . $column . '1', function ($cells) {

                        $cells->setBackground('#AAAAFF');
                    })->setHeight(1, 20)
                    ->setWidth('A',10)
                    ->setWidth('B',30)
                    ->setWidth('C',20)
                    ->setWidth('D',20);

                $rows = collect(array_values($cellData2));

                $sheet->rows($rows);

                $sheet->row(0, function ($row) {

                    $row->setFont(array(   //设置标题的样式
                        'family' => 'Calibri',
                        'size' => '16',
                        'bold' => true
                    ));
                })->cell('A'.count($cellData2).':' . $column . count($cellData2), function ($cells) {

                    $cells->setBackground('#5786AE');
                });

                for ($i = 2; $i <= count($cellData2)+1; $i++) {
                    $sheet->setHeight($i, 20);
//                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                    $sheet->row($i - 1, function ($row) {
                        $row->setAlignment('center');
                        $row->setValignment('center');
                    });
                }

                for ($i = 2; $i <= count($cellData2) - 1; $i++) {
                    for ($j = 1; $j <= count($pfs); $j++){
                        $sheet->row($i)->cell($this->cellLetter[$j+6].$i, function($cells) use ($testData2,$i,$j){
//                            判断数据
                            $money = $testData2[$i-2][$j-1];
                            if(!isset($money['id'])){
                                $cells->setFontColor('#858585');
                            }elseif($money['money'] == 0){
                                if($money['is_line'] == 0){
                                    $cells->setFontColor('#902E90');
                                }elseif($money['is_nopay'] == 1){
                                    $cells->setFontColor('#6EFFFF');
                                }
                            }else{
                                if($money['type'] == 1){
//                                    $cells->setFontColor('#858585');
                                }elseif($money['type'] == 2){
                                    $cells->setFontColor('#FB0000');
                                }elseif($money['type'] == 3){
                                    $cells->setFontColor('#00FF00');
                                }
                            }


                        });
                    }
                }

            });

        })->export('xls');
    }

    /**
     * 合同列表导出
     */
    protected function contractExport()
    {
        Excel::create('合同列表', function($excel) {

            $excel->sheet('合同列表', function($sheet) {

                $datas = $this->getData();

                //标题名称
                $title = ['作品'	, '类型', '合同类型', '合作模式', '授权类型', '授权范围', '我方公司', '合作方', '合同开始时间'	, '合同结束时间', '我方比例', '合作方比例', '保底', '其他描述', '我方联系人', '地址', '合作方联系人', '电话'];
                $sheet->prependRow($title);
                $column = $this->cellLetter[count($title) - 1];
                $rows = [];
                //设置整体样式
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 12,
                        'bold' => false,
                    ]
                ])->cell('A1:' . $column . '1', function ($cells) {

                    $cells->setBackground('#AAAAFF');
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
                $contractMode = [
                    '1' => '保底分成',
                    '2' => '买断',
                    '3' => '分成',
                ];
                $authType = [
                    '1' => '独家',
                    '2' => '非独家',
                ];
                $myCompany = [
                    '1' => '成都漫悦',
                    '2' => '成美滋滋',
                ];

//                dump($datas);
                foreach ($datas as $data) {
                    $rows[] = [
//                        'cartoon_name' => ($data['type'] == 1) ? $data['cartoon']['novel_name'] : $data['cartoon']['cartoon_name'],
                        'cartoon_name' => $data['cartoon_id'],
                        'type' => $data['type'] == 1 ? '小说' : '漫画',
                        'contract_type' => $contractType[$data['contract_type']] ?? '',
                        'contract_mode' => $contractMode[$data['contract_mode']] ?? '',
                        'auth_type' => $authType[$data['auth_type']] ?? '',
                        'auth_area' => $data['auth_area'],
                        'my_company' => $myCompany[$data['my_company']] ?? '',
                        'contract_company' => $data['contract_company'],
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'my_rate' => $data['my_rate'],
                        'contract_rate' => $data['contract_rate'],
                        'mins' => $data['mins'],
                        'other_detail' => $data['other_detail'],
                        'my_contacts' => $data['my_contacts'],
                        'address' => $data['address'],
                        'contract_contacts' => $data['contract_contacts'],
                        'contract_mobile' => $data['contract_mobile'],
                    ];

                }
                $sheet->rows($rows);

                $sheet->row(0, function ($row) {

                    $row->setFont(array(   //设置标题的样式
                        'family' => 'Calibri',
                        'size' => '16',
                        'bold' => true
                    ));
                });

                //循环设置宽高
                for ($i = 1; $i <= count($title); $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 20);
                }
                for ($j = 1; $j <= count($rows) + 1; $j++) {
                    $sheet->setHeight($j, 20);
                    $sheet->row($j, function ($row) {
                        $row->setAlignment('center');
                        $row->setValignment('center');
                    });
                }
            });

        })->export('xls');
    }

    /**
     * 作品列表导出
     */
    protected function cartoonExport()
    {
        Excel::create('作品列表', function ($excel) {
           $excel->sheet('作品列表', function ($sheet) {
               $datas = $this->getData();
               $title = ['小说名称', '漫画名称', '版权方', '版权方分成', '制作方', '制作方分成', '代理方', '代理方分成'];

               $sheet->prependRow($title);
               $column = count($title);
               $rows = count($datas);
               $cellData = [];

               //设置整体样式
               $sheet->setStyle([
                   'font' => [
                       'name' => 'Calibri',
                       'size' => 12,
                       'bold' => false,
                   ]
               ])->cell('A1:' . $this->cellLetter[$column - 1] . '1', function ($cells) {

                   $cells->setBackground('#AAAAFF');
               });

               foreach ($datas as $data) {
                   $cellData[] = [
                       'novel_name' => $data['novel_name'],
                       'cartoon_name' => $data['cartoon_name'],
                       'copyright' => $data['copyright']['company_name'] ?? '',
                       'copyright_rate' => $data['copyright_rate'],
                       'producer' => $data['producer']['producer_name'] ?? '',
                       'producer_rate' => $data['producer_rate'],
                       'partner' => $data['partner']['partner_name'] ?? '',
                       'partner_rate' => $data['partner_rate']
                   ];
               }

               $sheet->rows($cellData);


               $sheet->row(0, function ($row) {

                   $row->setFont(array(   //设置标题的样式
                       'family' => 'Calibri',
                       'size' => '16',
                       'bold' => true
                   ));
               });

               //循环设置宽高
               for ($i = 1; $i <= $column; $i++) {
                   $sheet->setWidth($this->cellLetter[$i - 1], 30);
               }
               for ($j = 1; $j <= $rows + 1; $j++) {
                   $sheet->setHeight($j, 20);
                   $sheet->row($j, function ($row) {
                       $row->setAlignment('center');
                       $row->setValignment('center');
                   });
               }

           });
        })->export('xls');
    }

    /**
     * 渠道列表导出
     */
    protected function pfExport()
    {
        Excel::create('渠道列表', function ($excel) {
            $excel->sheet('渠道列表', function ($sheet) {
                $datas = $this->getData();
//                dump($datas);die;
                $title = ['渠道名称', '渠道方类型'];

                $sheet->prependRow($title);
                $column = count($title);
                $rows = count($datas);
                $cellData = [];

                //设置整体样式
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 12,
                        'bold' => false,
                    ]
                ])->cell('A1:' . $this->cellLetter[$column - 1] . '1', function ($cells) {

                    $cells->setBackground('#AAAAFF');
                });

                foreach ($datas as $data) {
                    $cellData[] = [
                        'pfname' => $data['pfname'],
                        'type' => $data['type'] == 1 ? '分成渠道' : ($data['type'] == 2 ? '保底渠道' : '合作方明细'),
                    ];
                }

                $sheet->rows($cellData);


                $sheet->row(0, function ($row) {

                    $row->setFont(array(   //设置标题的样式
                        'family' => 'Calibri',
                        'size' => '16',
                        'bold' => true
                    ));
                });

                //循环设置宽高
                for ($i = 1; $i <= $column; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
                for ($j = 1; $j <= $rows + 1; $j++) {
                    $sheet->setHeight($j, 20);
                    $sheet->row($j, function ($row) {
                        $row->setAlignment('center');
                        $row->setValignment('center');
                    });
                }

            });
        })->export('xls');
    }

    /**
     * 版权列表导出
     */
    protected function copyrightExport()
    {
        Excel::create('版权列表', function ($excel) {
            $excel->sheet('版权列表', function ($sheet) {
                $datas = $this->getData();
//                dump($datas);die;
                $title = ['版权公司', '授权比例(大部分情况下所有作品比例相同)'];

                $sheet->prependRow($title);
                $column = count($title);
                $rows = count($datas);
                $cellData = [];

                //设置整体样式
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 12,
                        'bold' => false,
                    ]
                ])->cell('A1:' . $this->cellLetter[$column - 1] . '1', function ($cells) {

                    $cells->setBackground('#AAAAFF');
                });

                foreach ($datas as $data) {
                    $cellData[] = [
                        'company_name' => $data['company_name'],
                        'copyright_rate' => $data['copyright_rate'],
                    ];
                }

                $sheet->rows($cellData);


                $sheet->row(0, function ($row) {

                    $row->setFont(array(   //设置标题的样式
                        'family' => 'Calibri',
                        'size' => '16',
                        'bold' => true
                    ));
                });

                //循环设置宽高
                for ($i = 1; $i <= $column; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
                for ($j = 1; $j <= $rows + 1; $j++) {
                    $sheet->setHeight($j, 20);
                    $sheet->row($j, function ($row) {
                        $row->setAlignment('center');
                        $row->setValignment('center');
                    });
                }

            });
        })->export('xls');
    }

    protected function producerExport()
    {
        Excel::create('制作方列表', function ($excel) {
            $excel->sheet('制作方列表', function ($sheet) {
                $datas = $this->getData();
//                dump($datas);die;
                $title = ['制作方公司', '授权比例(大部分情况下所有作品比例相同)'];

                $sheet->prependRow($title);
                $column = count($title);
                $rows = count($datas);
                $cellData = [];

                //设置整体样式
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 12,
                        'bold' => false,
                    ]
                ])->cell('A1:' . $this->cellLetter[$column - 1] . '1', function ($cells) {

                    $cells->setBackground('#AAAAFF');
                });

                foreach ($datas as $data) {
                    $cellData[] = [
                        'producer_name' => $data['producer_name'],
                        'producer_rate' => $data['producer_rate'],
                    ];
                }

                $sheet->rows($cellData);


                $sheet->row(0, function ($row) {

                    $row->setFont(array(   //设置标题的样式
                        'family' => 'Calibri',
                        'size' => '16',
                        'bold' => true
                    ));
                });

                //循环设置宽高
                for ($i = 1; $i <= $column; $i++) {
                    $sheet->setWidth($this->cellLetter[$i - 1], 30);
                }
                for ($j = 1; $j <= $rows + 1; $j++) {
                    $sheet->setHeight($j, 20);
                    $sheet->row($j, function ($row) {
                        $row->setAlignment('center');
                        $row->setValignment('center');
                    });
                }

            });
        })->export('xls');
    }
}