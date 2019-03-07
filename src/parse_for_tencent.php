<?php
/*
 * 用途：解析抓取的腾讯数据，并入库
 * 作者：feb1234@163.com
 * 时间：2017-10-03
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsDictinfos.php');
ini_set('memory_limit','2500M');

$cart = new Cartooninfos();
$sect = new CartoonSectioninfos();
$dict = new Dictinfos();

$path = $argv[1];
$datetime = substr($path, strrpos($path,'.')+1);
$datatime = date('Y-m-d H:i:s',substr($datetime,10));

$cnt = file_get_contents($path);
$lines = explode("\n", $cnt);
$count = floor(count($lines)/2);
for($i=0; $i<$count; ++$i)
{
  $line1 = trim($lines[$i*2]);
  $line2 = trim($lines[$i*2+1]);
  if((strlen($line1)<100) || (strlen($line2)<100)) continue;
  $line1 = substr($line1, strpos($line1,'{'));
  $line2 = substr($line2, strpos($line2,'{'));
  $ldata = json_decode($line1,true);
  $sdata = json_decode($line2,true);
  $data = $ldata['data'];
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
    //$ctinfo = $cart->CartoonExistForNameAndAuthor($cartinfo['ctname'], $cartinfo['ctauthorname']);
    //if($ctinfo)
    //  $ctid = $ctinfo['ctid'];
    //else
      $ctid = $cart->add($cartinfo);
    $csinfo = array('ctid'=>$ctid, 'cssource'=>SOURCE_TENCENT, 'cssourceid'=>$data['comic']['comic_id']);
    $cart->add($csinfo,'cartoonsourceinfos');
  }

  /*$tagstr = $data['comic']['type'];
  if($tagstr)
  {
    $tags = explode(' ', $tagstr);
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
    }
  }*/

  $update_time = $data['comic']['update_time'];
  if(strlen($update_time) <= 5)
    $data['comic']['update_time'] = sprintf('%s-%s', date('Y'), $update_time);

  $sect_update_time = '';
  foreach($sdata['data'] as $row)
  {
    $n = $row['update_time'];
    if(strlen($n) <= 5)
      $n = sprintf("%s-%s", date('Y'), $n);
    if(empty($sect_update_time))
      $sect_update_time = $n;
    else{
      if($n < $sect_update_time)
        $sect_update_time = $n;
    }

    $ctsinfos = $sect->ExistSectionByCtidAndPfidAndSourceId($ctid,SOURCE_TENCENT,$row['chapter_id']);
    if(empty($ctsinfos)){
      $ctsectinfo = array('ctid'=>$ctid,'ctsname'=>$row['chapter_title'], 'ctscover'=>$row['chapter_cover_url'], 'ctscontent'=>'', 'ctssource'=>SOURCE_TENCENT, 'ctssourceid'=>$row['chapter_id']);
      $ctsid = $cart->add($ctsectinfo, 'cartoonsectioninfos');
    }else{
      $ctsid = $ctsinfos[0]['ctsid'];
    }
    $ctsdatainfo = array('ctsid'=>$ctsid, 'ctsdbrowsercount'=>0, 'ctsdgoodcount'=>$row['good_count'],'ctsddatetime'=>date('Y-m-d H:i:s',substr($datetime,0,10)), 'ctsdtimestamp'=>$datetime);
    $cart->add($ctsdatainfo, 'cartoonsectiondatainfos');
  }

  $cartdatainfo = array('ctid'=>$ctid,'ctdsource'=>SOURCE_TENCENT, 'ctdbrowsercount'=>$data['comic']['pgv_count'], 'ctdcollectcount'=>$data['comic']['coll_count'], 'ctdmonthticket'=>$data['comic']['month_ticket'],'ctdmonthticketrank'=>$data['comic']['month_ticket_rank'],'ctdprice'=>$data['comic']['comic_price'],'ctdgrade'=>$data['comic']['grade_ave'],'ctdgradecount'=>$data['comic']['grade_count'], 'ctddatetime'=>date('Y-m-d H:i:s',substr($datetime,0,10)),'ctdtimestamp'=>$datetime,'ctdkeyname'=>$data['comic']['type'],'ctdupdateatval'=>$data['comic']['update_time']);

  $cartdatainfo['ctdname'] = $cartinfo['ctname'];
  $cartdatainfo['ctdauthorname'] = $cartinfo['ctauthorname'];
  $cartdatainfo['ctdprogress'] = $cartinfo['ctprogress'];
  $cartdatainfo['ctdsourceid'] = $cartinfo['ctsourceid'];
  $cartdatainfo['ctdsectioncount'] = count($sdata['data']);
  if(!empty($sect_update_time))
    $cartdatainfo['ctdcreateat'] = $sect_update_time;
  $cart->add($cartdatainfo,'cartoondatainfos');

}

?>
