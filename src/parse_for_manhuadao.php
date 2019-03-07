<?php
/*
 * 用途：解析抓取的漫画岛数据，并入库
 * 作者：feb1234@163.com
 * 时间：2017-10-03
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/clsCartooninfos.php');
ini_set('memory_limit','512M');
$cart = new Cartooninfos();

$path = $argv[1];
$datetime = substr($path, strrpos($path,'.')+1);
$datatime = date('Y-m-d H:i:s',substr($datetime,10));

$cnt = file_get_contents($path);
$lines = explode("\n", $cnt);
//var_dump($lines);exit;
foreach($lines as $line)
{
  $line = trim($line);
  if(strlen($line) < 100) continue;
  $line = substr($line, strpos($line,'{'));
  $d = json_decode($line,true);
  foreach($d['comicsList'] as $data)
  {
    $cartinfo = array('uid'=>0,'ctsource'=>SOURCE_MANHUADAO, 'ctsourceid'=>$data['bigbook_id'],'cttype'=>5);
    $cartinfo['ctname'] = $data['bigbook_name'];
    $cartinfo['ctauthorname'] = $data['bigbook_author'];
    $cartinfo['ctprogress'] = ($data['progresstype']=='1')?PROGRESS_NORMAL:PROGRESS_OVER;
    $cartinfo['ctverticalimage'] = $data['bigcoverurl'];
    $cartinfo['cthorizontalimage'] = '';
    $cartinfo['ctdesc'] = '';
    $ctinfo = $cart->CartoonExistForSourceAndSourceid($cartinfo['ctsource'], $cartinfo['ctsourceid']);
    if($ctinfo){
      $ctid = $ctinfo['ctid'];
      if($ctinfo['ctname'] != $data['bigbook_name']){
        $cart->update($ctid, array('ctlatestname'=>$data['bigbook_name']));
      }
    }else{
      //$ctinfo = $cart->CartoonExistForNameAndAuthor($cartinfo['ctname'], $cartinfo['ctauthorname']);
      //if($ctinfo)
      //  $ctid = $ctinfo['ctid'];
      //else
        $ctid = $cart->add($cartinfo);
      $csinfo = array('ctid'=>$ctid, 'cssource'=>SOURCE_MANHUADAO, 'cssourceid'=>$data['bigbook_id']);
      $cart->add($csinfo,'cartoonsourceinfos');
    }
    $exclusive = 0;
    if(!empty($data['superscript']))
      $exclusive = 1;
    $cartdatainfo = array('ctid'=>$ctid, 'ctdsource'=>SOURCE_MANHUADAO, 'ctdbrowsercount'=>$data['bigbookview'], 'ctdcollectcount'=>$data['collectcount'],'ctdcommentcount'=>$data['discusscount'], 'ctdexclusive'=>$exclusive, 'ctdkeyname'=>$data['subject_name'],'ctddatetime'=>date('Y-m-d H:i:s',substr($datetime,0,10)),'ctdtimestamp'=>$datetime,'ctdupdateatval'=>$data['updatedate']);

    $cartdatainfo['ctdname'] = $cartinfo['ctname'];
    $cartdatainfo['ctdauthorname'] = $cartinfo['ctauthorname'];
    $cartdatainfo['ctdprogress'] = $cartinfo['ctprogress'];
    $cartdatainfo['ctdsourceid'] = $cartinfo['ctsourceid'];
    $cartdatainfo['ctdsectioncount'] = ;//count($data['data']['comics']);
    $cart->add($cartdatainfo,'cartoondatainfos');
  }
}

?>
