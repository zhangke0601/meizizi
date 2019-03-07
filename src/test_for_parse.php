<?php
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'inc/func.php');
ini_set('memory_limit','512M');

$cnt = file_get_contents($argv[1]);
$lines = explode("\n", $cnt);
foreach($lines as $line)
{
  $line = trim($line);
  $line = substr($line, strpos($line,'{'));
  $newdata = json_decode($line,true);
  $data = $newdata['body'];
  $ctdprice1 = '0';
  $ctdprice2 = '0';
  $ctdprice3 = '0';
  $ctdpriceval = $data['bookInfo']['priceInfo']['delPrice'];
  $ctdactive = $data['bookInfo']['priceInfo']['activePrice'];
  if(($ctdpriceval!='0'))
    $ctdprice1 = '1';
  if(($ctdpriceval!='0')||($ctdactive!='0'))
    $ctdprice2 = '1';
  if(isset($data['bookInfo']['bookId']))
    echo sprintf("%d %d %d\n", $data['bookInfo']['bookId'], $ctdprice1, $ctdprice2);
}

exit();

function mb_str_split($str,$split_length=1,$charset="UTF-8"){
  if(func_num_args()==1){
    return preg_split('/(?<!^)(?!$)/u', $str);
  }
  if($split_length<1)return false;
  $len = mb_strlen($str, $charset);
  $arr = array();
  for($i=0;$i<$len;$i+=$split_length){
    $s = mb_substr($str, $i, $split_length, $charset);
    $arr[] = $s;
  }
  return $arr;
}

function http_get_test($url,$t=30)
{
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
  curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt'); 
  curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
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
}

$cnt = file_get_contents($argv[1]);
$cnt = str_replace(array("\r\n", "\r", "\n"), '', $cnt);
$pos = strpos($cnt, 'err_material');
$cnt1 = substr($cnt, 0, $pos);
$cnt2 = substr($cnt, $pos+10);
$mates = array();
while(true)
{
  $pos = strpos($cnt2, 'err_material');
  if($pos === false)
  {
    $mates[] = $cnt2;
    break;
  }
  else
  {
    $mates[] = substr($cnt2,0,$pos);
    $cnt2 = substr($cnt2, $pos+10);
  }
}


$patt = '/peter-question-wrap.*?\/span>(.*?)<.*?<ul>(.*?)<\/ul>.*?blockquote.*?答案：(.*?)<.*?>([^<>]*?)<\/blockquote/';

preg_match($patt, $cnt1, $ret);
var_dump($ret);
$matequests = array();
foreach($mates as $idx=>$mate)
{
  $lpos = strpos($mate, '材料</span>');
  $rpos = strpos($mate, '<div', $lpos);
  $title = substr($mate,$lpos+strlen('材料</span>'), $rpos-$lpos-10-8);
  $quest = array('title'=>$title);
  $patt = '/secondary.*?\/span>(.*?)<.*?<ul>(.*?)<\/ul>.*?blockquote.*?答案：(.*?)<.*?>(.*?)<\/blockquote/';
  preg_match_all($patt, $mate, $ret);
  $quest = array('title'=>$title, 'subquests'=>$ret);
  $matequests[] = $quest;
}


exit();


exit();


$url = 'http://ncre.bjeea.cn/web/data/CreditViewAction.a';
$post_data = array();
$post_data['examId'] = 201;
$post_data['name'] = '马鲜';
$post_data['cardNo'] = '622628199703271489';
$post_data['getCreditOfStu'] = ' 重置 ';
$post_data['j_captcha_response'] = '5185';
$ret = http_post($url,$params);
var_dump($ret);



?>
