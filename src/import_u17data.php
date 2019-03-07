<?php
/*
 * 用途：导入excel中u17数据
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
  $cell3 = (string)$sheet->getCellByColumnAndRow(3, $i)->getValue();
  $cell4 = (string)$sheet->getCellByColumnAndRow(4, $i)->getValue();
  $cell8 = (string)$sheet->getCellByColumnAndRow(6, $i)->getValue();
  $cell12 = (string)$sheet->getCellByColumnAndRow(12, $i)->getValue();
  $cell13 = (string)$sheet->getCellByColumnAndRow(13, $i)->getValue();
  $cell14 = (string)$sheet->getCellByColumnAndRow(14, $i)->getValue();
  $cell14 = PHPExcel_Shared_Date::ExcelToPHP($cell14);
  $cell14 = date('Y-m-d H:i:s');
  $patt = '/comic\/(.*?)\./';
  preg_match($patt, $cell4, $ret);
  $sourceid = $ret[1];
  if($cell3 == '独家')
  {
    $csinfo = $cart->getoneinfo(sprintf('cssource=%d and cssourceid=%s', SOURCE_U17, $sourceid),'cartoonsourceinfos');

    if($csinfo)
    {
      $cartdatainfo = array('ctid'=>$csinfo['ctid'],'ctdsource'=>SOURCE_U17,
        'ctdexclusive'=>1,
        'ctdcommentcount'=>$cell12,'ctdtuijiancount'=>$cell13,
        'ctddatetime'=>$cell14,
        'ctdtimestamp'=>$cell14);
      //$cart->add($cartdatainfo, 'cartoondatainfos');
      $bdate = date('Y-m-d 00:00:00',strtotime($cell14));
      $edate = date('Y-m-d 00:00:00',strtotime($cell14)+3600*24);

      $ctdatainfos = $cart->getinfos(sprintf('ctdsource=%s and ctid=%d',SOURCE_U17,$csinfo['ctid']),'cartoondatainfos');
      foreach($ctdatainfos as $ctdatainfo)
      {
        $data = array('ctdexclusive'=>1);
        $cart->update($ctdatainfo['ctdid'], $data,'ctd','cartoondatainfos');

        $ctddatetime = $ctdatainfo['ctddatetime'];
        if(($ctddatetime>=$bdate) && ($ctddatetime<=$edate))
        {
          $cartdatainfo = array('ctdcommentcount'=>$cell12,'ctdtuijiancount'=>$cell13);
          $cart->update($ctdatainfo['ctdid'], $cartdatainfo, 'ctd', 'cartoondatainfos');
        }
      }
    }
  }

}


?>
