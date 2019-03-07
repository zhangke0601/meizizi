<?php
/*
 * 用途：解析抓取的U17_some数据，并入库
 * 作者：feb1234@163.com
 * 时间：2018-07-15
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsDictinfos.php');
ini_set('memory_limit','4512M');

$cart = new Cartooninfos();
$dict = new Dictinfos();
$sect = new CartoonSectioninfos();

$path = $argv[1];
$datetime = substr($path, strrpos($path,'.')+1);
$datatime = date('Y-m-d H:i:s',substr($datetime,10));

//$cnt = file_get_contents($path);
$fp = fopen($path,'r');

$rds = array();
while(!feof($fp))
{
  $line1 = fgets($fp, 10*1024);
  $line1 = substr($line1, strpos($line1,'{'));
  $ldata = json_decode($line1, true);
  $cid = $ldata['data']['returnData']['comic']['comic_id'];
  //if(!isset($rds[$cid]))
  if(isset($ldata['data']['returnData']['comic']['user_id']))
    $rds[$cid]['f'] = $line1;
  else
    $rds[$cid]['s'] = $line1;

  if(count($rds) >= 10000){
    execu();
  }
}

function execu(){
  global $rds;
  global $cart;
  global $sect;
  global $datetime;
  global $dict;
  foreach($rds as $idx=>$row)
  {
    if(count($row) != 2)
      continue;
    $line1 = $row['f'];
    $line2 = GetItemFromArray($row,'s');
    if((strlen($line1)<100) || (strlen($line2)<100)) continue;
    $ldata = json_decode($line1,true);
    $sdata = json_decode($line2,true);
    $data = $sdata['data']['returnData'];
    $cartinfo = array('uid'=>0,'ctsource'=>SOURCE_U17, 'ctsourceid'=>$data['comic']['comic_id'],'cttype'=>5);
    $cartinfo['ctname'] = $data['comic']['name'];
    $cartinfo['ctauthorname'] = $data['comic']['author']['name'];
    $cartinfo['ctprogress'] = ($data['comic']['series_status']=='1')?PROGRESS_OVER:PROGRESS_NORMAL;
    $cartinfo['ctverticalimage'] = $data['comic']['cover'];
    $cartinfo['cthorizontalimage'] = '';
    $cartinfo['ctdesc'] = $data['comic']['description'];
    $ctinfo = $cart->CartoonExistForSourceAndSourceid($cartinfo['ctsource'], $cartinfo['ctsourceid']);
    if($ctinfo){
      $ctid = $ctinfo['ctid'];
      if($ctinfo['ctname'] != $data['comic']['name']){
        $cart->update($ctid, array('ctlatestname'=>$data['comic']['name']));
      }
    }else{
      //$ctinfo = $cart->CartoonExistForNameAndAuthor($cartinfo['ctname'], $cartinfo['ctauthorname']);
      //if($ctinfo)
      //  $ctid = $ctinfo['ctid'];
      //else
      $ctid = $cart->add($cartinfo);
      $csinfo = array('ctid'=>$ctid, 'cssource'=>SOURCE_U17, 'cssourceid'=>$data['comic']['comic_id']);
      $cart->add($csinfo,'cartoonsourceinfos');
    }

  /*$tags = $data['comic']['theme_ids'];
  foreach($tags as $tag)
  {
    $tag = trim($tag);
    if(strlen($tag) > 0)
    {
      $taginfo = $dict->GetTagInfoByName($tag);
      if(empty($taginfo))
        $cttid = $dict->add(array('cttname'=>$tag),'cartoontaginfos');
      else
        $cttid = $taginfo['cttid'];
      $dict->AddCartoonAndTag($ctid, $cttid);
    }
  }*/
    $cpl = $sdata['data']['returnData']['chapter_list'];
    $cpl = SetKeyFromArray($cpl,'chapter_id');

    $totalfee = 0;
    foreach($ldata['data']['returnData']['chapter_list'] as $row)
      //foreach($data['chapter_list'] as $row)
    {
      $ctsinfos = $sect->ExistSectionByCtidAndPfidAndSourceId($ctid,SOURCE_U17,$row['chapter_id']);
      if(empty($ctsinfos)){
        $ctsectinfo = array('ctid'=>$ctid,'ctsname'=>$cpl[$row['chapter_id']]['name'], 'ctscover'=>'', 'ctscontent'=>'', 'ctssource'=>SOURCE_U17, 'ctssourceid'=>$row['chapter_id']);
        $ctsid = $cart->add($ctsectinfo, 'cartoonsectioninfos');
      }else{
        $ctsid = $ctsinfos[0]['ctsid'];
      }

      $fee = 0;
      $vip_images = $row['vip_images'];
      $is_view = $row['is_view'];
      if(($vip_images!='0') && ($is_view=='0'))
        $fee = 2;
      elseif(($vip_images=='0') && ($is_view=='0'))
        $fee = 1; //VIP免费
      if($totalfee < $fee)
        $totalfee = $fee;

      $ctsdatainfo = array('ctsid'=>$ctsid, 'ctsdprice'=>$fee, 'ctsdbrowsercount'=>0,'ctsddatetime'=>date('Y-m-d H:i:s',substr($datetime,0,10)), 'ctsdtimestamp'=>$datetime);
      $cart->add($ctsdatainfo, 'cartoonsectiondatainfos');
    }

  /*$fee = 0;
  $vip_images = $ldata['data']['returnData']['comic']['vip_images'];
  $is_view = $ldata['data']['returnData']['comic']['is_view'];
  if(($vip_images!='0') && ($is_view=='0'))
    $fee = 1;
  elseif(($vip_images=='0') && ($is_view=='0'))
  $fee = 2; //VIP免费*/
    $ctdcreateat = 0;
    foreach($data['chapter_list'] as $row)
    {
      if($ctdcreateat == 0)
        $ctdcreateat = $row['pass_time'];
      elseif($ctdcreateat > $row['pass_time'])
        $ctdcreateat = $row['pass_time'];
    }

    $cartdatainfo = array('ctid'=>$ctid,'ctdsource'=>SOURCE_U17,
      'ctdbrowsercount'=>$ldata['data']['returnData']['comic']['total_click'],
      'ctdcollectcount'=>$ldata['data']['returnData']['comic']['favorite_total'],
      'ctdprice'=>$totalfee,'ctdcreateat'=>date('Y-m-d H:i:s', $ctdcreateat),
      'ctdtotalticket'=>$ldata['data']['returnData']['comic']['total_ticket'],
      'ctdmonthticket'=>$ldata['data']['returnData']['comic']['month_ticket'],
      'ctddatetime'=>date('Y-m-d H:i:s',substr($datetime,0,10)),
      'ctdtimestamp'=>$datetime,
      'ctdkeyname'=>implode(',', $data['comic']['theme_ids']),
      'ctdupdateat'=>date('Y-m-d H:i:s', $data['comic']['last_update_time']),
      'ctdupdateatval'=>$data['comic']['last_update_time']);

    $cartdatainfo['ctdname'] = $cartinfo['ctname'];
    $cartdatainfo['ctdauthorname'] = $cartinfo['ctauthorname'];
    $cartdatainfo['ctdprogress'] = $cartinfo['ctprogress'];
    $cartdatainfo['ctdsourceid'] = $cartinfo['ctsourceid'];
    $cartdatainfo['ctdsectioncount'] = count($data['chapter_list']);

    $cart->add($cartdatainfo,'cartoondatainfos');

    unset($rds[$idx]);
  }
  $rds = array_values($rds);
}

?>
