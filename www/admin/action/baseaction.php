<?php
/*
 * 用途：处理前端用户请求
 * 作者：feb1234@163.com
 * 时间：2017-09-09
 * */
$base_dir = dirname(__FILE__).'/../../';
require_once($base_dir.'inc/init.php');
require_once($base_dir.'inc/PHPExcel/PHPExcel.php');
require_once($base_dir.'inc/util_for_yibangshou.php');
require_once($base_dir.'inc/util_for_shortmsg.php');
require_once($base_dir.'config/config.php');
require_once($base_dir.'model/clsUserinfos.php');
require_once($base_dir.'model/clsFunc.php');

$ajaxret = array('retno'=>RETNO_FAIL);
$logger = 'meizizi.admin.user';

$params = $_POST;
$type = GetItemFromArray($params, 'type');
$user = new Userinfos();
$uinfo = $user->IsLogin();
if($type == '')
{/*{{{*/
}/*}}}*/
else
{
  if($uinfo !== false)
  {
    if($type == '')
    {/*{{{*/
    }/*}}}*/
    else
    {
      $ajaxret['retno'] = RETNO_INVALIDOPT;
      $ajaxret['msg'] = '无效操作';
    }
  }
  else
  {
    $ajaxret['retno'] = RETNO_NOTLOGIN;
    $ajaxret['msg'] = '登陆超时';
  }
}

echo json_encode($ajaxret);
if($uinfo !== false)
  $ajaxret['uid'] = $uid;
qLogInfo($logger, json_encode($ajaxret));
?>
