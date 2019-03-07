<?php
/*
 * 用途：解析抓取的腾讯数据，并入库
 * 作者：feb1234@163.com
 * 时间：2017-10-03
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsDictinfos.php');
ini_set('memory_limit','512M');

$cart = new Cartooninfos();
$dict = new Dictinfos();

$path = $argv[1];
$datetime = substr($path, strrpos($path,'.')+1);
$datatime = date('Y-m-d H:i:s',substr($datetime,10));

$cnt = file_get_contents($path);
$lines = explode("\n", $cnt);
$count = count($lines);
for($i=0; $i<$count; ++$i)
{
  $line = trim($lines[$i]);
  if(strlen($line)<100) continue;
  $line = substr($line, strpos($line,'{'));
  $data = json_decode($line,true);
  $data = $data['data'];
  if(!isset($data['comic']))
    continue;
  $cartinfo = array('uid'=>0,'ctsource'=>SOURCE_TENCENT, 'ctsourceid'=>$data['comic']['comic_id'],'cttype'=>5);
  $cartinfo['ctname'] = $data['comic']['title'];
  $cartinfo['ctauthorname'] = $data['comic']['artist_name'];
  $cartinfo['ctprogress'] = ($data['comic']['book_status']=='1')?PROGRESS_NORMAL:PROGRESS_OVER;
  $cartinfo['ctverticalimage'] = $data['comic']['cover_url'];
  $cartinfo['cthorizontalimage'] = $data['comic']['extra_cover_url'];
  $cartinfo['ctdesc'] = $data['comic']['brief_intrd'];
  $cartinfo['ctvector'] = ($data['comic']['is_strip']==2)?MAN_VECTOR_TIAO:MAN_VECTOR_PAGE;
  $ctinfo = $cart->CartoonExistForSourceAndSourceid($cartinfo['ctsource'], $cartinfo['ctsourceid']);
  if($ctinfo){
    $ctid = $ctinfo['ctid'];
    if($ctinfo['ctname'] != $data['comic']['title']){
      $cart->update($ctid, array('ctlatestname'=>$data['comic']['title']));
    }
  }else{
    $ctid = $cart->add($cartinfo);
    $csinfo = array('ctid'=>$ctid, 'cssource'=>SOURCE_TENCENT, 'cssourceid'=>$data['comic']['comic_id']);
    $cart->add($csinfo,'cartoonsourceinfos');
  }

  $cartdatainfo = array('ctid'=>$ctid,'ctdsource'=>SOURCE_TENCENT, 'ctdbrowsercount'=>$data['comic']['pgv_count'], 'ctdcollectcount'=>$data['comic']['coll_count'], 'ctdmonthticket'=>$data['comic']['month_ticket'],'ctdmonthticketrank'=>$data['comic']['month_ticket_rank'],'ctdprice'=>$data['comic']['comic_price'],'ctdgrade'=>$data['comic']['grade_ave'],'ctdgradecount'=>$data['comic']['grade_count'], 'ctddatetime'=>date('Y-m-d H:i:s',substr($datetime,0,10)),'ctdtimestamp'=>$datetime,'ctdkeyname'=>$data['comic']['type'],'ctdupdateatval'=>$data['comic']['update_time']);
  $cartdatainfo['ctdname'] = $cartinfo['ctname'];
  $cartdatainfo['ctdauthorname'] = $cartinfo['ctauthorname'];
  $cartdatainfo['ctdprogress'] = $cartinfo['ctprogress'];
  $cartdatainfo['ctdsourceid'] = $cartinfo['ctsourceid'];
  $cartdatainfo['ctdsectioncount'] = 0;//count($data['data']['comics']);
  $cart->add($cartdatainfo,'cartoondatainfos');
}

?>
