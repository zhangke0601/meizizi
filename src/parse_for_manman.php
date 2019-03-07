<?php
/*
 * 用途：解析抓取的漫漫数据，并入库
 * 作者：feb1234@163.com
 * 时间：2017-10-03
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/clsCartooninfos.php');

$cart = new Cartooninfos();

$path = $argv[1];
$datetime = substr($path, strrpos($path,'.')+1);
$datatime = date('Y-m-d H:i:s',substr($datetime,10));

$cnt = file_get_contents($path);
$lines = explode("\n", $cnt);
foreach($lines as $idx=>$line)
{
  $line = trim($line);
  if(strlen($line) < 100) continue;
  $line = substr($line, strpos($line,'{'));
  $data = json_decode($line,true);
  $cartinfo = array('uid'=>0,'ctsource'=>SOURCE_MANMAN, 'ctsourceid'=>$data['data']['id'],'cttype'=>5);
  $cartinfo['ctname'] = $data['data']['title'];
  //if(isset($data['data']['users'][0]) && isset($data['data']['status']) )
  {
    $nickname = '';
    if(isset($data['data']['users']))
      $nickname = $data['data']['users'][0]['nickname'];
    else{
      if(isset($data['data']['author'])){
        $nickname = $data['data']['author']['nickname'];
      }
    }
    $status = 1;
    if(isset($data['data']['status']))
      $status = $data['data']['status'];
    $cartinfo['ctauthorname'] = $nickname;
    $cartinfo['ctprogress'] = ($status=='2')?PROGRESS_OVER:PROGRESS_NORMAL;
    $cartinfo['ctverticalimage'] = $data['data']['cover_image_url'];
    $cartinfo['cthorizontalimage'] = '';
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
      $csinfo = array('ctid'=>$ctid, 'cssource'=>SOURCE_MANMAN, 'cssourceid'=>$data['data']['id']);
      $cart->add($csinfo,'cartoonsourceinfos');
      /*foreach($data['data']['cate'] as $cate)
      {
        $cttpinfo = $cart->GetcartoontypeinfoBycttpname($cate['name']);
        if(!empty($cttpinfo))
          $cttpid = $cttpinfo['cttpid'];
        else
        {
          $info = array('cttpname'=>$cate['name']);
          $cttpid = $cart->add($info,'cartoontypeinfos');
        }
        $catpinfo = array('cttpid'=>$cttpid,'ctid'=>$ctid);
        $cart->add($catpinfo,'cartoonandtypeinfos');
      }*/
    }
    $exclusive = 0;
    if(strpos($data['data']['description'], '漫漫独家') !== false)
      $exclusive = 1;
    $cartdatainfo = array('ctid'=>$ctid, 'ctdsource'=>SOURCE_MANMAN, 'ctdbrowsercount'=>$data['data']['reads'], 'ctdzancount'=>$data['data']['likes'], 'ctddatetime'=>date('Y-m-d H:i:s',substr($datetime,0,10)),'ctdtimestamp'=>$datetime,'ctdupdateat'=>date('Y-m-d H:i:s',$data['data']['comics'][0]['publish_time']),'ctdupdateatval'=>$data['data']['comics'][0]['publish_time'],'ctdkeyname'=>implode(',',array_values($data['data']['cate'])),'ctdexclusive'=>$exclusive);

    $cartdatainfo['ctdname'] = $cartinfo['ctname'];
    $cartdatainfo['ctdauthorname'] = $cartinfo['ctauthorname'];
    $cartdatainfo['ctdprogress'] = $cartinfo['ctprogress'];
    $cartdatainfo['ctdsourceid'] = $cartinfo['ctsourceid'];
    $cartdatainfo['ctdsectioncount'] = count($data['data']['comics']);
    $cart->add($cartdatainfo,'cartoondatainfos');

    foreach($data['data']['comics'] as $row)
    {
      $ctsectinfo = array('ctid'=>$ctid,'ctsname'=>$row['title'], 'ctscover'=>$row['cover_image_url'], 'ctscontent'=>'', 'ctssource'=>SOURCE_MANMAN, 'ctssourceid'=>$row['id']);
      $ctsinfo = $cart->SectionExistForName($ctid,$ctsectinfo['ctsname']);
      if($ctsinfo)
        $ctsid = $ctsinfo['ctsid'];
      else
        $ctsid = $cart->add($ctsectinfo, 'cartoonsectioninfos');
      $ctsdatainfo = array('ctsid'=>$ctsid, 'ctsdsource'=>SOURCE_MANMAN, 'ctsdbrowsercount'=>GetItemFromArray($row,'reads',0),'ctsddatetime'=>date('Y-m-d H:i:s',substr($datetime,0,10)), 'ctsdtimestamp'=>$datetime);

      $cart->add($ctsdatainfo, 'cartoonsectiondatainfos');
    }
  }


}

?>
