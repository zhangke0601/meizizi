<?php
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'inc/func.php');
require_once($base_dir.'inc/HttpClient.php');

$type = $argv[1];

if($type == 'getimage')
{
  $url = 'https://ipcrs.pbccrc.org.cn/';
  //$cnt = http_get($url);
  $cnt = HttpClient::get_instance()->_GET($url);
  //var_dump($cnt);

  $url = 'https://ipcrs.pbccrc.org.cn/login.do?method=initLogin';
  $cnt = HttpClient::get_instance()->set_refer('https://ipcrs.pbccrc.org.cn/');
  $cnt = HttpClient::get_instance()->_GET($url);
  $pattern = '/<input.*?name="(.*?)".*?value="(.*?)"/';
  preg_match_all($pattern, $cnt ,$r);
  var_dump($cnt);
  var_dump($r);

  $url = 'https://ipcrs.pbccrc.org.cn/imgrc.do?a='.time();
  $cnt = HttpClient::get_instance()->set_refer('https://ipcrs.pbccrc.org.cn/');
  $cnt = HttpClient::get_instance()->_GET($url);
  //file_put_contents($cnt, '/Users/fengerbo/Downloads/1.jpg');
  file_put_contents( '/Users/fengerbo/Downloads/1.jpg', $cnt);
}
else
{
  $code = $argv[2];
  $token = $argv[3];
  $date = $argv[4];
  $url = 'https://ipcrs.pbccrc.org.cn/login.do';
  $params = array('method'=>'login', 'date'=>$date, 'loginname'=>'fengerbo', 'password'=>'850420lly', '_@IMGRC@_'=>$code, 'org.apache.struts.taglib.html.TOKEN'=>$token);
  //$cnt = http_post($url, $params);
  HttpClient::get_instance()->set_refer('https://ipcrs.pbccrc.org.cn/page/login/loginreg.jsp');
  // https://ipcrs.pbccrc.org.cn/page/login/loginreg.jsp
  $cnt = HttpClient::get_instance()->_POST($url, $params, NULL, NULL, 'application/x-www-form-urlencoded');
  var_dump($params, $cnt);

  //$url = 'https://ipcrs.pbccrc.org.cn/reportAction.do?method=applicationReport';
  //$cnt = HttpClient::get_instance()->_GET($url);
  //var_dump($cnt);

}
?>
