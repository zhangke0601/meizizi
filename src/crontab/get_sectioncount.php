<?php
/*
 * 用途：统计章节个数
 * 作者：feb1234@163.com
 * 时间：2018-03-14
 * */
$base_dir = dirname(__FILE__).'/../../';
require_once($base_dir.'config/config.php');
require_once($base_dir.'inc/util_for_shortmsg.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsPlatforminfos.php');

$logger = 'meizizicon.cron.get_sectioncount';
qlogConfig($base_dir.'config/qlog.cfg');
$cart = new Cartooninfos();
$sect = new CartoonSectioninfos();

$ctinfos = $cart->getidinfos();
foreach($ctinfos as $ctinfo)
{
  $count = $sect->GetSectionCountByCtid($ctinfo['ctid']);
  $cart->update($ctinfo['ctid'], array('ctsectioncount'=>$count));
}




?>
