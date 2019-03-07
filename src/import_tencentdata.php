<?php
/*
 * 用途：导入excel中腾讯数据
 * 作者：feb1234@163.com
 * 时间：2017-12-12
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
  $cell3 = (string)$sheet->getCellByColumnAndRow(2, $i)->getValue();
  $cell8 = (string)$sheet->getCellByColumnAndRow(7, $i)->getValue();
  if($cell8 == '独家')
  {
    $csinfo = $cart->getoneinfo(sprintf('cssource=%d and cssourceid=%s', SOURCE_TENCENT, $cell3),'cartoonsourceinfos');

    if($csinfo)
    {
      $ctdatainfos = $cart->getinfos(sprintf('ctdsource=%s and ctid=%d',SOURCE_TENCENT,$csinfo['ctid']),'cartoondatainfos');
      foreach($ctdatainfos as $ctdatainfo)
      {
        $data = array('ctdexclusive'=>1);
        $cart->update($ctdatainfo['ctdid'], $data,'ctd','cartoondatainfos');
      }
    }
  }

  //echo sprintf("%s %s\n",$cell3, $cell8);
}

var_dump($highestRow, $highestColumn);

?>
