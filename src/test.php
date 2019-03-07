<?php
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'inc/func.php');



$cookies = 'mm_lang=zh_CN;webwxuvid=cd7abbc0395cf7f5cb5be203d753baefbdcb100d1c884c54601740bd8acd1135a1b3352ea4d96decd0a0dedaf12a7fc6;webwx_auth_ticket=CIsBENfy+t4CGoABFUrPRhW92D+Ck2upg8r7b/zHJ1HHRZoiAQ34ZaR3Ib+khIj4UYDTEzNlm/eEUmHPrqZcZQalS6kfKcgJ98M12AQ06xo3CiRjQhKscTFxiJD271gmdWqrIgb3HYX76qMOt1K4iPs0BaMhhyyNQOFrmmA/hBcMhDOAEZnswNH5GG4=;wxloadtime=1532425718_expired;wxpluginkey=1532422682;wxuin=619347280;wxsid=kKkKLTwKKGKNvKwq;webwx_data_ticket=gScBPq7CC2T3VwXu2hqxPWhf;';
$headers = get_request_headers($cookies);
$url = 'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxgeticon?seq=1857852855&username=@1bc0a5e7eac2decbcb523c08dcb61b83&skey=@crypt_62a40860_7658dda2d0c1bbf5f0a6f40f9ba6d21c';
$getret = http_get_si($url, 30, $headers);
file_put_contents('/Users/fengerbo/Downloads/testvbot.jpg', $getret);

function get_request_headers($cookies)
{/*{{{*/
  $headers = array(
    "Upgrade-Insecure-Requests: 1",
    "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36",
    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
    //"Referer: http://ac.qq.com/loginSuccess.html?url=http%3A%2F%2Fac.qq.com%2FMyComic%3Fauth%3D1&has_onekey=1",
    'Accept-Encoding: gzip, deflate',
    'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,nl;q=0.7,ja;q=0.6,zh-TW;q=0.5',
    'Cookie: '.$cookies
  );

  return $headers;
}/*}}}*/

function http_get_si($url,$t=30,$headers=array())
{/*{{{*/
  $ret = '';
  $ch = curl_init();    
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, $t);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $t);
  curl_setopt($ch, CURLOPT_NOSIGNAL, 1 );
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);
  curl_setopt($ch, CURLOPT_REFERER, $url);
  if(empty($headers))
  {
    curl_setopt($ch, CURLOPT_COOKIEJAR, get_cookie_file()); 
    curl_setopt($ch, CURLOPT_COOKIEFILE, get_cookie_file());
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36');
  }
  else
  {
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  }
  $ret = curl_exec($ch);
  if(!curl_errno($ch))
  {
    $info = curl_getinfo($ch);
    $httpHeaderSize = $info['header_size'];  //header字符串体积
    $pHeader = substr($ret, 0, $httpHeaderSize); //获得header字符串
    $arr = explode("\n", $pHeader);
    $rartype = '';
    foreach($arr as $line)
    {
      $split = 'Content-Encoding';
      if(strpos($line, $split) !== false)
      {
        $rartype = trim(substr($line, strlen($split)+1));
      }
    }

    $ret = substr($ret, $httpHeaderSize);
    if($rartype == 'gzip')
    {
      $ret = gzdecode($ret);
    }
  }
  curl_close($ch);
  return $ret;
}/*}}}*/
?>
