<?php
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'config/config.php');
//require_once($base_dir."inc/class.phpmailer.php");   
require_once($base_dir.'inc/PHPMailer-master/src/Exception.php');
require_once($base_dir.'inc/PHPMailer-master/src/PHPMailer.php');
require_once($base_dir.'inc/PHPMailer-master/src/SMTP.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function GetItemFromArray($arr, $key, $default='')
{
  $item = $default;
  if(isset($arr[$key]))
    $item = $arr[$key];

  if(is_string($item))
    $item = trim($item);

  return $item;
}

function str_split_to_int($str, $ex='|')
{
  $a = array();
  $arr = explode($ex, $str);
  foreach($arr as $s)
  {
    $s = intval($s);
    if($s > 0)
      $a[] = $s;
  }
  return $a;
}

function GetItemsFromArray($arr, $key)
{
  $items = array();
  foreach($arr as $row)
  {
    if(isset($row[$key]))
      $items[] = $row[$key];
  }
  return $items;
}

function SetKeyFromArray($arr, $key)
{
  $a = array();
  foreach($arr as $row)
  {
    $a[$row[$key]] = $row;
  }

  return $a;
}

function GetKeyAndValueFromArray($arr, $kitem, $vitem)
{
  $kv = array();
  foreach($arr as $row)
  {
    $kv[$row[$kitem]] = $row[$vitem];
  }
  return $kv;
}

function array_index($arr, $val)
{
  $index = false;
  foreach($arr as $idx=>$v)
  {
    if($val == $v)
    {
      $index = $idx;
      break;
    }
  }
  return $index;
}

function interval_in_array($arr, $val)
{
  $pos = 0;
  foreach($arr as $idx=>$num)
  {
    if($num > $val)
      break;
    $pos = $idx+1;
  }

  return $pos;
}

function rad($d)  
{  
  return $d * 3.1415926535898 / 180.0;  
}  

function sort_by_dist($r1, $r2)
{
  $dist1 = $r1['dist'];
  $dist2 = $r2['dist'];
  if($dist1 > $dist2)
    return 1;
  elseif($dist1 < $dist2)
    return -1;
  return 0;
}

function GetDistance($lat1, $lng1, $lat2, $lng2)  
{  
  $EARTH_RADIUS = 6378.137;  
  $radLat1 = rad($lat1);  
  //echo $radLat1;  
  $radLat2 = rad($lat2);  
  $a = $radLat1 - $radLat2;  
  $b = rad($lng1) - rad($lng2);  
  $s = 2 * asin(sqrt(pow(sin($a/2),2) +  
    cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)));  
  $s = $s *$EARTH_RADIUS;  
  $s = round($s * 10000) / 10000;  
  return $s;  
}  

function VerifyRecordInfo($rinfo)
{
  global $cfg;

  $lend = GetItemFromArray($rinfo, 'lend');
  $day  = GetItemFromArray($rinfo, 'day');
  $cardid = GetItemFromArray($rinfo, 'cardid');
  $autorepayday = GetItemFromArray($rinfo, 'autorepayday');
  $vcode = GetItemFromArray($rinfo, 'vcode');

  if(in_array($lend, $cfg['lendenum']) && in_array($day, $cfg['lendday']) && isset($cfg['lendrepayenum'][$autorepayday]))
  {
    $rinfo['fee'] = $lend*LENDRATE*$day;
    $rinfo['reallend'] = $lend - $rinfo['fee'];
  }
  else
  {
    $rinfo = false;
  }

  return $rinfo;
}

function VerifyIdCardInfo($idcardinfo)
{
  $fullname  = GetItemFromArray($idcardinfo, 'fullname');
  $idcardnum = GetItemFromArray($idcardinfo, 'idcardnum');

  // TODO 验证身份证号和姓名
  return true;
}

function VerifyBankCardInfo($cardinfo)
{
  $idcardnum = GetItemFromArray($cardinfo, 'idcardnum');
  $bankid = GetitemFromArray($cardinfo, 'bankid');
  $cardnum = GetItemFromArray($cardinfo, 'cardnum');
  $bankphone = GetItemFromArray($cardinfo, 'bankphone');

  // TODO 需要根据银联提供的借口验证因康卡的有效性

  return true;
}

function GetSuffixFromPath($filepath)
{
  $suff = '';
  $pos = strrpos($filepath, '.');
  if($pos !== false)
    $suff = substr($filepath, $pos+1);
  return $suff;
}

function GetUrlHead()
{
  $scheme = GetItemFromArray($_SERVER, 'REQUEST_SCHEME');
  if(strlen($scheme) == 0)
    $scheme = 'http';
  $host   = GetItemFromArray($_SERVER, 'HTTP_HOST');

  $script_name = $_SERVER['SCRIPT_NAME'];
  $head = sprintf("%s://%s/", $scheme, $host);
  return $head;
}

function AddUrlHead($url)
{
  if(strlen($url) > 0)
  {
    $head = GetUrlHead();
    $url = sprintf('%s%s', $head, $url);
  }

  return $url;
}

function ShortFor4CardNum($cardnum)
{
  $short .= substr($cardnum, strlen($cardnum)-4, 4);
  return $short;
}

function ShortForCardNum($cardnum)
{
  $short = '';
  $short .= substr($cardnum, 0, 4);
  $short .= '******';
  $short .= substr($cardnum, strlen($cardnum)-4, 4);
  return $short;
}

function ShortForPhone($phone)
{
  $short = '';
  $short .= substr($phone, 0, 3);
  $short .= '****';
  $short .= substr($phone, strlen($phone)-4, 4);
  return $short;
}

function get_zh_spell($name)/*{{{*/
{
    $result = "";
    $arr = split_to_zh($name);
    foreach($arr as $row)
    {
        if($row[0] == 0)
            $result .= $row[1];
        elseif($row[0] == 1)
            $result .= iconv('utf-8','gb2312',change_to_zh_spell(iconv('gb2312','utf-8',$row[1])));
    }
    return $result;
}/*}}}*/

function split_to_zh($name)/*{{{*/
{
    $index = 0;
    $begin = 0;
    $name = trim($name);
    $data = array();
    //0英文、1中文
    $curchset = -1;
    for(; $index<strlen($name); ++$index)
    {
        $ch = substr($name, $index, 1);
        if(($ch>="\x00") && ($ch<="\x7f"))
        {
            if($curchset == -1)
               $curchset = 0;
            if($curchset != 0)
            {
                $str = substr($name, $begin, $index-$begin);
                if(strlen($str) > 0)
                {
                    $data[] = array(1,$str);
                    $begin = $index;
                    $curchset = 0;
                }
            }
        }
        else
        {
            if($curchset == -1)
               $curchset = 1;
            if($curchset != 1)
            {
                $str = substr($name, $begin, $index-$begin);
                if(strlen($str))
                {
                    $data[] = array(0,$str);
                    $begin = $index;
                    $curchset = 1;
                }
            }

            ++$index;
        }
    }
    $data[] = array($curchset, substr($name, $begin));

    return $data;
}/*}}}*/

//$str 中全是中文UTF8字符
function change_to_zh_spell($str)/*{{{*/
{
    global $codetable;
    $spell = '';
    $begin = 0;
    while($begin<strlen($str))
    {
        $match = false;
        $end = strlen($str);
        for(; $end>$begin; $end-=1)
        {
            $substr = substr($str, $begin, $end-$begin);
            if(isset($codetable[$substr]))
            {
                $spell .= $codetable[$substr];
                $begin = $end;
                $match = true;
                break;
            }
        }
        if(!$match)
            ++$begin;
    }
    return $spell;
}/*}}}*/

function build_query($diff=array())
{
  $params = $_GET;
  foreach($diff as $k=>$v)
  {
    $params[$k] = $v;
  }

  $str = http_build_query($params);
  $str = str_replace('%25','%',$str);
  return $str;
}

function OutputSelectOption($arr, $default="", $defaultstr="")
{
  $str = '';
  //if((is_string($default)&&(strlen($default)>0)) empty($default))
  if((is_string($default)&&(strlen($default)>0)) || !empty($default))
    ;
  else
    $str .= sprintf('<option value="">%s</option>', $defaultstr);
  foreach($arr as $key=>$val)
  {
    if($key == $default)
      $str .= sprintf("<option value='%s' selected='selected'>%s</option>", $key, $val);
    else
      $str .= sprintf("<option value='%s'>%s</option>", $key, $val);
  }
  return $str;
}

function OutputCheckboxGroup($arr, $prev, $vals=array(), $onclick="", $name=true)
{
  $str = '';
  foreach($arr as $k=>$v)
  {
    $str .= sprintf('<label style="margin-right:10px"><input class="%s" type="checkbox" id="%s%s" %s value="%s" %s %s title="%s"> %s</label>', $prev, $prev, $k, ($name)?sprintf("name=\"%s%s\"",$prev, $k):'', $k, ($onclick)?sprintf('onclick="%s"',$onclick):'', in_array($k,$vals)?'checked':'', $v, $v);
  }
  return $str;
}

function split_digit_to_array($pin)
{
  $idx = 0;
  $pins = array();
  while(true)
  {
    if($pin > 0)
    {
      if($pin % 2 == 1)
        $pins[] = pow(2,$idx);
      $pin /= 2;
    }
    else
    {
      break;
    }
    ++$idx;
  }
  return $pins;
}

function http_get($url,$t=30)
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

if(!function_exists('gzdecode')){
  function gzdecode ($data) {
    $flags = ord(substr($data, 3, 1));
    $headerlen = 10;
    $extralen = 0;
    $filenamelen = 0;
    if ($flags & 4) {
      $extralen = unpack('v' ,substr($data, 10, 2));
      $extralen = $extralen[1];
      $headerlen += 2 + $extralen;
    }
    if ($flags & 8) // Filename
      $headerlen = strpos($data, chr(0), $headerlen) + 1;
    if ($flags & 16) // Comment
      $headerlen = strpos($data, chr(0), $headerlen) + 1;
    if ($flags & 2) // CRC at end of file
      $headerlen += 2;
    $unpacked = @gzinflate(substr($data, $headerlen));
    if ($unpacked === FALSE)
      $unpacked = $data;
    return $unpacked;
  }
}

function http_get_header($url)
{
  $ret = '';
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  curl_setopt($ch, CURLOPT_TIMEOUT, 15);
  curl_setopt($ch, CURLOPT_NOBODY, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
  curl_setopt($ch, CURLOPT_NOSIGNAL, 1 );
  $ret = curl_exec($ch);

  $info = curl_getinfo($ch);

  curl_close($ch);
  return $info;
}

function http_post($url, $data)
{
  $ch = curl_init();    
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt'); 
  curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
  curl_setopt($ch, CURLOPT_NOSIGNAL, 1 );
  $res = curl_exec($ch);
  $info = curl_getinfo($ch);
  curl_close($ch);
  return $res;
}

function http_post_header($url, $data)
{
  $ch = curl_init();    
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt'); 
  curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
  curl_setopt($ch, CURLOPT_NOSIGNAL, 1 );
  curl_exec($ch);
  $info = curl_getinfo($ch);
  curl_close($ch);
  return $info;
}

function get_urls_from_content(&$content)
{
  $urls = array();
  $pattern = '/href=(\'|")(.*?)(\'|")/';

  preg_match_all($pattern, $content, $results);
  foreach($results[2] as $r)
  {
    $r = trim($r);
    if((strpos($r, '#')!==0) && (strpos($r, 'javascript')!==0))
    {
      if(strlen($r) > 0)
        $urls[] = $r;
    }
  }

  return $urls;
}

function filter_html_tag(&$str)
{
  $lstr = '<';
  $rstr = '>';

  while(true)
  {
    $lpos = strpos($str, $lstr);
    $rpos = strpos($str, $rstr);
    if($lpos !== false)
    {
      if($rpos !== false)
      {
        if($rpos > $lpos)
        {
          $str = substr($str,0,$lpos).substr($str,$rpos+1);
        }
        else
        {
          $str = substr($str,$rpos+1);
        }
      }
      else
      {
        $str = substr($str, 0, $lpos);
      }
    }
    else
    {
      if($rpos !== false)
      {
        $str = substr($str, $rpos+1);
      }
      else
      {
        break;
      }
    }
  }

  return $str;
}

function get_substr_from_dist(&$content, $lstr, $rstr)
{
  $substr = '';
  $lpos = strpos($content, $lstr);
  $rpos = strpos($content, $rstr, $lpos);
  if(($lpos!==false) && ($rpos!==false))
  {
    $substr = substr($content, $lpos+strlen($lstr), $rpos-$lpos-strlen($lstr));
  }

  return $substr;
}

function filter_javascript($html)
{
  $lstr = '<script';
  $rstr = '</script>';

  while(true)
  {
    $lpos = strpos($html, $lstr);
    $rpos = strpos($html, $rstr);
    if(($lpos!==false) && ($rpos!==false))
    {
      $html = substr($html, 0, $lpos) . substr($html, $rpos+strlen($rstr));
    }
    else
    {
      break;
    }
  }

  return $html;
}

function GotoUrl($url, $repage='')
{
  if(strlen($repage) > 0)
  {
    $url .= sprintf('?redirect=%s', urlencode($repage));
  }

  $script = sprintf("<script type='text/javascript'>
    if(window.parent.parent)
      window.parent.parent.location.href='%s';
    else if(window.parent)
      window.parent.location.href='%s';
    else
      window.location.href='%s';
</script>",$url, $url, $url);
 
  echo $script;
  exit();
}

function generate_ajax($params,$callback,$url="/funcaction.php")
{
  if(is_array($params))
  {
    $paramsstr = '{';
    foreach($params as $v)
    {
      if(strlen($paramsstr) == 1)
        $paramsstr .= sprintf('"%s":%s', $v, $v);
      else
        $paramsstr .= sprintf(',"%s":%s', $v, $v);
    }
    $paramsstr .= '}';
  }
  else
    $paramsstr = $params;

  $ajaxret = sprintf('var aj=$.ajax({
      url:"%s",
      data:%s,
      type:"post",
      cache:false,
      dataType:"json",
      success:function(data){
        if(data.retno == %d)
        {
          %s(data);
        }
        else if(data.retno == %d)
        {
          alert(data.msg);
        }
        else
        {
          alert(data.msg);
        }
      },
      error:function(){}
    });', $url, $paramsstr, RETNO_SUCC, $callback, RETNO_FAIL);

  return $ajaxret;
}

function getClientIp()
{
  $IPaddress='';
  if (isset($_SERVER)){
    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
      $IPaddress = $_SERVER["HTTP_X_FORWARDED_FOR"];
    } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
      $IPaddress = $_SERVER["HTTP_CLIENT_IP"];
    } else {
      $IPaddress = $_SERVER["REMOTE_ADDR"];
    }
  } else {
    if (getenv("HTTP_X_FORWARDED_FOR")){
      $IPaddress = getenv("HTTP_X_FORWARDED_FOR");
    } else if (getenv("HTTP_CLIENT_IP")) {
      $IPaddress = getenv("HTTP_CLIENT_IP");
    } else {
      $IPaddress = getenv("REMOTE_ADDR");
    }
  }
  return $IPaddress;
}

function sendmail($tomail, $title, $body)
{
  $header = "MIME-Version:1.0\r\nContent-type:text/html;charset=utf-8\r\n";
  mail($tomail, $title, $body, $header);
}

function smtp_mail( $sendto_email, $subject, $body)
{   
  $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
  try {
    $mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = 'smtp.126.com';  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = 'seven_29@126.com';                 // SMTP username
    $mail->Password = 'tang0198';                           // SMTP password
    //$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 25;                                    // TCP port to connect to

    $mail->setFrom('seven_29@126.com', '美滋滋');
    $arr = explode(',', $sendto_email);
    foreach($arr as $m)
    {
      $mail->AddAddress($m);  // 收件人邮箱和姓名   
    }

    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = $subject;

    $mail->Body = sprintf(' <html><head>  
      <meta http-equiv="Content-Language" content="zh-cn">  
      <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8">  
      </head>
      <body>%s</body>  
      </html>', $body);                                                                         
    $mail->AltBody ="text/html";   

    $mail->send();
    echo 'Message has been sent';
  } catch (Exception $e) {
    echo 'Message could not be sent.';
    echo 'Mailer Error: ' . $mail->ErrorInfo;
  }
} 

function get_view_for_page_index($pageindex, $pagecount, $url)
{/*{{{*/
  if($pageindex == 0)
    $pageindex = 1;
  if($pagecount <= 1)
    return '';

  $strresult = '';
  if($pageindex == 1)
  {
    $strresult .= "首页&nbsp;前一页";
  }
  else
  {
    if(strpos($url, 'javascript') !== false)
    {
      $strresult .= sprintf('<a onclick="%s">首页</a>&nbsp;<a onclick="%s">前一页</a>', str_replace("%s", 1, $url), str_replace("%s", $pageindex-1, $url));
    }
    else
    {
      $strresult .= sprintf("<a href='%s'>首页</a>&nbsp;<a href='%s'>前一页</a>", str_replace("%s", 1, $url), str_replace("%s", $pageindex-1, $url));
    }
  }

  $strresult .= "&nbsp;";
  if(($pageindex==$pagecount) || ($pagecount==0))
  {
    $strresult .= "后一页&nbsp;末页";
  }
  else
  {
    if(strpos($url, 'javascript') !== false)
    {
      $strresult .= sprintf('<a onclick="%s">后一页</a>&nbsp;<a onclick="%s">末页</a>', str_replace("%s",$pageindex+1,$url), str_replace("%s", $pagecount, $url));
    }
    else
    {
      $strresult .= sprintf("<a href='%s'>后一页</a>&nbsp;<a href='%s'>末页</a>", str_replace("%s",$pageindex+1,$url), str_replace("%s", $pagecount, $url));
    }
  }

  $strresult .= sprintf("&nbsp;(%s/%s)", $pageindex, $pagecount);

  return $strresult;
}/*}}}*/

function get_view_for_page_index_list($pageindex, $pagecount, $url)
{/*{{{*/
  if($pageindex == 0)
    $pageindex = 1;
  if($pagecount <= 1)
  {
    return '';
  }

  $strresult = '';
  if($pageindex == 1)
  {
    $strresult .= "";
  }
  else
  {
    //$strresult .= sprintf('<a href="%s" style="display:inline-block;;width:30px;height:26px;border-radius:4px;border:1px solid #ccc"><img src="/assets/images/icon_back.png"/></a>', str_replace("%s", $pageindex-1, $url));
  }

  for($i=1; $i<=$pagecount; ++$i)
  {
    if($i == $pageindex)
      $strresult .= sprintf('<a href="javascript:;" style="background-color:#197dc3;color:white;width:30px;height:26px;line-height:26px;border-radius:4px;border:1px solid #ccc;display:inline-block;margin:0px 2px">%s</a>', $i);
    else
      $strresult .= sprintf('<a href="%s" style="background-color:white;color:#197dc3;width:30px;height:26px;line-height:26px;border-radius:4px;border:1px solid #ccc;display:inline-block;margin:0px 2px">%s</a>', str_replace("%s",$i,$url), $i);
  }

  if(($pageindex==$pagecount) || ($pagecount==0))
  {
    $strresult .= "";
  }
  else
  {
    //$strresult .= sprintf('<a href="%s"  style="display:inline-block;width:30px;height:26px;border-radius:4px;border:1px solid #ccc"><img src="/assets/images/icon_right.png"/></a>', str_replace("%s",$pageindex+1,$url));
  }

  $strresult .= ' 跳转到<input id="editp" class="form-control" style="width:42px;height:26px;border-radius: 4px;border: solid 1px #dddddd;margin-top:-5px;margin-left:2px;display:inline-block;text-align:center"/><a class="btn btn-primary btn-si" style="width:42px;height:26px;margin-left:4px;margin-top:-5px;border-radius:4px;padding:0px" onclick="gotopage()">确定</a>';
  $strresult .= '<script>
    function gotopage(){
var p = $("#editp").val();
if(p.length > 0){
  var url = "'.$url.'";
  window.location.href=url.replace("%s",p);
}
}
</script>';

  //$strresult .= sprintf("&nbsp;(%s/%s)", $pageindex, $pagecount);

  return $strresult;
}/*}}}*/

?>
