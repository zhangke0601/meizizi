<?php
/*
 * 用途：解析抓取的漫画岛数据，并入库
 * 作者：feb1234@163.com
 * 时间：2017-10-03
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
ini_set('memory_limit','512M');
$cart = new Cartooninfos();
$sect = new CartoonSectioninfos();

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
  if(empty($ldata['comicsdetail']))
  {
    continue;
  }
  $data = $ldata['comicsdetail'][0];
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
  $ctdprice = '0';
  $ctdpriceval = $data['sourceprice'];
  if(!empty($ctdpriceval))
  {
    if($ctdpriceval == '0.25')
      $ctdprice = 1;
    elseif($ctdpriceval == '0.29')
      $ctdprice = 2;
    else
      $ctdprice = 2;
  }



  $ctdcreateat = 0;
  if(isset($sdata['bookPartList'] ))
  {
    foreach($sdata['bookPartList'] as $row)
    {
      if(!isset($row['partcoverurl']))
        continue;

      $ctsinfos = $sect->ExistSectionByCtidAndPfidAndSourceId($ctid,SOURCE_MANHUADAO,$row['part_id']);
      if(empty($ctsinfos)){
        $ctsectinfo = array('ctid'=>$ctid,'ctsname'=>$row['name'], 'ctscover'=>$row['partcoverurl'], 'ctscontent'=>'', 'ctssource'=>SOURCE_MANHUADAO, 'ctssourceid'=>$row['part_id']);
        $ctsid = $cart->add($ctsectinfo, 'cartoonsectioninfos');
        $ctsdatainfo = array('ctsid'=>$ctsid,'ctsddatetime'=>date('Y-m-d H:i:s',substr($datetime,0,10)), 'ctsdtimestamp'=>$datetime);
        $cart->add($ctsdatainfo, 'cartoonsectiondatainfos');
      }else{
        $ctsid = $ctsinfos[0]['ctsid'];
      }
      if($ctdcreateat == 0)
        $ctdcreateat = $row['createtime'];
      elseif($ctdcreateat > $row['createtime'])
        $ctdcreateat = $row['createtime'];
    }
  }

  $cartdatainfo = array('ctid'=>$ctid, 'ctdsource'=>SOURCE_MANHUADAO, 'ctdbrowsercount'=>$data['bigbookview'],
    'ctdcommentcount'=>$data['discusscount'], 'ctdexclusive'=>$exclusive, 'ctdrank'=>$data['ranking'],'ctdkeyname'=>$data['subject_name'],
    'ctddatetime'=>date('Y-m-d H:i:s',substr($datetime,0,10)),'ctdtimestamp'=>$datetime, 'ctdpriceval'=>$ctdpriceval,'ctdcreateat'=>$ctdcreateat,
    'ctdupdateat'=>$ldata['comicssource'][0]['updatedate'],'ctdupdateatval'=>$ldata['comicssource'][0]['updatedate']);

  $cartdatainfo['ctdname'] = $cartinfo['ctname'];
  $cartdatainfo['ctdauthorname'] = $cartinfo['ctauthorname'];
  $cartdatainfo['ctdprogress'] = $cartinfo['ctprogress'];
  $cartdatainfo['ctdsourceid'] = $cartinfo['ctsourceid'];
  $cartdatainfo['ctdsectioncount'] = count($sdata['bookPartList']);
  $cart->add($cartdatainfo,'cartoondatainfos');

}

?>
