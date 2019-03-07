<?php
/*
 * 用途：管理7牛云存储管理
 * 作者：feb1234@163.com
 * 时间：2016-05-17
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'config/config.php');
require_once($base_dir.'inc/qiniu_php-sdk-7.0.7/autoload.php');
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

function UploadImg($localpath, $prev='', $bname='meizizi')
{
  global $qiniuaccesskey;
  global $qiniusecretkey;
  global $qiniuoutlink;

  $auth = new Auth($qiniuaccesskey, $qiniusecretkey);
  $token = $auth->uploadToken($bname);
  $uploadMgr = new UploadManager();
  $newname = sprintf('%s%s_%s', empty($prev)?'':sprintf("%s_", $prev), time(), basename($localpath));

  list($ret, $err) = $uploadMgr->putFile($token, $newname, $localpath);

  $url = false;
  if ($err === null) {
    $url = sprintf("%s/%s", $qiniuoutlink, $ret['key']);
  }

  return $url;
}

?>
