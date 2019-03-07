<?php
/*
 * 用途：将上传失败和驳回的记录置删除状态/一个星期后删除
 * 作者：feb1234@163.com
 * 时间：2018-06-05
 * */
$base_dir = dirname(__FILE__).'/../../';
require_once($base_dir.'config/config.php');
require_once($base_dir.'inc/util_for_shortmsg.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsPlatforminfos.php');

$logger = 'meizizicon.cron.deletefailandback';
qlogConfig($base_dir.'config/qlog.cfg');
$cart = new Cartooninfos();


$prevdate = date('Y-m-d H:i:s', time()-7*24*3600);
//$strsql = sprintf('select * from cartoonreleaserecordinfos where ctrrcreatetime<="%s" and ctrrstate in (%d,%d)', $prevdate, STATE_UPLOADFAIL, STATE_AUTHFAIL);
$strsql = sprintf('update cartoonreleaserecordinfos set ctrrstate=%d where ctrrcreatetime<="%s" and ctrrstate in (%d,%d)', STATE_DEL, $prevdate, STATE_UPLOADFAIL, STATE_AUTHFAIL);
$ret = $cart->ExecuteSql($strsql);



?>
