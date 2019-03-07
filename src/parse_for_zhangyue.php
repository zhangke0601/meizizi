<?php
/*
 * 用途：解析抓取的快看数据，并入库
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
foreach($lines as $line)
{
  $line = trim($line);
  if(strlen($line) < 100) continue;
  $line = substr($line, strpos($line,'{'));
  $newdata = json_decode($line,true);
  if($newdata['code'] == '404')
    continue;
  $data = $newdata['body'];
  $cartinfo = array('uid'=>0,'ctsource'=>SOURCE_ZHANGYUE, 'ctsourceid'=>$data['bookInfo']['bookId'],'cttype'=>5);
  $cartinfo['ctname'] = $data['bookInfo']['bookName'];
  $cartinfo['ctauthorname'] = $data['bookInfo']['author'];
  $cartinfo['ctprogress'] = ($data['bookInfo']['completeState']=='N')?PROGRESS_NORMAL:PROGRESS_OVER;
  $cartinfo['ctverticalimage'] = $data['bookInfo']['picUrl'];
  $cartinfo['cthorizontalimage'] = '';
  $cartinfo['ctdesc'] = $data['bookInfo']['desc'];
  $ctinfo = $cart->CartoonExistForSourceAndSourceid($cartinfo['ctsource'], $cartinfo['ctsourceid']);
  if($ctinfo){
    $ctid = $ctinfo['ctid'];
    if($ctinfo['ctname'] != $data['bookInfo']['bookName']){
      $cart->update($ctid, array('ctlatestname'=>$data['bookInfo']['bookName']));
    }
  }else{
    //$ctinfo = $cart->CartoonExistForNameAndAuthor($cartinfo['ctname'], $cartinfo['ctauthorname']);
    //if($ctinfo)
    //  $ctid = $ctinfo['ctid'];
    //else
      $ctid = $cart->add($cartinfo);
    $csinfo = array('ctid'=>$ctid, 'cssource'=>SOURCE_ZHANGYUE, 'cssourceid'=>$data['bookInfo']['bookId']);
    $cart->add($csinfo,'cartoonsourceinfos');
    /*foreach($data['bookInfo']['categorys'] as $category)
    {
      $cttpinfo = $cart->GetcartoontypeinfoBycttpname($category['name']);
      if(!empty($cttpinfo))
        $cttpid = $cttpinfo['cttpid'];
      else
      {
        $info = array('cttpname'=>$category['name']);
        $cttpid = $cart->add($info,'cartoontypeinfos');
      }
      $catpinfo = array('cttpid'=>$cttpid,'ctid'=>$ctid);
      $cart->add($catpinfo,'cartoonandtypeinfos');
    }*/
  }
  if(strpos($data['commentList']['circleInfo']['totalNum'],'W'))
    $totalNum = substr($data['commentList']['circleInfo']['totalNum'],0, strpos($data['commentList']['circleInfo']['totalNum'],'W')).'0000';
  else
    $totalNum = $data['commentList']['circleInfo']['totalNum'];
  if(strpos($data['commentList']['circleInfo']['fansNum'],'W'))
    $fansNum = substr($data['commentList']['circleInfo']['fansNum'],0, strpos($data['commentList']['circleInfo']['fansNum'],'W')).'0000';
  else
    $fansNum = $data['commentList']['circleInfo']['fansNum'];
  $keyname = implode(',', GetItemsFromArray($data['bookInfo']['tagInfo'],'name'));
  $ctdprice = '1';
  $isFree =  $data['bookInfo']['priceInfo']['isFree'];
  $ctdpriceval = $data['bookInfo']['priceInfo']['activePrice'];
  //$ctdactive = $data['bookInfo']['priceInfo']['activePrice'];
  if(($isFree===true) || ($ctdpriceval===0))
    $ctdprice = '0';
  $cartdatainfo = array('ctid'=>$ctid, 'ctdsource'=>SOURCE_ZHANGYUE, 'ctdbrowsercount'=>0,
    'ctdcollectcount'=>$data['bookInfo']['likeNum'],
    'ctddatetime'=>date('Y-m-d H:i:s',substr($datetime,0,10)),'ctdtimestamp'=>$datetime,
    'ctdcommentcount'=>$totalNum,'ctdgrade'=>$data['bookInfo']['star'],
    'ctdgradecount'=>$data['bookInfo']['voteNum'], 'ctdkeyname'=>$keyname,
    'ctdprice'=>$ctdprice, 'ctdpriceval'=>$ctdpriceval,
    'ctdupdateat'=>$data['bookInfo']['lastChapterTime'],
    'ctdupdateatval'=>$data['bookInfo']['orgStatus']);

  $cartdatainfo['ctdname'] = $cartinfo['ctname'];
  $cartdatainfo['ctdauthorname'] = $cartinfo['ctauthorname'];
  $cartdatainfo['ctdprogress'] = $cartinfo['ctprogress'];
  $cartdatainfo['ctdsourceid'] = $cartinfo['ctsourceid'];
  $cartdatainfo['ctdsectioncount'] = 0;//count($data['data']['comics']);

  $cart->add($cartdatainfo,'cartoondatainfos');
  if(isset($data['chaperInfo']))
  {
    $ctsectinfo = array('ctid'=>$ctid,'ctsname'=>$data['chaperInfo']['chapterName'], 'ctscover'=>'', 'ctscontent'=>'', 'ctssource'=>SOURCE_ZHANGYUE, 'ctssourceid'=>$data['chaperInfo']['chapterName'], 'ctstotal'=>$data['chaperInfo']['chapterNum']);
    $ctsinfo = $cart->SectionExistForName($ctid,$ctsectinfo['ctsname']);
    if($ctsinfo)
      $ctsid = $ctsinfo['ctsid'];
    else
      $ctsid = $cart->add($ctsectinfo, 'cartoonsectioninfos');
    $ctsdatainfo = array('ctsid'=>$ctsid, 'ctsdsource'=>SOURCE_ZHANGYUE, 'ctsdbrowsercount'=>0,'ctsddatetime'=>date('Y-m-d H:i:s',substr($datetime,0,10)), 'ctsdtimestamp'=>$datetime);
    $cart->add($ctsdatainfo, 'cartoonsectiondatainfos');
  }
}

?>
