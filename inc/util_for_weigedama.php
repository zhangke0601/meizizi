<?php
/*
 * 用途：微格验证码识别
 * 作者：feb1234@163.com
 * 时间：2018-04-24
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'config/config.php');

function weige_decode($imgfile){
  global $weige_appkey;
  global $weige_appsecret;
  global $weige_appcode;

  $host = "https://302307.market.alicloudapi.com";
  $path = "/ocr/captcha";
  $method = "POST";
  $appcode = $weige_appcode;
  $headers = array();
  array_push($headers, "Authorization:APPCODE " . $appcode);
  //根据API的要求，定义相对应的Content-Type
  array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");
  $querys = "";
  $bodys = http_build_query(array("image"=>'data:image/jpeg;base64,'.base64_encode(file_get_contents($imgfile)),'type'=>1001));
  $url = $host . $path;

  $curl = curl_init();
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($curl, CURLOPT_FAILONERROR, false);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  //curl_setopt($curl, CURLOPT_HEADER, true);
  if (1 == strpos("$".$host, "https://"))
  {
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
  }
  curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
  $ret=curl_exec($curl);

  return json_decode($ret, true);
}

?>
