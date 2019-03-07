<?php
/*
 * 用途：短信相关函数
 * 作者：feb1234@163.com
 * 时间：2015-11-25
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'config/config.php');
//require_once($base_dir."src/taobao-sdk-PHP-auto_1455552377940-20160416/TopSdk.php");
require_once($base_dir.'inc/func.php');
//require_once($base_dir.'inc/util_for_taobao.php');

function GetVerifyCode($n=6)
{/*{{{*/
  $vcode = '';
  for($i=0; $i<$n; ++$i)
    $vcode .= rand(0,9);
  return $vcode;
}/*}}}*/

function SetVerifyCode($mobile, $vcode)
{/*{{{*/
  $_SESSION['vcodeinfo']['mobile'] = $mobile;
  $_SESSION['vcodeinfo']['vcode']  = $vcode;
}/*}}}*/

function VcodeVerify($mobile, $vcode)
{/*{{{*/
  $verify = false;
  $vcodeinfo = GetItemFromArray($_SESSION,'vcodeinfo', array());
  $vmobile = GetItemFromArray($vcodeinfo, 'mobile');
  $vvcode  = GetItemFromArray($vcodeinfo, 'vcode');
  if(strlen($mobile) && strlen($vcode))
  {
    if(($mobile==$vmobile) && ($vcode==$vvcode))
      $verify = true;
  }
  return $verify;
}/*}}}*/

/*{{{ 麦讯通 */
function ShortMsgSubmitForMxt($mobile, $message)
{/*{{{*/
  global $logger;
  $params = array('UserID'=>SHORTMSGUSERID,
    'Account'=>SHORTMSGACCOUNT,'Password'=>SHORTMSGPASSWD,
    'Phones'=>$mobile, 'SendType'=>1,'SendTime'=>'',
    'PostFixNumber'=>'','Content'=>$message
  );
  $url = SHORTMSGURL.http_build_query($params);
  $ret = http_get($url,3);
  qLogInfo($logger, sprintf("sendshortmsg\t%s\t%s", json_encode($params), json_encode($ret)));
  $arr = @simplexml_load_string($ret);
  $retcode = (string)$arr->RetCode;
  return $retcode;
}/*}}}*/
/*}}}*/

/*{{{ 盈华讯方 */
function ShortMsgForYinghua($mobile, $vcode)
{/*{{{*/
  $params = array('appid'=>YINGHUA_VERIFYAPPID);
  $params['mch_id'] = YINGHUA_MCHID;
  $params['nonce_str'] = '1234567890987654321';
  $params['validate_type'] = 1;
  $params['terminal_ip'] = '118.190.156.233';
  $params['out_trade_no'] = strval(time());
  $params['phone_num'] = $mobile;
  $params['ver_code'] = $vcode;
  $params['diy_voice'] = 0;
  $params['sign'] = SignForYinghua($params, YINGHUA_VERIFYSECRET);
  
  $xml = '<xml>';
  foreach($params as $k=>$v)
    $xml .= sprintf("<%s>%s</%s>", $k, $v, $k);
  $xml .= '</xml>';

  $url = 'http://voicesmsapi.vnetone.com/Order/Add';
  $ret = http_post($url, $xml);
  return $ret;
}/*}}}*/

function ShortMsgForYinghuaAtInvalidCookie($mobile)
{/*{{{*/
  $params = array('appid'=>YINGHUA_COOKIEAPPID);
  $params['mch_id'] = YINGHUA_MCHID;
  $params['nonce_str'] = '1234567890987654321';
  $params['validate_type'] = 3;
  $params['terminal_ip'] = '118.190.156.233';
  $params['out_trade_no'] = strval(time());
  $params['phone_num'] = $mobile;
  $params['content'] = '美滋滋管理后台有失效COOKIE，请处理！';
  $params['diy_voice'] = 0;
  $params['sign'] = SignForYinghua($params, YINGHUA_COOKIESECRET);
  
  $xml = '<xml>';
  foreach($params as $k=>$v)
    $xml .= sprintf("<%s>%s</%s>", $k, $v, $k);
  $xml .= '</xml>';

  $url = 'http://voicesmsapi.vnetone.com/Order/Add';
  $ret = http_post($url, $xml);
  return $ret;
}/*}}}*/

function ShortMsgForYinghuaAtCipanFull($mobile)
{/*{{{*/
  $params = array('appid'=>YINGHUA_FULLAPPID);
  $params['mch_id'] = YINGHUA_MCHID;
  $params['nonce_str'] = '1234567890987654321';
  $params['validate_type'] = 3;
  $params['terminal_ip'] = '118.190.156.233';
  $params['out_trade_no'] = strval(time());
  $params['phone_num'] = $mobile;
  $params['content'] = '美滋滋服务器磁盘满，请处理。';
  $params['diy_voice'] = 0;
  $params['sign'] = SignForYinghua($params, YINGHUA_FULLSECRET);
  
  $xml = '<xml>';
  foreach($params as $k=>$v)
    $xml .= sprintf("<%s>%s</%s>", $k, $v, $k);
  $xml .= '</xml>';

  $url = 'http://voicesmsapi.vnetone.com/Order/Add';
  $ret = http_post($url, $xml);
  return $ret;
}/*}}}*/

function ShortMsgForYinghuaAtGonggao($mobile)
{/*{{{*/
  $params = array('appid'=>YINGHUA_GONGGAOAPPID);
  $params['mch_id'] = YINGHUA_MCHID;
  $params['nonce_str'] = '1234567890987654321';
  $params['validate_type'] = 3;
  $params['terminal_ip'] = '118.190.156.233';
  $params['out_trade_no'] = strval(time());
  $params['phone_num'] = $mobile;
  $params['content'] = '有新的公告，请登录美滋滋查看。';
  $params['diy_voice'] = 0;
  $params['sign'] = SignForYinghua($params, YINGHUA_GONGGAOSECRET);
  
  $xml = '<xml>';
  foreach($params as $k=>$v)
    $xml .= sprintf("<%s>%s</%s>", $k, $v, $k);
  $xml .= '</xml>';

  $url = 'http://voicesmsapi.vnetone.com/Order/Add';
  $ret = http_post($url, $xml);
  return $ret;
}/*}}}*/

function SignForYinghua($params, $key)
{
  $buff = '';
  ksort($params);
  foreach ($params as $k => $v) {
    $buff .= $k . "=" . $v . "&";
  }
  if (strlen($buff) > 0) {
    $buff = substr($buff, 0, strlen($buff)-1);
  }

  $buff .= sprintf('&key=%s', $key);
  $sign = strtoupper(md5($buff));

  return $sign;
}

/*}}}*/

function GenerateTopClient()
{
  global $base_dir;
  require_once($base_dir.'src/taobao-sdk-PHP/TopSdk.php');
  global $taobaodayuappkey,$taobaodayuappsecret,$taobaogatewayurl;
  $client = new ClusterTopClient($taobaodayuappkey,$taobaodayuappsecret);
  $client->format = 'json';
  $client->gatewayUrl = $taobaogatewayurl;
 
  return $client;
}

/*{{{ Worker */
function ShortMsgSubmitForWorkerVerify($mobile,$vcode)
{
  $params = array('code'=>$vcode);
  return ShortMsgSubmitForDayu($mobile,'SMS_7791025',$params);
}

function ShortMsgSubmitForWorkerForgot($mobile, $passwd)
{
  $params = array('passwd'=>$passwd);
  return ShortMsgSubmitForDayu($mobile,'SMS_7821072',$params);
}

function ShortMsgSubmitForOwnerDispatch($mobile, $wmobile, $wpersonname, $orderid)
{
  $params = array('wmobile'=>$wmobile, 'wpersonname'=>$wpersonname, 'orderid'=>$orderid);
  return ShortMsgSubmitForDayu($mobile,'SMS_7791026',$params);
}

function ShortMsgSubmitForConfirmcode($mobile,$orderid,$wpersonname,$confirmcode)
{
  $params = array('oconfirmcode'=>$confirmcode, 'wpersonname'=>$wpersonname, 'orderid'=>$orderid);
  return ShortMsgSubmitForDayu($mobile,'SMS_7811046',$params);
}
/*}}}*/

/*{{{ Owner */
function ShortMsgSubmitForOwnerVerify($mobile, $vcode)
{
  $params = array('code'=>$vcode);
  return ShortMsgSubmitForDayu($mobile,'SMS_7781040',$params);
}
/*}}}*/

/*{{{ Shop */
function ShortMsgSubmitForShopVerify($mobile, $vcode)
{
  $params = array('code'=>$vcode);
  return ShortMsgSubmitForDayu($mobile,'SMS_7816262',$params);
}
/*}}}*/

function ShortMsgSubmitForDayu($mobile,$msgtemplate,$params)
{
  global $base_dir;
  global $taobaosessionkey;
  //require_once($base_dir.'src/taobao-sdk-PHP-auto_1455552377940-20160416/TopSdk.php');
  $client = GenerateTopClient();
  $req = new AlibabaAliqinFcSmsNumSendRequest;
  $req->setSmsType("normal");
  $req->setSmsFreeSignName("e帮手");
  $req->setSmsParam(json_encode($params));
  $req->setRecNum($mobile);
  $req->setSmsTemplateCode($msgtemplate);
  $resp = $client->execute($req,$taobaosessionkey);
  return $resp;
}

?>
