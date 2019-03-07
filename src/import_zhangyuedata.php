<?php
/*
 * 用途：导入excel中掌阅数据
 * 作者：feb1234@163.com
 * 时间：2017-12-26
* */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'inc/PHPExcel/PHPExcel.php');
require_once($base_dir.'model/clsCartooninfos.php');

$file = $argv[1];

$objReader = PHPExcel_IOFactory::createReader('Excel2007');
$objPHPExcel = $objReader->load($file);
$sheet = $objPHPExcel->getSheet(0);
$highestRow = $sheet->getHighestRow();           //取得总行数
$highestColumn = $sheet->getHighestColumn(); //取得总列数
$highestColumn = PHPExcel_Cell::columnIndexFromString($highestColumn);

$cart = new Cartooninfos();
for($i=2; $i<=$highestRow; ++$i)
{
  $cell3 = (string)$sheet->getCellByColumnAndRow(0, $i)->getValue();
  $cell4 = (string)$sheet->getCellByColumnAndRow(4, $i)->getValue();
  $cart->update($cell3,array('ctdprice'=>1),'ct','cartoondatainfos');
  //echo sprintf("%s %s\n", $cell3, $cell4);

}


?>
