<?php
/*
 * 用途：检查状态
 *     检查是否有无效COOKIE，如果存在发送短信通知
 * 作者：feb1234@163.com
 * 时间：2017-12-29
 * */
$base_dir = dirname(__FILE__).'/../../';
require_once($base_dir.'config/config.php');
require_once($base_dir.'inc/util_for_shortmsg.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsPlatforminfos.php');

$logger = 'meizizicon.cron.checkstate';
qlogConfig($base_dir.'config/qlog.cfg');
$cart = new Cartooninfos();
$sect = new CartoonSectioninfos();
$pf = new Platforminfos();

/*{{{ 检查是否存在无效COOKIES */
$upfinfos = $pf->GetInvalidCookieInfos();
if(count($upfinfos) > 0)
{
  $mobile = '15222287187';
  $ret = ShortMsgForYinghuaAtInvalidCookie($mobile);
  qLogInfo($logger, sprintf("%s %s", $mobile, $ret));

  sleep(1);
  $mobile = '17610588612';
  $ret = ShortMsgForYinghuaAtInvalidCookie($mobile);
  qLogInfo($logger, sprintf("%s %s", $mobile, $ret));

  /*sleep(1);
  $mobile = '15910350235';
  $ret = ShortMsgForYinghuaAtInvalidCookie($mobile);
  qLogInfo($logger, sprintf("%s %s", $mobile, $ret));*/
}
/*}}}*/




?>
