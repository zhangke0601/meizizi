<?php
/**
 * 生成Excel相关方法
 * Created by PhpStorm.
 * User: DreamWake
 * Date: 2018/8/30
 * Time: 14:31
 */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'inc/PHPExcel/PHPExcel.php');

/**
 * 生成Excel 浏览器返回
 * @param $fileName
 * @param $headArr
 * @param $data
 */
function getExcel($fileName,$headArr,$data){
    //对数据进行检验
    if(empty($data) || !is_array($data)){
        die("data must be a array");
    }
    //检查文件名
    if(empty($fileName)){
        exit;
    }
    $fileName .= ".xls";
    //创建PHPExcel对象，注意，不能少了\
    $objPHPExcel = new PHPExcel();
    $objProps = $objPHPExcel->getProperties();

    //设置表头
    $key = ord("A");
    $key2 = ord("@");//@--64
//    foreach($headArr as $v){
//        $colum = chr($key);
//        $objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum.'1', $v);
//
//        $key += 1;
//    }
    foreach($headArr as $v){
        if($key>ord("Z")){
            $key2 += 1;
            $key = ord("A");
            $colum = chr($key2).chr($key);//超过26个字母时才会启用
        }else{
            if($key2>=ord("A")){
                $colum = chr($key2).chr($key);//超过26个字母时才会启用
            }else{
                $colum = chr($key);
            }
        }
        $objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum.'1', $v);
        $key += 1;
    }

    $column = 2;
    $objActSheet = $objPHPExcel->getActiveSheet();
//    foreach($data as $key => $rows){ //行写入
//        $span = ord("A");
//        foreach($rows as $keyName=>$value){// 列写入
//            $j = chr($span);
//            $objActSheet->setCellValue($j.$column, $value);
//            $span++;
//        }
//        $column++;
//    }

    foreach($data as $key => $rows){
        $key = ord("A");
        $key2 = ord("@");//@--64
        foreach($rows as $keyName=>$value){
            if($key>ord("Z")){
                $key2 += 1;
                $key = ord("A");
                $colum = chr($key2).chr($key);//超过26个字母时才会启用
            }else{
                if($key2>=ord("A")){
                    $colum = chr($key2).chr($key);//超过26个字母时才会启用
                }else{
                    $colum = chr($key);
                }
            }
            $objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum.$column, $value);
            $key += 1;
        }
        $column++;
    }


    $fileName = iconv("utf-8", "gb2312", $fileName);
    //重命名表
    // $objPHPExcel->getActiveSheet()->setTitle('test');
    //设置活动单指数到第一个表,所以Excel打开这是第一个表
    $objPHPExcel->setActiveSheetIndex(0);
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    browser_export('Excel5',$fileName);
    $objWriter->save('php://output'); //文件通过浏览器下载
    exit;
}

function browser_export($type,$filename){
    if($type=="Excel5"){
        header('Content-Type: application/vnd.ms-excel');//告诉浏览器将要输出excel03文件
    }else{
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//告诉浏览器数据excel07文件
    }
    header('Content-Disposition: attachment;filename="'.$filename.'"');//告诉浏览器将输出文件的名称
    header('Cache-Control: max-age=0');//禁止缓存
}