<?php
/*
 * 用途：解析抓取的快看数据，并入库
 * 作者：feb1234@163.com
 * 时间：2017-10-03
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/clsCartooninfos.php');
ini_set('memory_limit','2500M');

$cart = new Cartooninfos();

$path = $argv[1];
$datetime = substr($path, strrpos($path,'.')+1);
$datatime = date('Y-m-d H:i:s',substr($datetime,10));

$cnt = file_get_contents($path);
$lines = explode("\n", $cnt);
foreach($lines as $line)
{
  $line = trim($line);
  if(strlen($line) < 100) continue;
  $line = substr($line, strpos($line,'{'));
  $data = json_decode($line,true);
  $cartinfo = array('uid'=>0,'ctsource'=>SOURCE_KUAIKAN, 'ctsourceid'=>$data['data']['id'],'cttype'=>5);
  $cartinfo['ctname'] = $data['data']['title'];
  $cartinfo['ctauthorname'] = $data['data']['user']['nickname'];
  $cartinfo['ctprogress'] = ($data['data']['update_status']=='连载中')?PROGRESS_NORMAL:PROGRESS_OVER;
  $cartinfo['ctverticalimage'] = $data['data']['vertical_image_url'];
  $cartinfo['cthorizontalimage'] = $data['data']['cover_image_url'];
  $cartinfo['ctdesc'] = $data['data']['description'];
  $ctinfo = $cart->CartoonExistForSourceAndSourceid($cartinfo['ctsource'], $cartinfo['ctsourceid']);
  if($ctinfo){
    $ctid = $ctinfo['ctid'];
    if($ctinfo['ctname'] != $data['data']['title']){
      $cart->update($ctid, array('ctlatestname'=>$data['data']['title']));
    }
  }else{
    //$ctinfo = $cart->CartoonExistForNameAndAuthor($cartinfo['ctname'], $cartinfo['ctauthorname']);
    //if($ctinfo)
    //  $ctid = $ctinfo['ctid'];
    //else
      $ctid = $cart->add($cartinfo);
    $csinfo = array('ctid'=>$ctid, 'cssource'=>SOURCE_KUAIKAN, 'cssourceid'=>$data['data']['id']);
    $cart->add($csinfo,'cartoonsourceinfos');
  }
  $exclusive = 0;
  if(strpos($data['data']['description'], '独家') !== false)
    $exclusive = 1;
  $created_at = 0;
  foreach($data['data']['comics'] as $c)
  {
    if($c['created_at'] > $created_at)
      $created_at = $c['created_at'];
  }
  $cartdatainfo = array('ctid'=>$ctid, 'ctdsource'=>SOURCE_KUAIKAN, 'ctdprice'=>($data['data']['is_free'])?0:1, 'ctdzancount'=>$data['data']['likes_count'], 'ctdcollectcount'=>$data['data']['fav_count'],'ctdcommentcount'=>$data['data']['comments_count'], 'ctdredu'=>$data['data']['view_count'],'ctdbrowsercount'=>$data['data']['popularity_value'],'ctdcreateat'=>date('Y-m-d H:i:s',$data['data']['created_at']),'ctdupdateat'=>date('Y-m-d H:i:s',$data['data']['updated_at']),'ctdexclusive'=>$exclusive,'ctddatetime'=>date('Y-m-d H:i:s',substr($datetime,0,10)),'ctdtimestamp'=>$datetime,'ctdkeyname'=>implode(',', $data['data']['category']),'ctdupdateat'=>date("Y-m-d H:i:s",$created_at),'ctdupdateatval'=>$created_at);
  $cartdatainfo['ctdname'] = $cartinfo['ctname'];
  $cartdatainfo['ctdauthorname'] = $cartinfo['ctauthorname'];
  $cartdatainfo['ctdprogress'] = $cartinfo['ctprogress'];
  $cartdatainfo['ctdsourceid'] = $cartinfo['ctsourceid'];
  $cartdatainfo['ctdsectioncount'] = count($data['data']['comics']);
  $cart->add($cartdatainfo,'cartoondatainfos');
  foreach($data['data']['comics'] as $row)
  {
    $ctsectinfo = array('ctid'=>$ctid,'ctsname'=>$row['title'], 'ctscover'=>$row['cover_image_url'], 'ctscontent'=>$row['url'], 'ctssource'=>SOURCE_KUAIKAN, 'ctssourceid'=>$row['id']);
    $ctsinfo = $cart->SectionExistForName($ctid,$ctsectinfo['ctsname']);
    if($ctsinfo)
      $ctsid = $ctsinfo['ctsid'];
    else
      $ctsid = $cart->add($ctsectinfo, 'cartoonsectioninfos');
    $ctsdatainfo = array('ctsid'=>$ctsid, 'ctsdsource'=>SOURCE_KUAIKAN, 'ctsdbrowsercount'=>$row['likes_count'],'ctsdgoodcount'=>$row['likes_count'],'ctsdcommentcount'=>$row['comments_count'],'ctsddatetime'=>date('Y-m-d H:i:s',substr($datetime,0,10)), 'ctsdtimestamp'=>$datetime);
    $cart->add($ctsdatainfo, 'cartoonsectiondatainfos');
  }
}

?>
