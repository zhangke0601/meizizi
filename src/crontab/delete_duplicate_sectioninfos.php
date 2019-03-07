<?php
/*
 * 用途：删除重复章节
 * 作者：feb1234@163.com
 * 时间：2017-12-29
 * */
$base_dir = dirname(__FILE__).'/../../';
require_once($base_dir.'config/config.php');
require_once($base_dir.'inc/util_for_shortmsg.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsPlatforminfos.php');

$logger = 'meizizicon.cron.deleteduplicate';
qlogConfig($base_dir.'config/qlog.cfg');
$cart = new Cartooninfos();

//$ctid = 394584;
//$ctssource = SOURCE_U17;

$strsql = sprintf('select ctid,ctsectioncount,ctsource from cartooninfos where ctsectioncount>5000 and cttype=5 order by ctsectioncount');
$ctinfos = $cart->ExecuteRead($strsql);

foreach($ctinfos as $ctinfo){
  $ctid = $ctinfo['ctid'];
  $ctssource = $ctinfo['ctsource'];

  $strsql = sprintf('select distinct(ctssourceid) from cartoonsectioninfos where ctid=%d and ctssource=%d ', $ctid, $ctssource);
  $ctsinfos = $cart->ExecuteRead($strsql);
  foreach($ctsinfos as $info){
    $ctssourceid = $info['ctssourceid'];
    $strsql = sprintf('select * from cartoonsectioninfos where ctid=%d and ctssource=%d and ctssourceid="%s" order by ctsid ', $ctid, $ctssource,$ctssourceid);
    $infos = $cart->ExecuteRead($strsql);
    if($infos){
      $min = $infos[0]['ctsid'];
      $strsql = sprintf('delete from cartoonsectioninfos where ctid=%d and ctssource=%d and ctssourceid="%s" and ctsid>%d ', $ctid, $ctssource, $ctssourceid,$min);
      var_dump($strsql);
      $cart->ExecuteSql($strsql);
    }
  }
}


?>
