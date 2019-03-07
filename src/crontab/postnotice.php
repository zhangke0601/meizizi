<?php
/*
 * 用途：发布公告
 * 作者：feb1234@163.com
 * 时间：2018-03-13
 * */
$base_dir = dirname(__FILE__).'/../../';
require_once($base_dir.'config/config.php');
require_once($base_dir.'inc/util_for_shortmsg.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsPlatforminfos.php');
require_once($base_dir.'model/clsNoticeinfos.php');
require_once($base_dir.'model/clsUserinfos.php');

$logger = 'meizizicon.cron.postnotice';
qlogConfig($base_dir.'config/qlog.cfg');
$cart = new Cartooninfos();
$notice = new Noticeinfos();
$user = new Userinfos();

$ninfos = $notice->GetPostingInfos();
foreach($ninfos as $ninfo)
{
  $nposttype = $ninfo['nposttype'];
  $nusertype = $ninfo['nusertype'];
  $nuserlist = str_split_to_int($ninfo['nuserlist'],',');
  $uinfos = array();
  if($nusertype == 1){
    $uinfos = $user->getinfos(sprintf('ustate!=%d', STATE_DEL));
  }else{
    $uinfos = $user->getinfos(sprintf('uid in (%s)', implode(',', $nuserlist)));
  }

  foreach($uinfos as $uinfo){
    $uaninfo = array('uid'=>$uinfo['uid'], 'nid'=>$ninfo['nid']);
    $notice->AddPostNotice($uaninfo);
    if($nposttype == 1){
      sleep(1);
      $ret = ShortMsgForYinghuaAtGonggao($uinfo['umobile']);
      qLogInfo($logger, sprintf("%s %s", $uinfo['umobile'], $ret));
    }
  }

  $notice->update($ninfo['nid'], array('nstate'=>STATE_POST));
}

?>

