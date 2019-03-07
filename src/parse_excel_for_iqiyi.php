<?php
/*
 * 用途：解析抓取的爱奇艺excel数据，并入库
 * 作者：feb1234@163.com
 * 时间：2018-05-02
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'inc/PHPExcel/PHPExcel.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsDictinfos.php');
ini_set('memory_limit','1500M');


//$cart = new Cartooninfos();
//$sect = new CartoonSectioninfos();
//$dict = new Dictinfos();

$path = $argv[1];
$patt = '/\d{4}-\d{2}-\d{2} \d{2}-\d{2}-\d{2}/';
preg_match($patt,$path,$ret);
$datetime = $ret[0];
$datetime = substr($datetime,0,13).':'.substr($datetime,14,2).':'.substr($datetime,17,2);
$datetime = strtotime($datetime);

$cnt = file_get_contents($path);
$lines = explode("\n", $cnt);

foreach($lines as $idx=>$line)
{
  $cell1 = (string)$sheet->getCellByColumnAndRow(1, $i)->getValue();
  $cell2 = (string)$sheet->getCellByColumnAndRow(2, $i)->getValue();
  $cell3 = (string)$sheet->getCellByColumnAndRow(3, $i)->getValue();
  $cell4 = (string)$sheet->getCellByColumnAndRow(4, $i)->getValue();
  $cell5 = (string)$sheet->getCellByColumnAndRow(5, $i)->getValue();
  $cell6 = (string)$sheet->getCellByColumnAndRow(6, $i)->getValue();
  $cell7 = (string)$sheet->getCellByColumnAndRow(7, $i)->getValue();
  $cell8 = (string)$sheet->getCellByColumnAndRow(8, $i)->getValue();

  if(!empty($cell1)){
    $cartinfo = array('uid'=>0, 'ctsource'=>SOURCE_AIQIYI,'ctsourceid'=>$cell0, 'cttype'=>5);
    $cartinfo['ctname'] = $cell1;
    $cartinfo['ctauthorname'] = $cell2;
    $cartinfo['ctprogress'] = ($cell4=='完结') ? PROGRESS_OVER : PROGRESS_NORMAL;
    var_dump($cartinfo);
    $ctinfo = $cart->CartoonExistForSourceAndSourceid($cartinfo['ctsource'], $cartinfo['ctsourceid']);
    if($ctinfo){
      $ctid = $ctinfo['ctid'];
      if($ctinfo['ctname'] != $cartinfo['ctname']){
        //$cart->update($ctid, array('ctlatestname'=>$cartinfo['ctname']));
      }
    }else{
      //$ctid = $cart->add($cartinfo);
      $csinfo = array('ctid'=>$ctid, 'cssource'=>SOURCE_AIQIYI, 'cssourceid'=>$cell0, 'cssourceurl'=>$cell8);
      var_dump($csinfo);
      //$cart->add($csinfo,'cartoonsourceinfos');
    }

    $exclusive = 0;
    if(trim($cell3) == '独家')
      $exclusive = 1;
    
    $cartdatainfo = array('ctid'=>$ctid, 'ctdsource'=>SOURCE_AIQIYI,'ctdbrowsercount'=>$cell5,'ctdexclusive'=>$exclusive,'ctddatetime'=>date('Y-m-d H:i:s',$datetime),'ctdtimestamp'=>strtotime($datetime),'ctdkeyname'=>$cell7,'ctdgrade'=>$cell6);
    $cartdatainfo['ctdname'] = $cartinfo['ctname'];
    $cartdatainfo['ctdauthorname'] = $cartinfo['ctauthorname'];
    $cartdatainfo['ctdprogress'] = $cartinfo['ctprogress'];
    $cartdatainfo['ctdsourceid'] = $cartinfo['ctsourceid'];
    $cartdatainfo['ctdsectioncount'] = 0;//count($data['data']['comics']);
    var_dump($cartdatainfo);
    //$cart->add($cartdatainfo,'cartoondatainfos');
  }





  ++$i;
  if(empty($cell0)){
    break;
  }
}


?>
