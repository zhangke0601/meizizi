<?php
/*
 * 用途：模拟登陆有妖气管理后台，并能上传新的漫画信息
 * 作者：feb1234@163.com
 * 时间：2017-08-27
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'inc/func.php');

$username = '17701265992';
$password = 'dongman123456';

$loginurl = 'http://passport.u17.com/member_v2/login.php?url=http://www.u17.com/';
$params = array('username'=>'', 'password'=>'');
$getdata = http_get($loginurl);
$xsrf = get_xsrf($getdata);


$data = array('username'=>$username, 'passwd'=>$password, '_xsrf'=>$xsrf);
$ret = http_post_header($loginurl, $data);
var_dump($ret);

$url = 'http://cp.ireader.com.cn/vendor/book/list?audit=2';
$url = 'http://cp.ireader.com.cn/vendor/book/data?audit=2&is_ajax=true&sortOrder=asc&pageSize=20&pageNumber=1&_=1503844630836';
$ret = http_get($url);
var_dump($ret);

$url = 'http://cp.ireader.com.cn/copyright/contract/list';
$ret = http_get($url);
var_dump($ret);


$url = 'http://cp.ireader.com.cn/vendor/book/save_one_bookmeta?_xsrf=2|cfa7f59b|7319bb204a45af67be674bbd6cdb9042|1503830620';
//continued 0连载 1全本
//category 20 原创 10 出版
//sales_area CN 全国 '' 全球
//method post
$params = array('contract_id'=>'','start_date'=>'', 'expire_date'=>'', 'cp_book_id'=>'','book_name'=>'author_name', 'continued'=>0, 'category'=>20, 'ebook_price'=>0, 'chapter_price'=>0.1, 'keyword'=>'ddd,dd', 'intro'=>'', 'sales_area'=>'');

//上传章节
$url = 'http://cp.ireader.com.cn/content/continued/book/uploadComic';
$params = array('_xsrf'=>'', 'session_id'=>'', 'cp_book_id'=>'', 'upload_type'=>'add', 'chapter_order'=>0, 'cp_id'=>2073, 'file_upload'=>$_FILES);

function get_xsrf($data)
{
  $xsrf = '';
  $pattern = '/name="_xsrf" value="(.*?)"/';
  preg_match($pattern, $data, $result);
  if(count($result) > 0)
    $xsrf = $result[1];
  return $xsrf;
}



?>
