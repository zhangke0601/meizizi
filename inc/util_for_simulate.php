<?php
/*
 * 用途：平台模拟操作
 * 作者：feb1234@163.com
 * 时间：2017-10-31
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'config/config.php');
require_once($base_dir.'inc/PHPExcel/PHPExcel.php');
require_once($base_dir.'inc/util_for_weigedama.php');
//require_once($base_dir.'inc/dama2Web_demo_php2/libs/Dama2Web.class.php');

/*{{{ 掌阅 */
function test_account_for_zhangyue($username,$password)
{/*{{{*/
  delete_cookie_file();
  $loginurl = 'http://cp.ireader.com.cn/auth/user/login';
  //$getdata = http_get_si($loginurl);
  //$xsrf = get_xsrf($getdata);

  //$data = array('username'=>$username, 'passwd'=>$password, '_xsrf'=>$xsrf);
  $data = array('username'=>$username, 'passwd'=>$password);
  $ret = http_post_si($loginurl, $data);
  $retdata = json_decode($ret, true);

  if($retdata['status'] == '200')
    return true;
  return false;
}/*}}}*/

function get_cartoonlist_for_zhangyue()
{/*{{{*/
  $url = 'http://cp.ireader.com.cn/vendor/book/data?audit=&book_type=2&is_ajax=true&sortOrder=asc&pageSize=10&pageNumber=1&_='.(time()-8*3600).'000';
  $list = http_get_si($url);
  $data = json_decode($list, true);
  foreach($data['data']['rows'] as $idx=>$row)
  {
    $name = $row['book_name'];
    $patt = '/《(.*?)\[漫画\]》/';
    preg_match($patt, $name, $r);
    if(!empty($r))
      $data['data']['rows'][$idx]['book_name'] = $r[1];
    $sectionlist = array();
    if($row['book_id'] > 0)
      $sectionlist = get_cartoomsectionlist_for_zhangyue($row['book_id']);
    $data['data']['rows'][$idx]['sectionlist'] = $sectionlist;
  }
  return $data;
}/*}}}*/

function get_cartoomsectionlist_for_zhangyue($ctid)
{/*{{{*/
  $url = sprintf('http://cp.ireader.com.cn/vendor/chapter/data?book_id=%d&include_book=true&book_type=2&is_ajax=true&sortOrder=asc&pageSize=50&pageNumber=1&_=%s', $ctid, (time()-8*3600).'000');
  $list = http_get_si($url);
  $data = json_decode($list, true);
  return $data;
}/*}}}*/

function get_xsrf($data)
{/*{{{*/
  $xsrf = '';
  $pattern = '/name="_xsrf" value="(.*?)"/';
  preg_match($pattern, $data, $result);
  if(count($result) > 0)
    $xsrf = $result[1];
  return $xsrf;
}/*}}}*/

function get_xsrf_for_cover($data)
{/*{{{*/
  $xsrf = '';
  $pattern = '/_xsrf: \'(.*?)\'/';
  preg_match($pattern, $data, $result);
  if(count($result) > 0)
    $xsrf = $result[1];
  return $xsrf;
}/*}}}*/

function get_cartoonincome_for_zhangyue($ctid)
{/*{{{*/
  $stat = array();
  $filename = sprintf('%s-日销售明细', date('Y-m-d H:i'));
  //$url = 'http://cp.ireader.com.cn/common/export/vendor_export_csv?is_ajax=true';
  $url = 'http://cp.ireader.com.cn/common/export/vendor_export_excel';
  //$data = 'csv_head=day_detail_head&url=http%3A%2F%2Fcp.ireader.com.cn%2Fcopyright%2Fplatform%2Fsett%2Fday_detail_data&args=%7B%22start_date%22%3A%222018-04-11%22%2C%22end_date%22%3A%222018-05-11%22%7D&name=2018-05-11%2009%3A52-%E6%97%A5%E9%94%80%E5%94%AE%E6%98%8E%E7%BB%86';
  $data = array('csv_head'=>'day_detail_head',
    'url'=>'http://cp.ireader.com.cn/copyright/platform/sett/day_detail_data',
    'args'=>sprintf('{"start_date":"2016-12-01","end_date":"%s"}', date('Y-m-d')),
    'name'=>$filename);
  http_post_si($url, $data);

  $coofile = get_cookie_file();
  $cnt = file_get_contents($coofile);
  $lstr = 'session_id|56:';
  $lpos = strpos($cnt,$lstr);
  $rpos = strpos($cnt,'|', $lpos+strlen($lstr));
  $sessionid = base64_decode(substr($cnt, $lpos+strlen($lstr), 56));

  $find = false;
  $totaltime = 0;
  while(true)
  {
    sleep(2);
    $url = 'http://cp.ireader.com.cn/common/export/export_csv_data?total=0&pageNumber=1&pageSize=10&name=&status=';
    //$url = sprintf('http://cp.ireader.com.cn/common/export/export_csv_data?is_ajax=true&sortOrder=asc&pageSize=10&pageNumber=1&_=%s284', time());
    $getret = http_get_si($url);
    $getret = json_decode($getret, true);
    if(empty($getret['data']['rows']))continue;
    foreach($getret['data']['rows'] as $row)
    {
      //if(($row['name']==$filename) && !empty($row['download_uri']))
      if((strpos($row['name'],date('Y-m-d'))!==false) && !empty($row['download_uri']))
      {
        $params = array('uri'=>$row['download_uri'], 'name'=>$filename,'session_id'=>$sessionid);
        //$url = 'http://cp.ireader.com.cn/common/export/download_csv?uri=group8/M00/2E/29/wKgHhlr092uEX1WDAAAAABOq5hA78285699.xlsx?v=rJ25NmrD&t=wKgHhlr092s.&name=2018-05-11%2009:52-%E6%97%A5%E9%94%80%E5%94%AE%E6%98%8E%E7%BB%86&session_id=c0bbf0dcab99da06c022629936327074588f70ef';
        $url = 'http://cp.ireader.com.cn/common/export/download_csv?'.http_build_query($params);
        $subgetret = http_get_si($url);
        $xlsfile = sprintf('/tmp/%d.xlsx', time());
        file_put_contents($xlsfile, $subgetret);

        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objPHPExcel = $objReader->load($xlsfile);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();           //取得总行数
        $highestColumn = $sheet->getHighestColumn(); //取得总列数
        $highestColumn = PHPExcel_Cell::columnIndexFromString($highestColumn);

        for($i=$highestRow; $i>=2; --$i)
        {
          $cell0 = (string)$sheet->getCellByColumnAndRow(0, $i)->getValue();
          $cell1 = (string)$sheet->getCellByColumnAndRow(1, $i)->getValue();
          $cell10 = (string)$sheet->getCellByColumnAndRow(10, $i)->getValue();

          if(!isset($stat[$cell1][$cell0]))
            $stat[$cell1][$cell0] = 0;
          $stat[$cell1][$cell0] += $cell10;
        }
        $find = true;
        unlink($xlsfile);
        break;
      }
    }
    if($find)
      break;

    $totaltime += 2;
    if($totaltime > 20)
      break;
  }

  return $stat;
}/*}}}*/

function get_cartoonincome_for_zhangyue_old($ctid)
{/*{{{*/
  $stat = array();
  $filename = sprintf('%s-日销售明细', date('Y-m-d H:i'));
  $url = 'http://cp.ireader.com.cn/common/export/vendor_export_csv?is_ajax=true';
  $data = array('is_background'=>true,'csv_head'=>'day_detail_head',
    'url'=>'http://cp.ireader.com.cn/copyright/platform/sett/day_detail_data',
    'args'=>sprintf('{"start_date":"2016-12-01","end_date":"%s"}', date('Y-m-d')),
    'name'=>$filename);
  http_post_si($url, $data);

  $find = false;
  $totaltime = 0;
  while(true)
  {
    sleep(2);
    $url = sprintf('http://cp.ireader.com.cn/common/export/export_csv_data?is_ajax=true&sortOrder=asc&pageSize=10&pageNumber=1&_=%s284', time());
    $getret = http_get_si($url);
    $getret = json_decode($getret, true);
    foreach($getret['data']['rows'] as $row)
    {
      if(($row['name']==$filename) && !empty($row['download_uri']))
      {
        $params = array('uri'=>$row['download_uri'], 'name'=>$filename);
        $url = 'http://cp.ireader.com.cn/common/export/download_csv?'.http_build_query($params);
        $subgetret = http_get_si($url);
        $xlsfile = sprintf('/tmp/%d.xlsx', time());
        file_put_contents($xlsfile, $subgetret);

        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objPHPExcel = $objReader->load($xlsfile);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();           //取得总行数
        $highestColumn = $sheet->getHighestColumn(); //取得总列数
        $highestColumn = PHPExcel_Cell::columnIndexFromString($highestColumn);

        for($i=$highestRow; $i>=2; --$i)
        {
          $cell0 = (string)$sheet->getCellByColumnAndRow(0, $i)->getValue();
          $cell1 = (string)$sheet->getCellByColumnAndRow(1, $i)->getValue();
          $cell10 = (string)$sheet->getCellByColumnAndRow(10, $i)->getValue();

          if(!isset($stat[$cell1][$cell0]))
            $stat[$cell1][$cell0] = 0;
          $stat[$cell1][$cell0] += $cell10;
        }
        $find = true;
        break;
      }
    }
    if($find)
      break;

    $totaltime += 2;
    if($totaltime > 20)
      break;
  }

  return $stat;
}/*}}}*/

function createcartoon_for_zhangyue($ctinfo)
{/*{{{*/
  global $logger;
  $url = 'http://cp.ireader.com.cn/vendor/book/one_bookmeta_ui?book_type=2&is_ajax=true&_='.time().'243';
  $cnt = http_get_si($url);
  $cnt = str_replace(array("\r\n","\r","\n"),"",$cnt);
  $patt = '/value="([^<>]*?)" name="batch_name"/';
  $patt = '/action="(.*?)".*?value="([^<>]*?)" name="batch_name"/';
  preg_match($patt, $cnt, $ret);
  qLogInfo($logger, sprintf('createcartoon_for_zhuangyue batch_name %s', json_encode($ret)));
  $ctrow['batch_name'] = $ret[2];
  $action = $ret[1];

  $url = 'http://cp.ireader.com.cn/copyright/contract/list?lookup=true&target=add_comic_contract&is_ajax=true&_='.time().'765';
  $cnt = http_get_si($url);
  qLogInfo($logger, sprintf('createcartoon_for_zhuangyue contract %s', $cnt));
  $postret = json_decode($cnt, true);

  $ctrow['book_type'] = 2;
  $ctrow['meta_type'] = 2;
  $ctrow['contract_id'] = 0;
  if(($postret['status']==200) && (count($postret['data']['data'])>0))
    $ctrow['contract_id'] = $postret['data']['data'][count($postret['data']['data'])-1]['contractId'];
  $ctrow['start_date'] = '';
  $ctrow['expire_date'] = '';
  $ctrow['cp_book_id'] = '';
  $ctrow['book_name'] = $ctinfo['ctname'];
  $ctrow['author_name'] = $ctinfo['ctauthorname'];
  $ctrow['continued'] = 0;
  $ctrow['category'] = 20;
  $ctrow['ebook_price'] = 0;
  $ctrow['chapter_price'] = 0.39;
  $kws = GetItemsFromArray($ctinfo['ctsubinfos'],'ctsuname');
  //foreach($ctinfo['ctsubinfos'] as $idx=>$info){
  //}
  $ctrow['keyword'] = implode('，', $kws);
  $ctrow['intro'] = $ctinfo['ctdesc'];
  $ctrow['sales_area'] = 'CN';
  $submiturl = 'http://cp.ireader.com.cn'.$action;
  $postret = http_post_si($submiturl, $ctrow);
  qLogInfo($logger, sprintf('createcartoon_for_zhuangyue params %s', json_encode($ctrow)));
  return json_decode($postret,true);
}/*}}}*/

function updatecartooncover_for_zhangyue($ctinfo)
{/*{{{*/
  $id = $ctinfo['cssourcebakid'];
  $url = sprintf('http://cp.ireader.com.cn/vendor/upload/cover?book_type=2&is_ajax=true&id=%s&_=%s', $id, time().'000');
  $getret = http_get_si($url);
  $xsrf = get_xsrf_for_cover($getret);

  $coverurl = downimg_to_local($ctinfo['ctverticalimage']);
  $pinfo = pathinfo($coverurl);
  if($pinfo['extension'] == 'png')
  {
    $coverurl = img_frompng_to_jpg($coverurl);
  }
  $postdata = array('file'=>sprintf('@%s', $coverurl), '_xsrf'=>$xsrf, 'id'=>$id, 'ratio'=>'low', 'size'=>'600*800');
  $url = 'http://cp.ireader.com.cn/vendor/upload/resource';
  $postret = http_post_si($url, $postdata);

  //删除下载的临时图片
  if(!empty($coverurl))
    unlink($coverurl);
  return json_decode($postret, true);
}/*}}}*/

function submitcartoonaudit_for_zhangyue($cssourceid)
{/*{{{*/
  $url = sprintf('http://cp.ireader.com.cn/vendor/book/submit_audit?is_ajax=true&id=%s&_=%s293', $cssourceid, time());
  $getret = http_get_si($url);
  return json_decode($getret, true);
}/*}}}*/

function submitcartoonsectionrelease_for_zhangyue($ctsid, $ctid='', $schtime='')
{/*{{{*/
  if(empty($schtime))
  {
    $url = sprintf('http://cp.ireader.com.cn/vendor/chapter/publish?book_type=2&is_ajax=true&id=%s&_=%s', $ctsid, time());
    $getret = http_get_si($url);
  }
  else
  {
    $schtime = date('Y-m-d H:i',intval(strtotime($schtime)/5/60)*5*60);
    $getdata = array('is_ajax'=>'true', 'chapter_ids'=>$ctsid, 'book_id'=>$ctid, 'scheduler_time'=>$schtime, '_'=>time().'973');
    $url = 'http://cp.ireader.com.cn/vendor/chapter/do_set_scheduler?'.http_build_query($getdata);
    $getret = http_get_si($url);
  }
  return json_decode($getret, true);
}/*}}}*/

function createsection_for_zhangyue($sectinfo)
{/*{{{*/
  global $logger;
  $action = 'http://cp.ireader.com.cn/vendor/chapter/add_comic_chapter';

  //-F 'book_id=11576575' -F 'name=curl' -F 'upload_status=[0,0]' -F 'src=[null,null]' -F 'index=20' -b 
  $ctsrow = array();
  $ctsrow['book_id'] = $sectinfo['cssourceid'];
  $ctsrow['name'] = $sectinfo['ctsname'];
  //$ctsrow['index'] = $sectinfo['ctssort'];
  $ctsrow['charge'] = 1;

  $src = array();
  $upload_status = array();
  $files = array();
  $content = json_decode($sectinfo['ctscontent'], true);
  $imguploadsucc = true;
  foreach($content as $img)
  {
    $src[] = null;
    $upload_status[] = 0;
    $url = $img['imgurl'];
    $newimgfile = downimg_to_local($url);
    if(empty($newimgfile)){
      $imguploadsucc = false;
      break;
    }else{
      $files[] = $newimgfile;
    }
  }

  if($imguploadsucc){
    $ctsrow['src'] = json_encode($src);
    $ctsrow['upload_status'] = json_encode($upload_status);

    $submiturl = 'http://cp.ireader.com.cn/'.$action;
    $params = 'curl ';
    foreach($ctsrow as $k=>$row)
    {
      $params .= sprintf(" -F '%s=%s' ", $k, $row);
    }
    foreach($files as $file)
      $params .= sprintf(" -F 'file=@%s' ", $file);
    $coo = getcookie_from_cookiefile(get_cookie_file());
    $params .= " -b '".$coo."'";
    $params .= " ".$action;

    $postret = exec($params);//http_post_si($submiturl, $ctsrow);

    //删除下载后的图片
    foreach($files as $file) {
        if (!empty($file))
            unlink($file);
    }
    qLogInfo($logger, sprintf('createsection_for_zhangyue cmd %s', $params));
  }else{
    $postret = json_encode(array('status'=>400, 'msg'=>'章节图片上传失败，请稍后重新提交'));
  }

  return json_decode($postret,true);
}/*}}}*/
/*}}}*/

/*{{{ 快看 */
function test_account_for_kuaikan($username,$password)
{/*{{{*/
  delete_cookie_file();
  $loginurl = 'https://www.kuaikanmanhua.com/v1/passport/login/pc/user_login';
  //$getdata = http_get_si($loginurl);
  //$xsrf = get_xsrf($getdata);

  $data = array('key'=>$username, 'password'=>$password, 'remember'=>1);
  $ret = http_post_si($loginurl, $data);
  $data = json_decode($ret, true);

  if($data['code'] == '200')
    return true;
  return false;
}/*}}}*/

/**
 * 微博作品状态同步
 */
function get_cartoonlist_for_weibo($cookie, $comic_id)
{
    $url = 'http://stats.ftread.com/api/Cartoon/syncWeibo';  //new_meizizi利用querylist抓取微博数据接口
    $data = [
        'cookie' => $cookie,
        'comic_id' => $comic_id,
    ];
    $result = http_post($url, $data);
    return $result;
}

/*{{{ 快看 */
function test_account_for_kuaikan_old($username,$password)
{/*{{{*/
  delete_cookie_file();
  $loginurl = 'http://www.kuaikanmanhua.com/v1/passport/login/pc/user_mobile_login';
  //$getdata = http_get_si($loginurl);
  //$xsrf = get_xsrf($getdata);

  $data = array('phone'=>$username, 'password'=>$password, 'remember'=>1);
  $ret = http_post_si($loginurl, $data);
  $data = json_decode($ret, true);

  if($data['code'] == '200')
    return true;
  return false;
}/*}}}*/

function get_cartoonlist_for_kuaikan()
{/*{{{*/
  $url = 'http://www.kuaikanmanhua.com/node_analyze_api/author/topic/list';
  $list = http_get_si($url);
  $data = json_decode($list, true);
  foreach($data['data'] as $idx=>$row)
  {
    $sectionlist = get_cartoomsectionlist_for_kuaikan($row['id']);
    $data['data'][$idx]['sectionlist'] = $sectionlist;
  }
  return $data;
}/*}}}*/

function get_cartoomsectionlist_for_kuaikan($ctid)
{/*{{{*/
  $url = sprintf('http://www.kuaikanmanhua.com/node_analyze_api/author/topic/list_comics?topic_id=%s&page=1&count=40', $ctid, (time()-8*3600).'000');
  $list = http_get_si($url);
  $data = json_decode($list, true);
  return $data['data'];
}/*}}}*/

function get_cartoonsectioninfo_for_kuaikan($ctsid)
{/*{{{*/
  $url = 'http://www.kuaikanmanhua.com/node_analyze_api/author/comic/preview?comic_id='.$ctsid;
  $getret = http_get_si($url);
  $data = json_decode($getret, true);
  return $data['data'];
}/*}}}*/

function createsection_for_kuaikan($sectinfo)
{/*{{{*/
  global $logger;
  $action = '/author/comic/add';

  $ctsrow = array();
  $ctsrow['topic_id'] = $sectinfo['cssourceid'];
  $ctsrow['title'] = $sectinfo['ctsname'];
  $content = json_decode($sectinfo['ctscontent'], true);
  $sectinfo['ctsimgcount'] = count($content);
  $ctsrow['chapterCell'] = $sectinfo['ctsimgcount'];

  $gettokenurl = 'https://www.kuaikanmanhua.com/node_analyze_api/image/qiniu/token';
  $tokendata = http_get_si($gettokenurl);
  $tokendata = json_decode($tokendata, true);
  $token = $tokendata['data']['token'];//获取token
  $imagebase = $tokendata['data']['image_base'];
  qLogInfo($logger, sprintf("createsection_for_kuaikan token %s", json_encode($tokendata)));

  $ctsplatformcoverinfos = json_decode($sectinfo['ctsplatformcoverinfos'], true);
  $ctscover= $sectinfo['ctscover'];
  if(isset($ctsplatformcoverinfos[SOURCE_KUAIKAN]))
    $ctscover = $ctsplatformcoverinfos[SOURCE_KUAIKAN]['verticalimg'];//获取封面
  $submitret = array('code'=>400);
  if(!empty($ctscover)){
    $newimgfile = downimg_to_local($ctscover);//下载封面图片到本地

    $imgurl = 'http://upload.qiniu.com/';
    $baseimg = sprintf('image/%s/%s.png', date('Ymd'), substr(md5($newimgfile),0,9));
    $imgdata = array('token'=>$token, 'file'=>sprintf("@%s",$newimgfile), 'key'=>$baseimg);
    $postret = http_post_si($imgurl, $imgdata);//上传封面到七牛云
    qLogInfo($logger, sprintf("createsection_for_kuaikan qiniu_cover %s", $postret));
    $postret = json_decode($postret, true);

    $update_date = date('Y-m-d');
    if(!empty($sectinfo['ctrrreleasetime']))
      $update_date = substr($sectinfo['ctrrreleasetime'], 0, 10);

    $list = get_cartoomsectionlist_for_kuaikan($sectinfo['cssourceid']);
    $order = $sectinfo['ctssort'];//count($list);

    $row = array('topic_id'=>intval($sectinfo['cssourceid']), 'comic_id'=>'', 'order'=>$order, 'update_date'=>$update_date);
    $update = 'https://www.kuaikanmanhua.com/node_analyze_api/author/comic/predict/order_changes?'.http_build_query($row);
    $getret = http_get_si($update);

    //{"topic_id":1814,"title":"第3话 所有人都要陪葬！","cover_img":"image/180302/3emrgQV79.png","comic_type":0,"order":4,"update_date":"2018-03-09","zoomable":1}
    $addurl = 'https://www.kuaikanmanhua.com/node_analyze_api/author/comic/add';
    $ctsrow = array('topic_id'=>intval($sectinfo['cssourceid']), 'title'=>$sectinfo['ctsname'], 'cover_img'=>$baseimg, 'comic_type'=>0,'order'=>$order, 'comic_property'=>'1', 'update_date'=>$update_date,'zoomable'=>1);
    $postret = http_post_si_for_kk($addurl, decodeUnicode(json_encode($ctsrow)));
    qLogInfo($logger, sprintf("createsection_for_kuaikan addtopic %s %s", json_encode($ctsrow), $postret));
    $postret = json_decode($postret, true);
    //$submitret = array('code'=>400);
    if($postret['code'] == 200){
      $ctsid = $postret['data'];
      $submitret['code'] = 200;
      $submitret['ctsid'] = $ctsid;

      if($ctsid){
        unset($ctsrow['topic_id']);
        unset($ctsrow['cover_img']);
        $ctsrow['comic_id'] = $ctsid;

        $content = json_decode($sectinfo['ctscontent'], true);
        $ctsrow['local_names'] = '';
        $ctsrow['comic_images'] = '';
        foreach($content as $img)
        {
          $url = $img['imgurl'];
          $newimgfile = downimg_to_local($url);
          $suff = substr($newimgfile,strrpos($newimgfile,'.')+1);
          $key = sprintf('image/c%s/%s/%s.%s', $ctsid, date('Ymd'), substr(md5($newimgfile),0,9),$suff);
          $postdata = array('token'=>$token,
            'file'=>sprintf("@%s", $newimgfile),
            'key'=>$key);
          list($width, $height, $type, $attr) = getimagesize($newimgfile);
          $postret = http_post_si('http://upload.qiniu.com/',$postdata);
          qLogInfo($logger, sprintf("createsection_for_kuaikan submitsectionimage %s", $postret));
          $postret = json_decode($postret,true);
          $ctsrow['local_names'] .= sprintf('%s:%s,', $key, substr($newimgfile, strrpos($newimgfile,'/')+1));
          $curheight = 0;
          while(true){
            $ctsrow['comic_images'] .= sprintf('%s?imageMogr2/auto-orient/crop/!800x625a0a%d/format/webp,', $key, $curheight);
            if($curheight+625 > $height)
              break;
            $curheight += 625;
          }
        }
        $ctsrow['local_names'] = trim($ctsrow['local_names'], ',');
        $ctsrow['comic_images'] = trim($ctsrow['comic_images'], ',');

        $editurl = 'https://www.kuaikanmanhua.com/node_analyze_api/author/comic/edit';
        $postret = http_post_si_for_kk($editurl, json_encode($ctsrow));
        qLogInfo($logger, sprintf("createsection_for_kuaikan editsection %s", $postret));

        while(true){
          $submiturl = 'https://www.kuaikanmanhua.com/node_analyze_api/author/comic/commit';
          $ctsrow = array('comic_id'=>$ctsid);
          $postret = http_post_si_for_kk($submiturl, json_encode($ctsrow));
          qLogInfo($logger, sprintf("createsection_for_kuaikan submitsection %s", $postret));
          $postret = json_decode($postret, true);
          if($postret['code'] == 200)
            break;
          sleep(5);
        }
      }
    }
    else
    {
      $submitret['msg'] = $postret['message'];
    }
  }
  else
  {
    $submitret['msg'] = '没有封面，请上传封面';
  }

  return $submitret;
}/*}}}*/


/*}}}*/

/*{{{ 漫画岛 */
function test_account_for_manhuadao($username,$password)
{/*{{{*/
  delete_cookie_file();
  $loginurl = 'http://www.manhuadao.cn/AdminLogin/Login';
  $vcode = get_imgcode_for_manhuadao();

  if(empty($vcode))
  {
    return false;
  }
  else
  {
    $data = array('userName'=>$username, 'password'=>$password, 'vcode'=>$vcode);
    $ret = http_post_si($loginurl, $data);
    $data = json_decode($ret, true);

    if($data)
    {
      if($data['Statu'] != '2')
        return true;
      return false;
    }
    else
      return false;
  }
}/*}}}*/

function get_cartoonlist_for_manhuadao()
{/*{{{*/
  $data = array();
  $page = 1;

  while(true)
  {
    $tmp = get_cartoonlist_for_manhuadao_page($page);
    $data = array_merge($data, $tmp);
    if(count($tmp) < 5)
      break;
    ++$page;
  }


  return $data;
}/*}}}*/

function get_cartoonlist_for_manhuadao_page($page)
{/*{{{*/
  $url = 'http://www.manhuadao.cn/Admin_Areas/CartoonArea/Search?t='.time().'761&page='.$page.'&pageSize=5&comicName=&_='.time().'984';
  $list = http_get_si($url);
  $data = json_decode($list, true);
  $ht = $data['html'];
  $ht = str_replace(array("\r\n","\r","\n"), '', $ht);
  $patt = '/cartoon-title[^<]*?<p>([^<]*)?<span.*?笔名：<span>(.*?)<\/span>.*?题材：<span>(.*?)<\/span>.*?章节数：<span>(.*?)<\/span>.*?更新状态：<span>(.*?)<\/span>.*?更新时间：<span>(.*?)<\/span>.*?创建时间：<span>(.*?)<\/span>.*?投稿状态：<span>(.*?)<\/span>.*?漫画简介：<span>([^\/]*)?<\/span>.*?href="([^<:]*)?"/';
  preg_match_all($patt, $ht, $r);
  $data = array();
  foreach($r[0] as $idx=>$v)
  {
    $id = $r[10][$idx];
    $pos = strrpos($id,'/');
    $id = substr($id, $pos+1);
    $row = array();
    $row['ctsourceid'] = $id;
    $row['ctname'] = trim($r[1][$idx]);
    $row['ctauthorname'] = trim($r[2][$idx]);
    $row['ctprogress'] = trim($r[5][$idx]);
    $row['ctdesc'] = trim($r[9][$idx]);
    $sectionlist = get_cartoomsectionlist_for_manhuadao($row['ctsourceid']);
    $row['sectionlist'] = $sectionlist;
    $data[] = $row;
  }
  /*foreach($data['data'] as $idx=>$row)
  {
    $sectionlist = get_cartoomsectionlist_for_kuaikan($row['id']);
    $data['data'][$idx]['sectionlist'] = $sectionlist;
  }*/
  return $data;
}/*}}}*/

function get_cartoomsectionlist_for_manhuadao($ctid)
{/*{{{*/
  $url = sprintf('http://www.kuaikanmanhua.com/node_analyze_api/author/topic/list_comics?topic_id=%s&page=1&count=20', $ctid, (time()-8*3600).'000');
  $curpage = 1;
  $retdata = array();
  while(true)
  {
    $url = sprintf('http://www.manhuadao.cn/Admin_Areas/CartoonArea/CpSearch?t=1510756187444&page=%s&pageSize=10&comicId=%s&chapterName=&chapterStatus=&_=1510756187461', $curpage, $ctid);
    $list = http_get_si($url);
    $data = json_decode($list, true);
    $ht = $data['html'];
    $ht = str_replace(array("\r\n","\r","\n"), '', $ht);
    $patt = '#<td.*?>(.+?)</td>#';
    preg_match_all($patt, $ht, $r);
    $count = count($r[1])/6;
    for($i=1; $i<$count; ++$i)
    {
      $row = array();
      $td = $r[1][$i*6+5];
      $lpos = strpos($td,'Preview');
      $rpos = strpos($td, '"', $lpos+8);
      $sid = substr($td, $lpos+8, $rpos-$lpos-8);
      $row['ctsname'] = trim(strip_tags($r[1][$i*6+1]));
      $row['ctssourceid'] = $sid;
      $row['ctsstatus'] = $r[1][$i*6+4];
      $retdata[] = $row;
    }
    ++$curpage;
    if($data['currentPage'] >= $data['total'])
      break;
  }
  return $retdata;
}/*}}}*/

function get_imgcode_for_manhuadao()
{/*{{{*/
  $ret = '';
  global $dama2account;
  global $dama2passwd;
  $url = 'http://www.manhuadao.cn/AdminLogin/CheckCode/1';
  $imgdata = http_get_si($url);
  $imgfile = sprintf('/tmp/%s', md5($imgdata));
  file_put_contents($imgfile, $imgdata);
  /*$web = new Dama2Web($dama2account, $dama2passwd);
  $retdata = $web->decodeHex($imgfile, 200);
  if($retdata['ret'] == 0)
  {
    $ret = $retdata['result'];
  }*/
  $retdata = weige_decode($imgfile);
  if($retdata['code'] == 0){
    $ret = $retdata['data']['captcha'];
  }

  unlink($imgfile);
  return $ret;
}/*}}}*/

function get_cartoonincome_for_manhuadao($mon='')
{/*{{{*/
    $logFile = '/home/search/meizizi/error_log/'.date('Y-m-d').__FUNCTION__.'.log';
  $stat = array();
  if(empty($mon))
    $curmon = date('Y-m');
  else
    $curmon = $mon;
  $getdata = array('startTime'=>$curmon, 'endTime'=>$curmon, 'comicName'=>'');
  $url = 'http://www.manhuadao.cn/Admin_Areas/PersonArea/Search?'.http_build_query($getdata);
  $getret = http_get_si($url);
  //  error_log('manhuadao:'.$getret.PHP_EOL,3,$logFile);//debug
  $getret = json_decode($getret,true);
  $htstr = $getret['html'];

  $lpos = strpos($htstr, 'income-info');
  if($lpos!==false)
  {
    $htstr = substr($htstr, $lpos);
    $htstr = str_replace(array("\r\n", "\r", "\n"), '', $htstr);
    $patt = '/<tr>.*?<td>(.*?)<\/td>.*?<td>(.*?)<\/td>.*?<td>(.*?)<\/td>.*?<td>(.*?)<\/td>.*?<td>(.*?)<\/td>.*?<td>(.*?)<\/td>/';
    preg_match_all($patt, $htstr, $ret);
    //  error_log('manhuadao_preg:'.json_encode($ret).PHP_EOL,3,$logFile);//debug
    $stat['income'] = array();
    foreach($ret[0] as $idx=>$row)
    {
      if($idx > 0)
      {
        $ret6 = $ret[6][$idx];
        $ret2 = strip_tags($ret[2][$idx]);
        $patt = '/"(.*?)"/';
        preg_match($patt, $ret6, $subret);
        $url = 'http://www.manhuadao.cn'.str_replace('&amp;','&',$subret[1]);
        $cnt  = http_get_si($url);
        $cssourceid = substr($url, strrpos($url,'=')+1);
        $lpos = strpos($cnt, '<tbody>');
        $rpos = strpos($cnt, "</tbody>", $lpos);
        $htstr = substr($cnt, $lpos, $rpos-$lpos-1);
        $htstr = str_replace(array("\r\n", "\r", "\n"), '', $htstr);
        $patt = '/<tr>.*?<td>(.*?)<\/td>.*?<td>.*?<\/td>.*?<td>.*?<\/td>.*?<td>.*?<\/td>.*?<td>(.*?)<\/td>/';
        preg_match_all($patt, $htstr, $subret);
        //error_log('cnt_'.$url.'_preg'.json_encode($subret).PHP_EOL,3,$logFile);//debug
        $stat['income'][$cssourceid]['name'] = trim($ret2);
        foreach($subret[0] as $idx=>$r)
        {
          $stat['income'][$cssourceid]['data'][$subret[1][$idx]] = trim($subret[2][$idx],'￥');
        }
      }
    }
  }

  return $stat;
}/*}}}*/

function createcartoon_for_manhuadao($ctinfo)
{/*{{{*/
  global $logger;
  global $corrsubs;
  $action = 'http://www.manhuadao.cn/Admin_Areas/CartoonArea/AddCartoon';

  $addurl = 'http://www.manhuadao.cn/Admin_Areas/CartoonArea/AddCartoon';
  $cnt = http_get_si($addurl);
  $cnt = str_replace(array("\r\n","\r","\n"),'',$cnt);
  $patt = '/name="authorId" value="([^<>]*?)".*?name="authorName" value="([^<>]*?)"/';
  preg_match($patt, $cnt, $r);
  $ctrow['authorId'] = $r[1];
  $ctrow['authorName'] = $r[2];


  $imguploadurl = 'http://www.manhuadao.cn/Admin_Areas/ImgUpload/ComicUpload';
  $ctverticalimage = $ctinfo['ctverticalimage'];
  $imgfile = substr($ctverticalimage, strrpos($ctverticalimage,'/')+1);
  $newimgfile = sprintf('/tmp/%s.png',$imgfile);
  $imgcnt = file_get_contents($ctinfo['ctverticalimage'].'-origin');//开原图保护后加-origin访问原图
  file_put_contents($newimgfile, $imgcnt);
  $postdata = array('file'=>sprintf("@%s", $newimgfile));
  $postret = http_post_si($imguploadurl,$postdata);

  //删除下载后已使用的临时图片
  if(!empty($newimgfile))
    unlink($newimgfile);
  qLogInfo($logger, sprintf("createcartoon_for_manhuadao imageupload request %s %s", $imguploadurl, json_encode($postdata)));
  qLogInfo($logger, sprintf("createcartoon_for_manhuadao imageupload response %s", $postret));
  $postret = json_decode($postret,true);
  if(isset($postret['result']) != 0){
    $imgurl = $postret['msg'];

    $resizeurl = 'http://www.manhuadao.cn/Admin_Areas/ImgUpload/ResizeCover';
    $postdata = array('img'=>$imgurl,'x'=>0,'y'=>0,'w'=>300,'h'=>400);
    $postret = http_post_si($resizeurl,$postdata);
    qLogInfo($logger, sprintf("createcartoon_for_manhuadao resize request %s %s", $resizeurl, json_encode($postdata)));
    qLogInfo($logger, sprintf("createcartoon_for_manhuadao resize response %s", $postret));
    $postret = json_decode($postret, true);
    if($postret['result_code'] == 1){
      $ctrow['comicCover'] = $postret['result_url'];
      $ctrow['comicName'] = $ctinfo['ctname'];
      $ctrow['comicDesc'] = $ctinfo['ctdesc'];
      $types = array(MAN_VECTOR_PAGE=>'1', MAN_VECTOR_TIAO=>'0', MAN_VECTOR_CHA=>'0');
      $ctrow['comicChannel'] = $types[$ctinfo['ctvector']];
      $ctrow['initState'] = 0;
      if($ctinfo['csreleasetype'] == 5)
      {
        $ctrow['comicNature'] = '独家原创';
        $ctrow['initState'] = 1;
      }
      else
      {
        $ctrow['comicNature'] = '授权作品';
        if($ctinfo['csfirstrelease'] == 5)
          $ctrow['initState'] = 1;
      }
      $ctrow['competition'] = '不参加';
      $ctrow['shangchuanxieyi'] = 'on';
      $ctsubinfos = $ctinfo['ctsubinfos'];
      $ctsubs = GetItemsFromArray($ctsubinfos,'ctsuid');
      $postdata = http_build_query($ctrow);
      foreach($ctsubs as $sub)
      {
        if(isset($corrsubs[$sub]))
        {
          $postdata .= sprintf("&comicType=%s",$corrsubs[$sub][SOURCE_MANHUADAO]);
        }
      }

      qLogInfo($logger, $postdata);
      $submiturl = $action;
      $postret = http_post_si($submiturl, $postdata);
    }else{
      $postret = json_encode(array('Statu'=>0, 'Msg'=>$postret['msg']));
    }
  }else{
    $postret = json_encode(array('Statu'=>0, 'Msg'=>$postret['msg']));
  }
  return json_decode($postret,true);
}/*}}}*/

function createsection_for_manhuadao($sectinfo)
{/*{{{*/
  global $logger;
  $action = '/Admin_Areas/CartoonArea/PartAdd';
  $ctsrow = array();
  $ctsrow['chapterName'] = $sectinfo['ctsname'];
  $content = json_decode($sectinfo['ctscontent'], true);
  $sectinfo['ctsimgcount'] = count($content);
  $ctsrow['chapterCell'] = $sectinfo['ctsimgcount'];

  $authorurl = 'http://www.manhuadao.cn/Admin_Areas/CartoonArea/PartAdd?cartoonid='.$sectinfo['cssourceid'];
  $cnt = http_get_si($authorurl);
  $patt = '/name="authorId" value="(.*?)"/';
  preg_match($patt, $cnt,$ret);
  $ctsrow['authorId'] = $ret[1];
  qLogInfo($logger, sprintf("createsection_for_manhuadao partadd %s %s", $authorurl, $cnt));

  $coveruploadurl = 'http://www.manhuadao.cn/Admin_Areas/ImgUpload/ChapterCoverUpload';
  $ctsplatformcoverinfos = json_decode($sectinfo['ctsplatformcoverinfos'], true);
  $ctscover= $sectinfo['ctscover'];
  //$ctscover = $sectinfo['ctscover'];
  if(isset($ctsplatformcoverinfos[3]))
    $ctscover = $ctsplatformcoverinfos[3]['verticalimg'];
  $imgfile = substr($ctscover, strrpos($ctscover,'/')+1);
  if(!empty($ctscover)){
    $newimgfile = downimg_to_local($ctscover);
    $postdata = array('file'=>sprintf("@%s", $newimgfile),'cartoonid'=>$sectinfo['cssourceid']);
    $postret = http_post_si($coveruploadurl,$postdata);
    qLogInfo($logger, sprintf("createsection_for_manhuadao coverupload request %s %s", $coveruploadurl, json_encode($postdata)));
    qLogInfo($logger, sprintf("createsection_for_manhuadao coverupload response %s", $postret));
    $postret = json_decode($postret,true);
    if($postret && isset($postret['result_url']))
    {
      $imgurl = $postret['result_url'];
      $ctsrow['chapterCover'] = $imgurl;
      $ctsrow['chapterStatus'] = 0;
      $ctsrow['comicId'] = $sectinfo['cssourceid'];

      $imguploadurl = 'http://www.manhuadao.cn/Admin_Areas/ImgUpload/ImgsUpload';
      $content = json_decode($sectinfo['ctscontent'], true);
      $imguploadsucc = true;
      foreach($content as $img)
      {
        $url = $img['imgurl'];
        $newimgfile = downimg_to_local($url);
        if(empty($newimgfile)){
          $imguploadsucc = false;
          break;
        }else{
          $postdata = array('file'=>sprintf("@%s", $newimgfile),'cartoonid'=>$sectinfo['cssourceid']);
          $postret = http_post_si($imguploadurl,$postdata);
          qLogInfo($logger, sprintf("createsection_for_manhuadao imageupload request %s %s", $imguploadurl, json_encode($postdata)));
          qLogInfo($logger, sprintf("createsection_for_manhuadao imageupload response %s", $postret));
          $postret = json_decode($postret,true);
          //$ctsrow['fileselect[]'][] = $postret['imgpath'];
          $ctsrow[$postret['imgpath']] = 0;
        }
      }

      if($imguploadsucc){
        //$ctsrow['isFinish'] = 'on';
        $ctsrow['publishStatus'] = 0;
        if(!empty($sectinfo['ctrrreleasetime']))
        {
          $ctsrow['publishStatus'] = 1;
          $ctsrow['publishTime'] = substr($sectinfo['ctrrreleasetime'],0,10);
        }

        $submiturl = 'http://www.manhuadao.cn/'.$action;
        $postret = http_post_si($submiturl, $ctsrow);
      }else{
        $postret = json_encode(array('Statu'=>0, 'Msg'=>'章节图片上传失败，请稍后重试'));
      }
    }
    else
    {
      $postret = json_encode(array('Statu'=>0, 'Msg'=>$postret['msg']));
    }
  }else{
    $postret = json_encode(array('Statu'=>0, 'Msg'=>'请上传封面'));
  }

  return json_decode($postret,true);
}/*}}}*/

/*}}}*/

/*{{{ 布卡 */
function test_account_for_buka($username,$password)
{/*{{{*/
  delete_cookie_file();
  $loginurl = 'http://td.buka.cn/user/login';
  $vcode = get_imgcode_for_buka();
  $vcode = strtolower($vcode);

  if(empty($vcode))
  {
    return false;
  }
  else
  {
    $data = array('uname'=>$username, 'password'=>md5($password), 'vcode'=>$vcode,'login'=>1);
    $ret = http_post_si($loginurl, $data);
    $data = json_decode($ret, true);

    if($data['ret'] == 0)
      return true;
    return false;
  }
}/*}}}*/

function get_cartoonlist_for_buka()
{/*{{{*/
  $url = 'http://td.buka.cn/user/manga';
  $cnt= http_get_si($url);

  $lpos = strpos($cnt, 'mangas');
  $lpos = strpos($cnt, '[', $lpos);
  $rpos = strpos($cnt, "\n", $lpos);
  $htstr = substr($cnt, $lpos, $rpos-$lpos-1);
  $list = json_decode($htstr, true);

  foreach($list as $idx=>$row)
  {
    $sectionlist = get_cartoomsectionlist_for_buka($row['mid']);
    $list[$idx]['sectionlist'] = $sectionlist;
  }
  return $list;
}/*}}}*/

function get_cartoomsectionlist_for_buka($ctid)
{/*{{{*/
  $url = sprintf('http://td.buka.cn/user/chapter/%s', $ctid);
  $cnt = http_get_si($url);

  $lpos = strpos($cnt, 'datas');
  $lpos = strpos($cnt, '{', $lpos);
  $rpos = strpos($cnt, "\n", $lpos);
  $htstr = substr($cnt, $lpos, $rpos-$lpos-2);
  $list = json_decode($htstr, true);

  return $list;
}/*}}}*/

function get_imgcode_for_buka()
{/*{{{*/
  $ret = '';
  global $dama2account;
  global $dama2passwd;
  $url = 'http://td.buka.cn/views/res/code/captcha.php';
  $imgdata = http_get_si($url);
  $imgfile = sprintf('/tmp/%s', md5($imgdata));
  file_put_contents($imgfile, $imgdata);
  $web = new Dama2Web($dama2account, $dama2passwd);
  $retdata = $web->decodeHex($imgfile, 200);
  if($retdata['ret'] == 0)
  {
    $ret = $retdata['result'];
  }
  unlink($imgfile);
  return $ret;
}/*}}}*/

function createcartoon_for_buka($ctinfo)
{/*{{{*/
  $action= 'http://td.buka.cn/contribution/manga';
  $headers = array(
'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
'Accept-Encoding:gzip, deflate',
'Accept-Language:zh-CN,zh;q=0.9,en;q=0.8,ja;q=0.7',
'Cache-Control:max-age=0',
'Connection:keep-alive',
'Host:td.buka.cn',
'Origin:http://td.buka.cn',
'Referer:http://td.buka.cn/',
'Upgrade-Insecure-Requests:1',
'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36'
);

  $listurl = 'http://td.buka.cn/user/manga';
  http_get_si($listurl);

  $imguploadurl = 'http://td.buka.cn/contribution/uploadLogo?type=cover';
  $ctverticalimage = $ctinfo['ctverticalimage'];
  $imgfile = substr($ctverticalimage, strrpos($ctverticalimage,'/')+1);
  $newimgfile = sprintf('/tmp/%s.png',$imgfile);
  //$newimgfile = sprintf('/tmp/%s.png',md5($imgfile));
  $imgcnt = file_get_contents($ctinfo['ctverticalimage'].'-origin');////开启原图保护后加-origin访问原图
  $newimgfile = sprintf('/tmp/%s.png',md5($imgcnt));
  file_put_contents($newimgfile, $imgcnt);
  $postdata = array('picfile'=>sprintf("@%s", $newimgfile));
  $postret = http_post_si($imguploadurl,$postdata,$headers);
  unlink($newimgfile);
  $postret = json_decode($postret,true);
  var_dump($postdata,$postret);

  exit();
  //$ctrow[''] = $postret['logo'];

  $imguploadurl = 'http://td.buka.cn/contribution/uploadLogo?type=banner';
  $cthorizontalimage = $ctinfo['cthorizontalimage'];
  $imgfile = substr($ctverticalimage, strrpos($cthorizontalimage,'/')+1);
  $newimgfile = sprintf('/tmp/%s.png',$imgfile);
  $imgcnt = file_get_contents($ctinfo['cthorizontalimage'].'-origin');////开启原图保护后加-origin访问原图
  file_put_contents($newimgfile, $imgcnt);
  $postdata = array('picfile'=>sprintf("@%s", $newimgfile));
  $postret = http_post_si($imguploadurl,$postdata, $headers);
  unlink($newimgfile);
  $postret = json_decode($postret,true);
  $ctrow['pic'] = $postret['logo'];
  $ctrow['logodir'] = $postret['logodir'];
  $ctrow['category'] = '';

  $ctrow['type'] = 1;
  $ctrow['name'] = $ctinfo['ctname'];
  $ctrow['author'] = $ctinfo['ctauthorname'];
  $ctrow['alias'] = '';
  $ctrow['intro'] = $ctinfo['ctdesc'];
  $ctrow['finish'] = 0;
  $ctrow['tag'] = '';

  $submiturl = $action;
  $postret = http_post_si($submiturl, $ctrow);
  return json_decode($postret,true);
}/*}}}*/
/*}}}*/

/*{{{ 网易 */
function test_account_for_wangyi($username,$password)
{/*{{{*/
  delete_cookie_file();
  $url = 'https://manhua.163.com/';
  $cnt = http_get_si($url);
  $lpos = strpos($cnt, 'csrfToken');
  $lpos = strpos($cnt, '"', $lpos);
  $rpos = strpos($cnt, '"', $lpos+3);
  $token = substr($cnt, $lpos+1, $rpos-$lpos-1);

  $loginurl = 'https://manhua.163.com/login/upCheck';
  $data = array('username'=>$username, 'password'=>md5($password), 'csrfToken'=>$token,'redirect'=>'/','remember'=>true);
  $ret = http_post_header_si($loginurl, $data);
  $succ = false;
  if($ret['http_code'] == 302)
  {
    $header = $ret['headinfo'];
    $lines = explode("\n", $header);
    foreach($lines as $line)
    {
      $line = trim($line);
      if(strpos($line, 'Location') === 0)
      {
        if(strpos($line, 'login') === false)
          $succ = true;
        break;
      }
    }
  }
  return $succ;
}/*}}}*/

/**
 * 内存使用量
 * @return string
 */
function memory_usage() {
    $memory     = ( !function_exists('memory_get_usage')) ? '0' : round(memory_get_usage()/1024/1024, 2).'MB';
    return $memory;
}

function get_cartoonlist_for_wangyi()
{/*{{{*/
  $totalPage = 1;
  $list = array();
  for ($curPage = 1;$curPage<=$totalPage;$curPage++){
    sleep(1);
    $url = 'https://zz.manhua.163.com/book/list?currentPage='.$curPage;
    $cnt= http_get_si($url);
    //error_log($cnt,3,'/home/search/meizizi/error_log/163_cartoon_list_'.$curPage.'.html');//debug
    if($curPage == 1){
        $pattPage = '/book\/list\?currentPage=(\d+)/';
        preg_match_all($pattPage,$cnt,$matched);
        if(empty($matched))
            $totalPage = 1;
        else
            $totalPage = array_pop($matched[1]);
    }
    unset($matched);
    $lpos = strpos($cnt, 'table');
    $rpos = strpos($cnt, "</table", $lpos);
    $htstr = substr($cnt, $lpos, $rpos-$lpos-1);
    $str = str_replace(array("\r\n", "\r", "\n"), '', $htstr);
    //error_log($cnt,3,'/home/search/meizizi/error_log/163_cartoon_list_before_preg.log');//debug
    unset($cnt,$htstr);
    $patt = '/href="([^<>_=]*)?".*?src="([^<>]*)?".*?title">([^<>]*)?<span.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<\/li>/';
    $patt = '/href="([^<>_=]*?)".*?src="([^<>]*?)".*?info.*?>([^<>]*?)<\/a.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<td>.*?<\/td>.*?<td>(.*?)<\/td>.*?<\/tr>/';
    $patt = '/href="([^<>_=]*?)".*?src="([^<>]*?)".*?info.*?>([^<>]*?)<\/a.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<td>(.*?)<\/td>.*?<td>(.*?)<\/td>.*?<\/tr>/';
    preg_match_all($patt, $str, $ret);
    unset($str);
    // error_log(json_encode($ret),3,'/home/search/meizizi/error_log/163_cartoon_list_preg.log');//debug

    foreach($ret[0] as $idx=>$r)
    {
        $url = $ret[1][$idx];
        $id = substr($url, strrpos($url,'/')+1);
        $update = $ret[4][$idx];
        $click = substr(str_replace('&nbsp;','',$ret[6][$idx]), strlen('点击：'));
        $coll  = substr(str_replace('&nbsp;','',$ret[7][$idx]), strlen('收藏：'));
        $row = array('ctsourceid'=>$id, 'ctverticalimage'=>$ret[2][$idx], 'ctname'=>trim($ret[3][$idx]), 'ctstate1'=>$ret[8][$idx], 'ctstate2'=>$ret[9][$idx], 'ctbrowsercount'=>$click, 'ctcollectcount'=>$coll,'ctupdateat'=>$update);

        $sectionlist = get_cartoomsectionlist_for_wangyi($row['ctsourceid']);
        $row['sectionlist'] = $sectionlist;

        $viewurl = sprintf('https://zz.manhua.163.com/book/view/%s', $id);
        $viewcnt = http_get_si($viewurl);
        $viewcnt = str_replace(array("\r\n", "\r", "\n"), '', $viewcnt);
        $lpos = strpos($viewcnt,'DRM.initBehaviors');
        $lpos = strpos($viewcnt, '(', $lpos);
        $rpos = strpos($viewcnt,')', $lpos);
        $cnt = substr($viewcnt, $lpos+1, $rpos-$lpos-1);
        unset($viewcnt);
        $cntdata = json_decode(str_replace("'",'"',$cnt), true);
        $row['ctprogress'] = $cntdata['workSerial']['value'];
        unset($cntdata);
        $list[] = $row;
    }
    unset($ret);
  }
  //error_log($beginMem.'/'.$endMem,3,'/home/search/meizizi/error_log/'.__FUNCTION__.'_memuse.log');//debug
  return $list;
}/*}}}*/




function get_cartoonlist_for_wangyi_old_2()
{/*{{{*/
    $url = 'https://zz.manhua.163.com/book/list';
    $cnt= http_get_si($url);
    //error_log($cnt,3,'/home/search/meizizi/error_log/163_cartoon_list.html');//debug
    $lpos = strpos($cnt, 'table');
    $rpos = strpos($cnt, "</table", $lpos);
    $htstr = substr($cnt, $lpos, $rpos-$lpos-1);
    $str = str_replace(array("\r\n", "\r", "\n"), '', $htstr);
    $patt = '/href="([^<>_=]*)?".*?src="([^<>]*)?".*?title">([^<>]*)?<span.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<\/li>/';
    $patt = '/href="([^<>_=]*?)".*?src="([^<>]*?)".*?info.*?>([^<>]*?)<\/a.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<td>.*?<\/td>.*?<td>(.*?)<\/td>.*?<\/tr>/';
    $patt = '/href="([^<>_=]*?)".*?src="([^<>]*?)".*?info.*?>([^<>]*?)<\/a.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<td>(.*?)<\/td>.*?<td>(.*?)<\/td>.*?<\/tr>/';
    //error_log($cnt,3,'/home/search/meizizi/error_log/163_cartoon_list_before_preg.log');//debug
    preg_match_all($patt, $str, $ret);
    //error_log(json_encode($ret),3,'/home/search/meizizi/error_log/163_cartoon_list_preg.log');exit;//debug

    $list = array();
    foreach($ret[0] as $idx=>$r)
    {
        $url = $ret[1][$idx];
        $id = substr($url, strrpos($url,'/')+1);
        $update = $ret[4][$idx];
        $click = substr(str_replace('&nbsp;','',$ret[6][$idx]), strlen('点击：'));
        $coll  = substr(str_replace('&nbsp;','',$ret[7][$idx]), strlen('收藏：'));
        $row = array('ctsourceid'=>$id, 'ctverticalimage'=>$ret[2][$idx], 'ctname'=>trim($ret[3][$idx]), 'ctstate1'=>$ret[8][$idx], 'ctstate2'=>$ret[9][$idx], 'ctbrowsercount'=>$click, 'ctcollectcount'=>$coll,'ctupdateat'=>$update);

        $sectionlist = get_cartoomsectionlist_for_wangyi($row['ctsourceid']);
        $row['sectionlist'] = $sectionlist;

        $viewurl = sprintf('https://zz.manhua.163.com/book/view/%s', $id);
        $viewcnt = http_get_si($viewurl);
        $viewcnt = str_replace(array("\r\n", "\r", "\n"), '', $viewcnt);
        $lpos = strpos($viewcnt,'DRM.initBehaviors');
        $lpos = strpos($viewcnt, '(', $lpos);
        $rpos = strpos($viewcnt,')', $lpos);
        $cnt = substr($viewcnt, $lpos+1, $rpos-$lpos-1);
        $cntdata = json_decode(str_replace("'",'"',$cnt), true);
        $row['ctprogress'] = $cntdata['workSerial']['value'];

        $list[] = $row;
    }
    return $list;
}/*}}}*/

function get_cartoonlist_for_wangyi_old()
{/*{{{*/
  $url = 'https://zz.manhua.163.com/';
  $cnt= http_get_si($url);

  $lpos = strpos($cnt, 'books');
  $rpos = strpos($cnt, "</ul", $lpos);
  $htstr = substr($cnt, $lpos, $rpos-$lpos-1);
  $str = str_replace(array("\r\n", "\r", "\n"), '', $htstr);
  $patt = '/href="([^<>_=]*)?".*?src="([^<>]*)?".*?title">([^<>]*)?<span.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<p>([^<>]*)?<\/p>.*?<\/li>/';
  preg_match_all($patt, $str, $ret);

  $list = array();
  foreach($ret[0] as $idx=>$r)
  {
    $url = $ret[1][$idx];
    $id = substr($url, strrpos($url,'/')+1);
    $update = substr($ret[6][$idx], strlen('更新：'));
    $click = substr($ret[7][$idx], strlen('点击：'));
    $coll  = substr($ret[8][$idx], strlen('收藏：'));
    $row = array('ctsourceid'=>$id, 'ctverticalimage'=>$ret[2][$idx], 'ctname'=>trim($ret[3][$idx]), 'ctprogress'=>$ret[4][$idx], 'ctbrowsercount'=>$click, 'ctcollectcount'=>$coll,'ctupdateat'=>$update);

    $sectionlist = get_cartoomsectionlist_for_wangyi($row['ctsourceid']);
    $row['sectionlist'] = $sectionlist;
    $list[] = $row;
  }
  return $list;
}/*}}}*/

function get_cartoomsectionlist_for_wangyi($ctid)
{/*{{{*/
  $url = sprintf('https://zz.manhua.163.com/book/manage/%s', $ctid);
  $cnt = http_get_si($url);

  $lpos = strpos($cnt, 'articles');
  $rpos = strpos($cnt, "</ul>", $lpos);
  $htstr = substr($cnt, $lpos, $rpos-$lpos);
  $str = str_replace(array("\r\n", "\r", "\n"), '', $htstr);
  $patt = '/data-titletext="([^<>]*)?".*?15%;">([^<>]*)?<\/div>.*?href="([^<>]*)?".*?<\/li>/';
  preg_match_all($patt, $str, $ret);
  $list = array();
  foreach($ret[0] as $idx=>$r)
  {
    $url = trim($ret[3][$idx], '/');
    $id = substr($url, strrpos($url,'/')+1);
    $row = array('ctssourceid'=>$id, 'ctsprogress'=>$ret[2][$idx], 'ctsname'=>trim($ret[1][$idx]));
    $list[] = $row;
  }

  return $list;
}/*}}}*/

/**
 * 获取网易漫画章节列表(包含未审核通过的)
 * @return array
 */
function get_cartoomsectionlist_for_wangyi_new($ctid)
{
    $url = sprintf('https://zz.manhua.163.com/book/manage/%s', $ctid);
    $cnt = http_get_si($url);

    $lpos = strpos($cnt, 'articles');
    $rpos = strpos($cnt, "</ul>", $lpos);
    $htstr = substr($cnt, $lpos, $rpos-$lpos);unset($cnt);
    $str = str_replace(array("\r\n", "\r", "\n"), '', $htstr);unset($htstr);

    //第一次匹配 以章节名为主 忽略章节ID
    $patt1 = '/title">([^<>]*)?<\/span>.*?15%;">([^<>]*)?<\/div>/';
    preg_match_all($patt1, $str, $ret1);
    $list1 = array();
    foreach($ret1[0] as $idx=>$r)
    {
        $tmpTitle = trim($ret1[1][$idx]);
        $tmpTitleArray = explode('：',$tmpTitle);
        unset($tmpTitleArray[0]);
        $title = implode('',$tmpTitleArray);

        $row = array('ctsprogress'=>$ret1[2][$idx], 'ctsname'=> $title);
        $list1[] = $row;
    }

    //第二次匹配
    $patt2 = '/data-titletext="([^<>]*)?".*?15%;">([^<>]*)?<\/div>.*?href="([^<>]*)?".*?<\/li>/';
    preg_match_all($patt2, $str, $ret2);
    $list2 = array();

    foreach($ret2[0] as $idx=>$r)
    {
        $url = trim($ret2[3][$idx], '/');
        $id = substr($url, strrpos($url,'/')+1);
        $row = array('ctssourceid'=>$id, 'ctsprogress'=>$ret2[2][$idx], 'ctsname'=>trim($ret2[1][$idx]));
        $list2[] = $row;
    }

    //融合结果
    foreach ($list1 as $k => $value){
        if(isset($list2[$k]['ctssourceid']))
            $list1[$k]['ctssourceid'] =  $list2[$k]['ctssourceid'];
        else
            $list1[$k]['ctssourceid'] =  null;
    }

    return $list1;
}

function get_cartoonstat_for_wangyi($ctid)
{/*{{{*/
  $stat = array();
  $url = 'https://zz.manhua.163.com/data?bookId='.$ctid;
  $cnt = http_get_si($url);
  $pos = strpos($cnt, 'DRM');
  $cnt = substr($cnt, $pos);

  $lpos = strpos($cnt, 'chart:');
  $rpos = strpos($cnt, "\n", $lpos);
  $line1 = substr($cnt, $lpos+6, $rpos-$lpos-7);
  $line1 = trim($line1);

  $lpos = strpos($cnt, 'chart:', $rpos);
  $rpos = strpos($cnt, "\n", $lpos);
  $line2 = substr($cnt, $lpos+6, $rpos-$lpos-7);
  $line2 = trim($line2);
  $d1 = json_decode($line1, true);
  $d2 = json_decode($line2, true);
  if(!empty($d1))
  {
    foreach($d1['date'] as $idx=>$v)
      $stat['tucao'][$v] = $d1['data'][$idx];
  }
  if(!empty($d2))
  {
    foreach($d2['date'] as $idx=>$v)
      $stat['click'][$v] = $d2['data'][$idx];
  }

  /*{{{ income */
  /*$url = 'https://zz.manhua.163.com/income_specific?bookId='.$ctid;
  $getret = http_get_si($url);
  $lpos = strpos($getret,'<select');
  $rpos = strpos($getret,'</select>',$lpos);
  $substr = substr($getret, $lpos, $rpos-$lpos);
  $patt = '/value="(.*?)">/';
  preg_match_all($patt, $substr, $match);
  $years = array();
  foreach($match[0] as $idx=>$v)
    $years[] = $match[1][$idx];
  $income = array();
  foreach($years as $y)
  {
    $url = sprintf('https://zz.manhua.163.com/income_specific?bookId=%s&year=%s', $ctid, $y);
    $getret = http_get_si($url);
    $getret = str_replace(array("\r\n","\r","\n"),'',$getret);
    $patt = '/<tr>.*?<td>(.*?)月<\/td>.*?<td>(.*?)<\/td>.*?<td>(.*?)<\/td>.*?<\/tr>/';
    preg_match_all($patt, $getret ,$matches);
    foreach($matches[0] as $idx=>$v)
    {
      $income[$y][$matches[1][$idx]] = array('sale'=>$matches[2][$idx], 'shang'=>$matches[3][$idx]);
    }
  }
  $stat['income'] = $income;
  /*}}}*/

  /*{{{ revenue_specific */
  $url = 'https://zz.manhua.163.com/revenue_specific?bookId='.$ctid;
  $getret = http_get_si($url);
  $patt = '/<div class="mh-time">漫画发布时间：(.*?)年(.*?)月.*?日<\/div>/';
  preg_match($patt, $getret, $match);
  $year = $match[1];
  $mon = $match[2];

  $dashangstat = get_cartoondashang_for_wangyi($ctid, $year);

  $income = array();
  while(true)
  {
    $url = sprintf('https://zz.manhua.163.com/revenue_specific?bookId=%s&year=%s&month=%s', $ctid, $year, $mon);
    $getret = http_get_si($url);
    $getret = str_replace(array("\r\n","\r","\n"),'',$getret);

    $patt = '/<span>包月分成：(.*?)<\/span>/';
    preg_match($patt,$getret,$match);
    $t = floatval($match[1]);

    $patt = '/<tr>.*?<td.*?>.*?(-\\d*?)<\/td>.*?<td.*?>(.*?)<\/td>.*?<td.*?>(.*?)<\/td>.*?<\/tr>/';
    preg_match_all($patt, $getret ,$matches);
    $d = strtotime(sprintf("%s-%s-01 00:00:00", $year, $mon));
    $curincome = array();
    $imcount = 0;
    foreach($matches[0] as $idx=>$v)
    {
      $date = sprintf('%s%s', date("Y-m", $d), $matches[1][$idx]);
      $date = date('Y-m-d', strtotime($date));
      if(($matches[2][$idx]!='0.00') || ($matches[3][$idx]!='0.00')){
        $curincome[$date] = array('sale'=>$matches[2][$idx]+$matches[3][$idx]);
        ++$imcount;
      }
    }
    foreach($curincome as $idx=>$row)
    {
      $curincome[$idx]['sale'] += $t/count($curincome);
      if(isset($dashangstat[$year][ltrim($mon)]))
        $curincome[$idx]['sale'] += $dashangstat[$year][ltrim($mon)]/count($curincome);
    }
    $income = array_merge($income, $curincome);

    $cd = strtotime('+1month', $d);
    if($cd > time())
      break;

    $year = date('Y', $cd);
    $mon = ltrim(date('m', $cd));
  }
  ksort($income);
  $stat['income'] = $income;
  /*}}}*/

  return $stat;
}/*}}}*/

function get_cartoondashang_for_wangyi($ctid, $year)
{
  $dashangstat = array();
  while(true){
    $url = sprintf('https://zz.manhua.163.com/welfare_specific?bookId=%s&year=%s', $ctid, $year);
    $cnt = http_get_si($url);
    $patt = '/<tr>.*?<td>(.*?)月<\/td>.*?<td>(.*?)<\/td>.*?<td>(.*?)<\/td>.*?<\/tr>/';
    $cnt = str_replace(array("\r\n", "\r", "\n"), '', $cnt);
    preg_match_all($patt, $cnt, $ret);
    foreach($ret[0] as $idx=>$r)
    {
      $f = floatval($ret[3][$idx]);
      if($f > 0)
        $dashangstat[$year][$ret[1][$idx]] = $f;
    }

    $year += 1;
    if($year > date('Y'))
      break;
  }
  return $dashangstat;
}

function get_cartoonincome_for_wangyi()
{/*{{{*/
  $stat = array();
  $url = 'https://zz.manhua.163.com/income';
  $cnt = http_get_si($url);
  $patt = '/<span style="color:#f4555e">￥(.*?)<.*?style="color:#f4555e">￥(.*?)<\/span> \( 销售(.*?) \+ 打赏(.*?) \).*?style="color:#f4555e">￥(.*?)<\/span> \( 销售(.*?) \+ 打赏(.*?) \)/';
  preg_match($patt, $cnt, $ret);
  $stat['total'] = $ret[5];
  $stat['totalsale'] = $ret[6];
  $stat['totalshang'] = $ret[7];
  $stat['lastmonth'] = $ret[2];
  $stat['lastmonthsale'] = $ret[3];
  $stat['lastmonthshang'] = $ret[4];
  $stat['lastday'] = $ret[1];

  $url = 'https://zz.manhua.163.com/revenue';
  $cnt = http_get_si($url);

  $url = 'https://zz.manhua.163.com/welfare';
  $cnt = http_get_si($url);

  return $stat;
}/*}}}*/

function get_cartoonmessage_for_wangyi()
{/*{{{*/
  $url = 'https://zz.manhua.163.com/message';
  $cnt = http_get_si($url);
  $lpos = strpos($cnt, 'table');
  $rpos = strpos($cnt, "</table", $lpos);
  $htstr = substr($cnt, $lpos, $rpos-$lpos-1);
  $str = str_replace(array("\r\n", "\r", "\n"), '', $htstr);

  $patt = '/<tr>.*?>([^<>]*?)<\/p.*?>([^<>]*?)<\/p.*?<td>(.*?)<\/td>.*?<td>(.*?)<\/td>.*?<\/tr>/';
  preg_match_all($patt, $str, $ret);

  $msgs = array();
  foreach($ret[0] as $idx=>$row)
  {
    $msgs[] = array('title'=>$ret[1][$idx], 'content'=>$ret[2][$idx], 'auther'=>$ret[3][$idx], 'createtime'=>$ret[4][$idx]);
  }

  return $msgs;
}/*}}}*/

function get_cartooninfo_for_wangyi_from_website($ctid)
{/*{{{*/
  $ctinfo = array();
  $url = sprintf('https://manhua.163.com/book/catalog/%s.json?_c=%s445', $ctid, time());
  $getret = http_get_si($url);
  $getret = json_decode($getret, true);
  if(isset($getret['catalog']['sections']) && (count($getret['catalog']['sections'])>0))
  {
    $ctinfo['sectionlist'] = $getret['catalog']['sections'][0]['sections'];
  }
  return $ctinfo;
}/*}}}*/

function createcartoon_for_wangyi($ctinfo)
{/*{{{*/
  global $logger;
  global $corrsubs;
  global $wysubs;
  $action = 'https://zz.manhua.163.com/book/create';

  $addurl = 'https://zz.manhua.163.com/book/create';
  $cnt = http_get_si($addurl);
  $lpos = strpos($cnt, 'csrfToken');
  //$lpos = strpos($cnt, '"', $lpos);
  //$rpos = strpos($cnt, '"', $lpos+3);
  //$token = substr($cnt, $lpos+1, $rpos-$lpos-1);
  $lpos = strpos($cnt, '=', $lpos);
  $rpos = strpos($cnt, '"', $lpos+3);
  $token = substr($cnt, $lpos+2, $rpos-$lpos-2);

  $ctrow['operation'] = 0;
  $ctrow['individual'] = 0;
  $ctrow['csrfToken'] = $token;
  $ctrow['bookId'] = 0;
  $ctrow['title'] = $ctinfo['ctname'];
  $ctrow['author'] = $ctinfo['ctauthorname'];

  $imguploadurl = 'https://zz.manhua.163.com/upload.json?csrfToken='.$token;
  $ctverticalimage = $ctinfo['ctverticalimage'];
  $imgfile = substr($ctverticalimage, strrpos($ctverticalimage,'/')+1);
  $newimgfile = sprintf('/tmp/%s.png',$imgfile);
  $imgcnt = file_get_contents($ctinfo['ctverticalimage'].'-origin');////开启原图保护后加-origin访问原图
  file_put_contents($newimgfile, $imgcnt);
  $postdata = array('file'=>sprintf("@%s", $newimgfile),'id'=>'WU_FILE_0');
  $postret = http_post_si($imguploadurl,$postdata);

  //删除下载的临时图片
  if(!empty($newimgfile))
    unlink($newimgfile);
  qLogInfo($logger, sprintf("createcartoon_for_wangyi imageupload1 request %s %s", $imguploadurl, json_encode($postdata)));
  qLogInfo($logger, sprintf("createcartoon_for_wangyi imageupload1 response %s", $postret));
  $postret = json_decode($postret,true);
  $ctrow['coverInfo'] = $postret['data'][0]['url'];

  $imguploadurl = 'https://zz.manhua.163.com/upload.json?csrfToken='.$token;
  $cthorizontalimage = $ctinfo['cthorizontalimage'];
  $imgfile = substr($ctverticalimage, strrpos($cthorizontalimage,'/')+1);
  $newimgfile = sprintf('/tmp/%s.png',$imgfile);
  $imgcnt = file_get_contents($ctinfo['cthorizontalimage'].'-origin');////开启原图保护后加-origin访问原图
  file_put_contents($newimgfile, $imgcnt);
  $postdata = array('file'=>sprintf("@%s", $newimgfile),'id'=>'WU_FILE_1');
  $postret = http_post_si($imguploadurl,$postdata);

  //删除下载的临时图片
  if(!empty($newimgfile))
    unlink($newimgfile);
  qLogInfo($logger, sprintf("createcartoon_for_wangyi imageupload2 request %s %s", $imguploadurl, json_encode($postdata)));
  qLogInfo($logger, sprintf("createcartoon_for_wangyi imageupload2 response %s", $postret));
  $postret = json_decode($postret,true);
  $ctrow['recLatestCover'] = $postret['data'][0]['url'];

  $corrusergroups = array(USERGROUP_NAN=>0,USERGROUP_NV=>1,USERGROUP_ALL=>1);
  $ctrow['readerType'] = $corrusergroups[$ctinfo['ctusergroup']];
  $types = array(MAN_VECTOR_PAGE=>'0', MAN_VECTOR_TIAO=>'1', MAN_VECTOR_CHA=>'2');
  $ctrow['bookType'] = $types[$ctinfo['ctvector']];
  //$ctrow['subject'] = '16,13';
  $ctrow['issueStatus'] = 1;
  $ctrow['publishType'] = 0;
  if($ctinfo['csreleasetype'] == 5)
  {
    $ctrow['publishType'] = 1;
    $ctrow['issueStatus'] = 0;
  }
  else
  {
    if($ctinfo['csfirstrelease'] == 5)
      $ctrow['issueStatus'] = 0;
  }
  $ctrow['tucaoEnable'] = 1;
  $ctrow['shortIntro'] = mb_substr($ctinfo['ctdesc'],0,9);
  $ctrow['description'] = $ctinfo['ctdesc'];
  $ctrow['agreement'] = 'on';
  //var_dump($action,$ctrow);
  //exit();

  $ctsubinfos = $ctinfo['ctsubinfos'];
//  var_dump($ctsubinfos);//debug
  $ctsubs = GetItemsFromArray($ctsubinfos,'ctsuid');
  $postdata = http_build_query($ctrow);
  $subs = array();
//  var_dump($ctsubs);//debug
  foreach($ctsubs as $sub)
  {
    if(isset($corrsubs[$sub]))
    {
      if(isset($wysubs[$corrsubs[$sub][SOURCE_WANGYI]]))
        $subs[] = $wysubs[$corrsubs[$sub][SOURCE_WANGYI]];
    }
  }
  $ctrow['subject'] = implode(',', $subs);

  qLogInfo($logger, json_encode($ctrow));
  $submiturl = $action;
  $postret = http_post_header_si($submiturl, $ctrow);
  qLogInfo($logger, json_encode($postret));
  $ctid = '';
  if($postret['http_code'] == 302)
  {
    $header = $postret['headinfo'];
    $lines = explode("\n", $header);
    foreach($lines as $line)
    {
      $line = trim($line);
      if(stripos($line, 'Location') === 0)
      {
        $pos = strrpos($line,'/');
        $ctid = substr($line, $pos+1);
        break;
      }
    }
  }
  return $ctid;
}/*}}}*/

function createsection_for_wangyi($sectinfo)
{/*{{{*/
  global $logger;
  $action = 'https://zz.manhua.163.com/article/create.json';

  $cturl  = 'https://zz.manhua.163.com/article/create/'.$sectinfo['cssourceid'];
  $cnt = http_get_si($cturl);
  //$lpos = strpos($cnt, 'csrfToken');
  //$lpos = strpos($cnt, '"', $lpos);
  //$rpos = strpos($cnt, '"', $lpos+3);
  //$token = substr($cnt, $lpos+1, $rpos-$lpos-1);
  $lpos = strpos($cnt, 'csrfToken" value="');
  $lpos = strpos($cnt, '=', $lpos);
  $rpos = strpos($cnt, '"', $lpos+3);
  $token = substr($cnt, $lpos+2, $rpos-$lpos-2);
  $ctsrow = array();
  $ctsrow['titleOrder'] = $sectinfo['ctssort'];
  $ctsrow['titleText'] = $sectinfo['ctsname'];
  $ctsrow['csrfToken'] = $token;
  $ctsrow['chapterId'] = 0;
  $ctsrow['sectionId'] = 0;
  $ctsrow['bookId'] = $sectinfo['cssourceid'];
  if($sectinfo['ctsvip'] == 5){
    $ctsrow['needPay'] = 1;
    $ctsrow['price'] = 50;
    if(isset($sectinfo['ctprices'][SOURCE_WANGYI])){
      $ctsrow['price'] = $sectinfo['ctprices'][SOURCE_WANGYI];
    }
  }
  if(!empty($sectinfo['ctrrreleasetime'])){
    $ctsrow['autoPublish'] = 1;
    $ctsrow['autoPublishDate'] = substr($sectinfo['ctrrreleasetime'],0,10);
    $ctsrow['autoPublishHour'] = ltrim(substr($sectinfo['ctrrreleasetime'],11,2));
    $ctsrow['autoPublishMinute'] = '0';
  }

  $imguploadurl = 'https://zz.manhua.163.com/uploadPrivate.json?csrfToken='.$token;
  $content = json_decode($sectinfo['ctscontent'], true);
  $ctsrow['images'] = array();
  $imguploadsucc = true;
  foreach($content as $idx=>$img)
  {
    $url = $img['imgurl'];
    $newimgfile = downimg_to_local($url);
    if(empty($newimgfile)){
      $imguploadsucc = false;
      break;
    }else{
      $postdata = array('id'=>'WU_FILE_'.$idx,'file'=>sprintf("@%s", $newimgfile));
      $postret = http_post_si($imguploadurl,$postdata);
      qLogInfo($logger, sprintf("createsection_for_wangyi imageupload request %s %s", $imguploadurl, json_encode($postdata)));
      qLogInfo($logger, sprintf("createsection_for_wangyi imageupload response %s", $postret));
      $postret = json_decode($postret,true);
      $imgrow = array('csrfToken'=>$token,'bookId'=>$sectinfo['cssourceid'],'sectionId'=>0,'bookTitle'=>$sectinfo['ctname'],'quality'=>filesize($newimgfile),'size'=>sprintf("%sX%s", $postret['data'][0]['imageWidth'],$postret['data'][0]['imageHeight']),'duration'=>1032,'picTitle'=>substr($newimgfile, strrpos($newimgfile,'/')+1),'_'=>time().'123');
      $uploadpic = 'https://zz.manhua.163.com/article/uploadPic_info?';
      $ret = http_get_si($uploadpic.http_build_query($imgrow));
      qLogInfo($logger, sprintf("createsection_for_wangyi uploadpicinfo request %s %s", $uploadpic, json_encode($imgrow)));
      qLogInfo($logger, sprintf("createsection_for_wangyi uploadpicinfo response %s", $ret));
      $ctsrow['images'][] = array('imageUrl'=>$postret['data'][0]['url'],'title'=>substr($newimgfile, strrpos($newimgfile,'/')+1),'key'=>$postret['data'][0]['key'], 'type'=>$postret['data'][0]['type'], 'imageId'=>0, 'editVerifyCode'=>$postret['data'][0]['editVerifyCode']);
    }
  }

  if($imguploadsucc){
    $ctsrow['images'] = json_encode($ctsrow['images']);
    $submiturl = $action;
    $postret = http_post_si($submiturl, $ctsrow);
    qLogInfo($logger, sprintf("createsection_for_wangyi submit %s", json_encode($ctsrow)));
  }else{
    $postret = json_encode(array('error'=>array('code'=>1, 'message'=>'章节图片上传失败，请重新提交后重试')));
  }

  //{"error":{"message":"话创建成功","code":0}}
  return json_decode($postret,true);
}/*}}}*/
/*}}}*/

/*{{{ 腾讯 */
function test_account_for_tencent($username,$password)
{/*{{{*/
  global $base_dir;
  delete_cookie_file();

  $casperpath = '/home/search/phantomjs/casperjs-1.1.4-1/bin/casperjs';
  $casperfile = '/home/search/phantomjs/qq.js';
  $cookiefile = get_cookie_file();
  $cmd = sprintf('%s %s %s %s %s', $casperpath, $casperfile, $username, $password, $cookiefile);
  exec($cmd);

  $cnt = file_get_contents($cookiefile);
  $d = json_decode($cnt,true);
  $cookies = '';
  foreach($d as $row)
  {
    $cookies .= sprintf("%s=%s; ", $row['name'], $row['value']);
  }
  $headers = get_request_headers($cookies);
  $url = 'http://ac.qq.com/MyComic?auth=1';
  $getret = http_get_si($url, 30, $headers);
  if(strpos($getret, '用户登录 - 腾讯动漫') === false)
    return $cookies;
  else
    return false;
}/*}}}*/

function get_loginstate_for_tencent($cookies)
{
  $url = 'http://ac.qq.com/MyComic?auth=1';
  $headers = get_request_headers($cookies);
  $cnt= http_get_si($url,30, $headers);
  if(strpos($cnt, '用户登录 - 腾讯动漫') === false)
    return true;
  return false;
}

function get_cartoonlist_for_tencent($cookies)
{/*{{{*/
  $url = 'http://ac.qq.com/MyComic?auth=1';
  $headers = get_request_headers($cookies);
  $cnt= http_get_si($url,30, $headers);

  $lpos = strpos($cnt, 'h_a_works_list_wr');
  $rpos = strpos($cnt, "mod_page", $lpos);
  $htstr = substr($cnt, $lpos, $rpos-$lpos-1);
  $htstr = str_replace(array("\r\n", "\r", "\n"), '', $htstr);
  $patt = '/href="(.*?)".*?src="(.*?)".*?漫画名称：(.*?)笔名：([^<>]*?)<.*?([^<>]*?)<\/span>.*?腾讯首发：(.*?)<.*?腾讯独家：(.*?)<.*?付费作品：(.*?)<.*?创建时间：(.*?)<.*?更新时间：(.*?)<.*?进度：(.*?)<.*?总浏览量：(.*?)<.*?收藏：(.*?)</';
  $patt = '/href="(.*?)".*?src="(.*?)".*?([^<>]*?)<\/span>.*?漫画名称：(.*?)笔名：([^<>]*?)<.*?腾讯首发：(.*?)<.*?腾讯独家：(.*?)<.*?付费作品：(.*?)<.*?创建时间：(.*?)<.*?更新时间：(.*?)<.*?进度：(.*?)<.*?总浏览量：(.*?)<.*?收藏：(.*?)<.*?href="(.*?)".*?p-audit/';
  preg_match_all($patt, $htstr, $ret);

  $list = array();
  foreach($ret[0] as $idx=>$r)
  {
    $url = $ret[1][$idx];
    $id = substr($url, strrpos($url,'/')+1);
    if(intval($id) == 0)
    {
      $url = $ret[14][$idx];
      $id = substr($url, strrpos($url,'/')+1);
    }
    $row = array('ctsourceid'=>$id, 'ctverticalimage'=>$ret[2][$idx], 'ctname'=>trim(str_replace('&nbsp;','',$ret[4][$idx])), 'ctprogress'=>$ret[11][$idx], 'ctbrowsercount'=>$ret[12][$idx], 'ctcollectcount'=>$ret[13][$idx]);

    list($info,$sectionlist) = get_cartoomsectionlist_for_tencent($row['ctsourceid'], $cookies);
    foreach($info as $k=>$v)
      $row[$k] = $v;
    $row['sectionlist'] = $sectionlist;
    $list[] = $row;
  }

  if(count($list) == 5){
    $page = 2;
    while(true){
      $url = sprintf('http://ac.qq.com/MyComic/comicList/page/%d/key/', $page);
      $headers = get_request_headers($cookies);
      $cnt= http_get_si($url,30, $headers);

      $lpos = strpos($cnt, 'h_a_works_list_wr');
      $rpos = strpos($cnt, "mod_page", $lpos);
      $htstr = substr($cnt, $lpos, $rpos-$lpos-1);
      $htstr = str_replace(array("\r\n", "\r", "\n"), '', $htstr);
      $patt = '/href="(.*?)".*?src="(.*?)".*?漫画名称：(.*?)笔名：([^<>]*?)<.*?([^<>]*?)<\/span>.*?腾讯首发：(.*?)<.*?腾讯独家：(.*?)<.*?付费作品：(.*?)<.*?创建时间：(.*?)<.*?更新时间：(.*?)<.*?进度：(.*?)<.*?总浏览量：(.*?)<.*?收藏：(.*?)</';
      $patt = '/href="(.*?)".*?src="(.*?)".*?([^<>]*?)<\/span>.*?漫画名称：(.*?)笔名：([^<>]*?)<.*?腾讯首发：(.*?)<.*?腾讯独家：(.*?)<.*?付费作品：(.*?)<.*?创建时间：(.*?)<.*?更新时间：(.*?)<.*?进度：(.*?)<.*?总浏览量：(.*?)<.*?收藏：(.*?)<.*?href="(.*?)".*?p-audit/';
      preg_match_all($patt, $htstr, $ret);

      foreach($ret[0] as $idx=>$r)
      {
        $url = $ret[1][$idx];
        $id = substr($url, strrpos($url,'/')+1);
        if(intval($id) == 0)
        {
          $url = $ret[14][$idx];
          $id = substr($url, strrpos($url,'/')+1);
        }
        $row = array('ctsourceid'=>$id, 'ctverticalimage'=>$ret[2][$idx], 'ctname'=>trim(str_replace('&nbsp;','',$ret[4][$idx])), 'ctprogress'=>$ret[11][$idx], 'ctbrowsercount'=>$ret[12][$idx], 'ctcollectcount'=>$ret[13][$idx]);

        list($info,$sectionlist) = get_cartoomsectionlist_for_tencent($row['ctsourceid'], $cookies);
        foreach($info as $k=>$v)
          $row[$k] = $v;
        $row['sectionlist'] = $sectionlist;
        $list[] = $row;
      }
      if(count($ret[0])!=5)
        break;
      ++$page;
    }
  }

  return $list;
}/*}}}*/

function get_cartoomsectionlist_for_tencent($ctid,$cookies)
{/*{{{*/
  $url = sprintf('http://ac.qq.com/MyComic/chapterList/id/%s', $ctid);
  $headers = get_request_headers($cookies);
  $cnt = http_get_si($url, 30, $headers);

  $ctinfo = array();

  $lpos = strpos($cnt, 'chapter_list');
  $rpos = strpos($cnt, "</table>", $lpos);
  $htstr = substr($cnt, $lpos, $rpos-$lpos-1);
  $htstr = str_replace(array("\r\n", "\r", "\n"), '', $htstr);
  $patt = '/chapter_id=\'(.*?)\'.*?td>(.*?)<.*?<td>(.*?)<.*?<td>(.*?)<.*?<td>(.*?)<.*?<td>(.*?)<.*?<td>(.*?)<.*?<td>(.*?)<.*?<td.*?<\/tr>/';
  preg_match_all($patt, $htstr, $ret);

  $list = array();
  foreach($ret[0] as $idx=>$r)
  {
    $id = $ret[1][$idx];
    $row = array('ctssourceid'=>$id, 'ctsname'=>trim($ret[3][$idx]),'ctsprogress'=>$ret[7][$idx]);
    $list[] = $row;
  }

  return array($ctinfo,$list);
}/*}}}*/

function get_cartoomsectionlist_for_tencent_old2($ctid,$cookies)
{/*{{{*/
  $url = sprintf('http://ac.qq.com/Comic/ComicInfo/id/%s', $ctid);
  $headers = get_request_headers($cookies);
  $cnt = http_get_si($url, 30, $headers);

  $ctinfo = array();

  $lpos = strpos($cnt, 'works-intro-digi');
  $rpos = strpos($cnt, "works-intro-opera", $lpos);
  $htstr = substr($cnt, $lpos, $rpos-$lpos-1);
  $htstr = str_replace(array("\r\n", "\r", "\n"), '', $htstr);
  $patt = '/作者：.*?>(.*?)图：(.*?)文：(.*?)<.*?gray9">(.*?)</';
  preg_match($patt, $htstr, $ret);
  if(empty($ret))
  {
    $patt = '/作者：.*?>(.*?)<\/em>.*?<.*?gray9">(.*?)</';
    preg_match($patt, $htstr, $ret);
    $ctinfo['ctauthorname'] = str_replace('&nbsp;','',$ret[1]);
    $ctinfo['ctimageauthor'] = '';
    $ctinfo['cttextauthor'] = '';
    $ctinfo['ctdesc'] = trim($ret[2]);
  }
  else
  {
    $ctinfo['ctauthorname'] = str_replace('&nbsp;','',$ret[1]);
    $ctinfo['ctimageauthor'] = $ret[2];
    $ctinfo['cttextauthor'] = $ret[3];
    $ctinfo['ctdesc'] = trim($ret[4]);
  }

  $url = sprintf('http://ac.qq.com/MyComic/chapterList/id/%s', $ctid);
  $headers = get_request_headers($cookies);
  $cnt = http_get_si($url, 30, $headers);
  $lpos = strpos($cnt, 'chapter_list');
  $rpos = strpos($cnt, "h_a_w_manage_tips", $lpos);
  $htstr = substr($cnt, $lpos, $rpos-$lpos);
  $str = str_replace(array("\r\n", "\r", "\n"), '', $htstr);
  //$patt = '/td>(.?*)<.*?td>(.*?)<.*?td>(.*?)<.*?td>(.*?)<.*?td>(.*?)<.*?td>(.*?)<.*?td>(.*?)<.*?td.*?href="(.*?)".*?<\/tr>/';
  $patt = '/td>(.*?)<.*?<td>(.*?)<.*?<td>(.*?)<.*?<td>(.*?)<.*?<td>(.*?)<.*?<td>(.*?)<.*?<td>(.*?)<.*?<td.*?preview.*?href="(.*?)".*?<\/tr>/';
  preg_match_all($patt, $str, $ret);
  $list = array();
  foreach($ret[0] as $idx=>$r)
  {
    $url = $ret[8][$idx];
    $patt = '/\/([^\/]*?)\/cid\/(.*?)$/';
    preg_match($patt, $url, $subret);
    $id = sprintf("%s-%s", $subret[1], $subret[2]);
    $row = array('ctssourceid'=>$id, 'ctsname'=>trim($ret[2][$idx]),'ctsprogress'=>$ret[6][$idx]);
    $list[] = $row;
  }

  return array($ctinfo,$list);
}/*}}}*/

function get_cartoonstat_for_tencent($cookies)
{/*{{{*/
  $stat = array();
  $headers = get_request_headers($cookies);
  $url = 'http://ac.qq.com/MyComic/myWalletSingle';
  $begm = '2017-11-01';
  $begm = date('Y-m-01',strtotime('-1 month'));//默认从上月1号开始抓取
  while(true)
  {
    $postdata = array('stime'=>$begm, 'etime'=>$begm, 'search'=>'', 'finish_state'=>0, 'order'=>1);
    $cnt = http_post_si($url, $postdata, $headers);

    $lpos = strpos($cnt, 'anthor-table-wrap');
    $rpos = strpos($cnt, "</table>", $lpos);
    $htstr = substr($cnt, $lpos, $rpos-$lpos-1);
    $patt = '/<tr>.*?<td>(.*?)<\/td>.*?<td>(.*?)<\/td>.*?<td>(.*?)<\/td>.*?<td>(.*?)<\/td>.*?<td.*?>(.*?)<\/td>/';
    $str = str_replace(array("\r\n", "\r", "\n"), '', $htstr);
    preg_match_all($patt, $str, $ret);
    if(!empty($ret[0][0]))
    {
      foreach($ret[0] as $idx=>$r)
      {
        $row = array($ret[1][$idx], $ret[2][$idx], $ret[3][$idx],$ret[4][$idx],$ret[5][$idx]);
        $stat[$begm][] = $row;
      }
    }

    $begm = date("Y-m-d",strtotime($begm)+24*3600);
    if($begm > date('Y-m-d H:i:s'))
      break;
  }
  return $stat;
}/*}}}*/

//获取的是作品月销售
function get_cartoonstat_for_tencent_old($cookies)
{/*{{{*/
  $stat = array();
  $headers = get_request_headers($cookies);
  $url = 'http://ac.qq.com/MyComic/getMyWalletComicIncome';
  $begm = '2017-01-01 00:00:00';
  while(true)
  {
    $curm = substr($begm, 0, 7);
    $postdata = array('comicStartDate'=>$curm, 'comicEndDate'=>$curm, 'comicSearch'=>'');
    $postret = http_post_si($url, $postdata, $headers);
    $postret = json_decode($postret, true);
    if($postret['total'] > 0)
    {
      $stat[$curm] = $postret['list'];
    }

    $begm = date("Y-m-d H:i:s",strtotime('+1 month',strtotime($begm)));
    if($begm > date('Y-m-d H:i:s'))
      break;
  }
  return $stat;
}/*}}}*/

function get_cartoomsectionlist_for_tencent_old($ctid,$cookies)
{/*{{{*/
  $url = sprintf('http://ac.qq.com/Comic/ComicInfo/id/%s', $ctid);
  $headers = get_request_headers($cookies);
  $cnt = http_get_si($url, 30, $headers);

  $ctinfo = array();

  $lpos = strpos($cnt, 'works-intro-digi');
  $rpos = strpos($cnt, "works-intro-opera", $lpos);
  $htstr = substr($cnt, $lpos, $rpos-$lpos-1);
  $htstr = str_replace(array("\r\n", "\r", "\n"), '', $htstr);
  $patt = '/作者：.*?>(.*?)图：(.*?)文：(.*?)<.*?gray9">(.*?)</';
  preg_match($patt, $htstr, $ret);
  $ctinfo['ctauthorname'] = $ret[1];
  $ctinfo['ctimageauthor'] = $ret[2];
  $ctinfo['cttextauthor'] = $ret[3];
  $ctinfo['ctdesc'] = $ret[4];

  $lpos = strpos($cnt, 'works-chapter-list-wr');
  $rpos = strpos($cnt, "chapter-page-new", $lpos);
  $htstr = substr($cnt, $lpos, $rpos-$lpos);
  $str = str_replace(array("\r\n", "\r", "\n"), '', $htstr);
  $patt = '/href="(.*?)".*?>(.*?)</';
  preg_match_all($patt, $str, $ret);
  $list = array();
  foreach($ret[0] as $idx=>$r)
  {
    $url = $ret[1][$idx];
    $patt = '/\/([^\/]*?)\/cid\/(.*?)$/';
    preg_match($patt, $url, $subret);
    $id = sprintf("%s-%s", $subret[1], $subret[2]);
    $row = array('ctssourceid'=>$id, 'ctsname'=>trim($ret[2][$idx]));
    $list[] = $row;
  }

  return array($ctinfo,$list);
}/*}}}*/

function createcartoon_for_tencent($ctinfo,$cookies)
{/*{{{*/
  global $logger;
  global $corrtags;
  global $txtags;
  $action = '/MyComic/doCreateComic';
  $headers = get_request_headers($cookies);

  $ctrow = array();
  $ctrow['title'] = $ctinfo['ctname'];
  $ctrow['name']  = $ctinfo['ctauthorname'];
  $ctrow['gut_img'] = $ctinfo['ctimageauthor'];
  $ctrow['gut_text'] = $ctinfo['cttextauthor'];
  $types = array(MAN_VECTOR_PAGE=>'3', MAN_VECTOR_TIAO=>'7', MAN_VECTOR_CHA=>'7');

  $msg = '';
  $ctid = '';
  if(isset($types[$ctinfo['ctvector']]) && isset($corrtags[$ctinfo['cttid1']]) && isset($corrtags[$ctinfo['cttid2']])){
    $ctrow['type'] = $types[$ctinfo['ctvector']];
    $ctrow['contentTag1'] = $txtags[$corrtags[$ctinfo['cttid1']][SOURCE_TENCENT]]; //104;
    $ctrow['contentTag2'] = $txtags[$corrtags[$ctinfo['cttid2']][SOURCE_TENCENT]]; //105;
    $tags = array();
    foreach($ctinfo['cttypeinfos'] as $idx=>$row)
    {
      $tags[] = $row['cttpdesc'];
    }
    $ctrow['tagInfoInput'] = implode('|', $tags);//'5001|5002';
    $ctrow['finish_state'] = 1;

    $imguploadurl = 'http://ac.qq.com/MyComic/uploadCover/';
    if(!empty($ctinfo['ctverticalimage'])){
      $newimgfile = downimg_to_local($ctinfo['ctverticalimage']);
      $postdata = array('type'=>1, 'Filedata'=>sprintf("@%s", $newimgfile));
      $postret = http_post_si($imguploadurl,$postdata,$headers);
      qLogInfo($logger, sprintf("createcartoon_for_tencent updatecover1 %s", $postret));
      $lpos = strpos($postret,'{');
      $rpos = strrpos($postret, '}');
      $postret = json_decode(substr($postret, $lpos, $rpos-$lpos+1),true);
      $ctrow['cover_v_url'] = $postret['file'];

      $imguploadurl = 'http://ac.qq.com/MyComic/uploadCover/';
      qLogInfo($logger, sprintf("createcartoon_for_tencent cthorizontalimage %s", $ctinfo['cthorizontalimage']));
      $newimgfile = downimg_to_local($ctinfo['cthorizontalimage']);
      $postdata = array('type'=>2, 'Filedata'=>sprintf("@%s", $newimgfile));
      $postret = http_post_si($imguploadurl,$postdata,$headers);
      qLogInfo($logger, sprintf("createcartoon_for_tencent updatecover2 %s", $postret));
      $lpos = strpos($postret,'{');
      $rpos = strrpos($postret, '}');
      $postret = json_decode(substr($postret, $lpos, $rpos-$lpos+1),true);
      $ctrow['cover_h_url'] = $postret['file'];

      $ctrow['first_state'] = 1;
      $ctrow['sole_state'] = 1;
      if($ctinfo['csreleasetype'] == 5)
      {
        $ctrow['first_state'] = 2;
        $ctrow['sole_state'] = 2;
      }
      else
      {
        if($ctinfo['csfirstrelease'] == 5)
          $ctrow['first_state'] = 2;
      }
      $ctrow['nation_state'] = 1;
      $ctrow['brief_intrd'] = $ctinfo['ctdesc'];
      $ctrow['announce'] = $ctinfo['ctnotice'];
      $ctrow['real_name'] = $ctinfo['ctrealname'];
      $ctrow['identity_no'] = $ctinfo['ctidcardnum'];
      $ctrow['mobile'] = $ctinfo['ctcontact'];

      $url = 'http://ac.qq.com/MyComic/createComic';
      $cnt = http_get_si($url,30,$headers);
      $htstr = str_replace(array("\r\n","\r","\n"), '', $cnt);
      $patt = '/name="tokenKey" value="(.*?)"/';
      preg_match($patt, $htstr, $ret);
      $ctrow['tokenKey'] = $ret[1];

      qLogInfo($logger, json_encode($ctrow));
      $submiturl = 'http://ac.qq.com'.$action;
      $postret = http_post_header_si($submiturl, $ctrow, $headers);
      $succ = false;
      $ctid = '';
      if($postret['http_code'] == 302)
      {
        $header = $postret['headinfo'];
        $lines = explode("\n", $header);
        foreach($lines as $line)
        {
          $line = trim($line);
          if(strpos($line, 'Location') === 0)
          {///MyComic/createChapter/id/628919/
            $patt = '/id\/(.*?)\//';
            preg_match($patt, $line, $ret);
            $ctid = $ret[1];
            break;
          }
        }
      }
    }else{
      $msg = '请确认封面是否上传';
    }
  }else{
    $msg = '请完整填写作品信息';
  }

  return array('ctid'=>$ctid, 'msg'=>$msg);
}/*}}}*/

function createsection_for_tencent($sectinfo, $cookies)
{/*{{{*/
  global $logger;
  $action = 'https://zz.manhua.163.com/article/create.json';
  $headers = get_request_headers($cookies);

  $ctsrow = array();
  $cturl  = 'http://ac.qq.com/MyComic/createChapter/id/'.$sectinfo['cssourceid'];
  $cnt = http_get_si($cturl,30,$headers);
  $htstr = str_replace(array("\r\n","\r","\n"), '', $cnt);
  $patt = '/name="tokenKey"(.*?)value="(.*?)"/';
  preg_match($patt, $htstr, $ret);
  $tokenkey = $ret[2];
  if(!empty($tokenkey)){
    $ctsrow['tokenKey'] = $ret[2];
    qLogInfo($logger, sprintf("createsection_for_tencent tokenkey %s", $tokenkey));

    $upload = 'http://ac.qq.com/MyComic/doCreateChapter';
    $ctsrow['id'] = $sectinfo['cssourceid'];
    $ctsrow['ctitle'] = $sectinfo['ctsname'];
    $ctsrow['vip_state'] = 1;
    if($sectinfo['ctsvip'] == 5)
      $ctsrow['vip_state'] = 2;
    //$ctsrow['use-appoint'] = 0;
    if(!empty($sectinfo['ctrrreleasetime'])){
      $ctsrow['publish_time'] = date('Y-m-d H:00:00', strtotime($sectinfo['ctrrreleasetime']));
    }
    if(empty($sectinfo['ctrrpfsectionid'])){
      $postret = http_post_si($upload, $ctsrow, $headers);
      qLogInfo($logger, sprintf("createsection_for_tencent createchapter %s", $postret));
      $postret = json_decode($postret, true);
      $ctsid = isset($postret['cid'])? $postret['cid'] : null;
    }else{
      $ctsid = $sectinfo['ctrrpfsectionid'];
    }
    $submitret = array('code'=>1);
    if(!empty($ctsid))
    {
      $submitret['ctsid'] = $ctsid;
      $ctsplatformcoverinfos = json_decode($sectinfo['ctsplatformcoverinfos'], true);
      $ctscover = $sectinfo['ctscover'];
      if(isset($ctsplatformcoverinfos[5]))
        $ctscover = $ctsplatformcoverinfos[5]['verticalimg'];
      $imguploadurl = sprintf('http://ac.qq.com/MyComic/uploadLightCover/type/2/id/%s/cid/%s', $sectinfo['cssourceid'], $ctsid);
      $ctvector = $sectinfo['ctvector'];
      if(($ctvector==MAN_VECTOR_PAGE) || (!empty($ctscover) && ($ctvector!=MAN_VECTOR_PAGE))){
        if($ctvector != MAN_VECTOR_PAGE){
          $newimgfile = downimg_to_local($ctscover);
          $postdata = array('Filedata'=>sprintf("@%s", $newimgfile));
          $postret = http_post_si($imguploadurl,$postdata,$headers);
          qLogInfo($logger, sprintf("createsection_for_tencent uploadcover %s", $postret));
          $lpos = strpos($postret,'{');
          $rpos = strrpos($postret, '}');
          $postret = json_decode(substr($postret, $lpos, $rpos-$lpos+1),true);
        }else{
          $postret = array('status'=>2,'cover_v_url'=>'');
        }
        if($postret['status'] == 2){//status为2是错误信息{"status":2,"msg":"系统错误或服务器繁忙！","file":"01_14_51_4b2513d0252e6340fbf6e9572557a248_1533106311214.png","cover_url":"https://manhua.qpic.cn/chp_cover/0/01_14_51_4b2513d0252e6340fbf6e9572557a248_1533106311214.png/0"}
          $ctsrow = array();
          $ctsrow['cover_v_url'] = $postret['file'];
          $submitret['code'] = 0;

          $content = json_decode($sectinfo['ctscontent'], true);
          $ctsrow['images'] = '';
          $imgdata = array();
          foreach($content as $idx=>$img)
          {
            $url = $img['imgurl'];
            $imguploadurl = 'http://ac.qq.com/MyComicExt/addPic';
            $newimgfile = downimg_to_local($url);
            $ctsimgdata = array('Filename'=>basename($newimgfile),'name'=>basename($newimgfile),'Filedata'=>sprintf("@%s", $newimgfile), 'folder'=>'/uploads', 'cid'=>$ctsid, 'seq_no'=>$idx+1, 'id'=>$sectinfo['cssourceid'], 'Upload'=>'Submit Query','token'=>$tokenkey,'fileext'=>'*.jpg;*.jpeg;*.png');
            $postret = http_post_si($imguploadurl, $ctsimgdata, $headers);
            qLogInfo($logger, sprintf("createsection_for_tencent uploadimage request %s %s", $imguploadurl, json_encode($ctsimgdata)));
            qLogInfo($logger, sprintf("createsection_for_tencent uploadimage response %s", $postret));
            $postret = json_decode($postret, true);
            //$postret['state'] = 0;
            if($postret['state'] == 0){
              break;
            }else{
              $imgdata[] = sprintf('%s,%s', $postret['pid'], $idx+1);
            }
          }

          if($postret['state'] == 0){
            $submitret['ctsid'] = '';
            $submitret['code'] = 1;
            $submitret['msg'] = $postret['msg'];
            $ret = deletesection_for_tencent($sectinfo['cssourceid'],$ctsid,$tokenkey,$headers);
            qLogInfo($logger, sprintf("deletesection_for_tencent %d %d %s", $sectinfo['cssourceid'],$ctsid, json_encode($ret)));
          }else{
            $url = 'http://ac.qq.com/MyComicExt/sortCpPic';
            $imguploaddata = array('id'=>$sectinfo['cssourceid'],'cid'=>$ctsid,'pids'=>implode('|', $imgdata), 'cross_page'=>1, 'blank_first'=>1, 'read_order'=>1);
            $postret = http_post_si($url, $imguploaddata, $headers);
            qLogInfo($logger, sprintf("createsection_for_tencent sortcppic %s", $postret));
          }
        }else{
          $submitret['code'] = 1;
          $submitret['msg'] = $postret['msg'];
        }
      }else{
        $submitret['code'] = 1;
        $submitret['msg'] = '没有上传章节封面';
      }
    }else{
      $submitret['msg'] = $postret['msg'];
    }
  }else{
    $submitret['msg'] = 'tokenkey获取失败';
  }

  return $submitret;
}/*}}}*/

function deletesection_for_tencent($ctid,$ctsid,$tokenkey,$headers){
  /*{{{*/
  $url = 'http://ac.qq.com/MyComicExt/delChapter';
  $data = array('id'=>$ctid, 'cid'=>$ctsid, 'tokenKey'=>$tokenkey);
  $postret = http_post_si($url, $data, $headers);
  return json_decode($postret,true);
}/*}}}*/

function get_cartooninfo_for_tencent_from_website($ctid)
{/*{{{*/
  $ctinfo = array();
  $url = 'http://ac.qq.com/Comic/comicInfo/id/'.$ctid;
  $cnt = http_get_si($url);

  $lpos = strpos($cnt, 'chapter-page-all');
  $rpos = strpos($cnt, "chapter-page-new", $lpos);
  $htstr = substr($cnt, $lpos, $rpos-$lpos-1);
  $str = str_replace(array("\r\n", "\r", "\n"), '', $htstr);

  $patt = '/href="(.*?)".*?>(.*?)</';
  preg_match_all($patt, $str, $ret);
  $ctsrows = array();
  foreach($ret[0] as $idx=>$val)
  {
    $patt = '/cid\/(.*?)$/';
    preg_match($patt, $ret[1][$idx], $v);
    $row = array('ctsname'=>trim($ret[2][$idx]), 'ctssourceid'=>sprintf('%s', $v[1]));
    $ctsrows[] = $row;
  }
  $ctinfo['sectionlist'] = $ctsrows;

  return $ctinfo;
}/*}}}*/

/*}}}*/

/*{{{ 爱奇艺 */

function get_loginstate_for_iqiyi($cookies)
{/*{{{*/
  $headers = get_request_headers($cookies);
  $url = 'http://mp-api.iqiyi.com/comics/api/1.0/comics?pageNo=1&pageSize=20';
  $getret = http_get_si($url, 30, $headers);
  $getret = json_decode($getret, true);
  if($getret['code'] != 0)
    return false;
  return true;
}/*}}}*/

/**
 * 多页数据抓取
 * @param $cookies
 * @return array
 */
function get_cartoonlist_for_iqiyi($cookies)
{/*{{{*/
  $headers = get_request_headers($cookies);
  $list = array();
  $totalPage = 1;
  $idx = 0;
  for ($curPage = 1;$curPage<=$totalPage;$curPage++){
    sleep(1);
    $url = 'http://mp-api.iqiyi.com/comics/api/1.0/comics?pageNo='.$curPage.'&pageSize=5';

    $getret = http_get_si($url, 30, $headers);
    //error_log("iqiyi:$getret",3,'/home/search/meizizi/error_log/'.date('Y-m-d').__FUNCTION__.'.log');//debug
    $getret  = json_decode($getret, true);
    if($curPage == 1){
      //总页数以第一次请求的为准
      if(isset($getret['data']['totalPages']))
          $totalPage = $getret['data']['totalPages'];
    }
    //error_log("当前页/总页数:$curPage/$totalPage",3,'/home/search/meizizi/error_log/'.date('Y-m-d').__FUNCTION__.'.log');//debug
    if(isset($getret['data']['data'])){
        $comicList = $getret['data']['data'];
        unset($getret);

        foreach($comicList as $info)
        {
            if($info['status'] == 2)//status(1:已上线 2:已下线)
                continue;

            $ctid = $info['id'];
            $qipuid = $info['qipuId'];
            $sectionlist = get_cartoonsectionlist_for_iqiyi($qipuid, $cookies);
            $list[$idx] = $info;
            $list[$idx]['sectionlist'] = $sectionlist;
            $idx++;
        }
    }
  }

  return $list;
}/*}}}*/

/**
 * 多页数据抓取
 * @param $cookies
 * @return array
 */
function get_cartoonlist_for_iqiyi_old2($cookies)
{/*{{{*/
  $headers = get_request_headers($cookies);
  $list = array();
  $totalPage = 1;
  for ($curPage = 1;$curPage<=$totalPage;$curPage++){
    sleep(1);
    $url = 'http://mp-api.iqiyi.com/comics/api/1.0/comics?pageNo='.$curPage.'&pageSize=5';

    $getret = http_get_si($url, 30, $headers);
      error_log("iqiyi:$getret",3,'/home/search/meizizi/error_log/'.date('Y-m-d').__FUNCTION__.'.log');//debug
    $getret  = json_decode($getret, true);
    if($curPage == 1){
      //总页数以第一次请求的为准
      if(isset($getret['data']['totalPages']))
          $totalPage = $getret['data']['totalPages'];
    }
    error_log("当前页/总页数:$curPage/$totalPage",3,'/home/search/meizizi/error_log/'.date('Y-m-d').__FUNCTION__.'.log');//debug
    if(isset($getret['data']['data'])){
        $list = $getret['data']['data'];
        unset($getret);

        foreach($list as $idx=>$info)
        {
            $ctid = $info['id'];
            $qipuid = $info['qipuId'];
            //if($info['status'] == 2)//status(1:已上线 2:已下线) TODO 暂时忽略已下线记得取消忽略
            //    continue;
            $sectionlist = get_cartoonsectionlist_for_iqiyi($qipuid, $cookies);
            $list[$idx]['sectionlist'] = $sectionlist;
        }
    }
  }

  return $list;
}/*}}}*/

function get_cartoonlist_for_iqiyi_old($cookies)
{/*{{{*/
  $headers = get_request_headers($cookies);
  $url = 'http://mp-api.iqiyi.com/comics/api/1.0/comics?pageNo=1&pageSize=20';
  $getret = http_get_si($url, 30, $headers);
  $getret  = json_decode($getret, true);
  $list = array();
  if(isset($getret['data']))
  {
    $list = $getret['data']['data'];
    foreach($list as $idx=>$info)
    {
      $ctid = $info['id'];
      $qipuid = $info['qipuId'];
      $sectionlist = get_cartoonsectionlist_for_iqiyi($qipuid, $cookies);
      $list[$idx]['sectionlist'] = $sectionlist;
    }
  }

  return $list;
}/*}}}*/

function get_cartoonsectionlist_for_iqiyi($qipuid,$cookies)
{/*{{{*/
  $headers = get_request_headers($cookies);
  $url = sprintf('http://mp-api.iqiyi.com/comics/api/1.0/chapters?order=asc&bookQipuId=%s&pageNo=1&pageSize=100&orderBy=chapterSequence', $qipuid);
  $infos = http_get_si($url, 30, $headers);
  $infos = json_decode($infos,true);
  return $infos['data']['data'];
}/*}}}*/

function get_cartoonincome_for_iqiyi($ctid,$cookies)
{/*{{{*/
    $logFile = '/home/search/meizizi/error_log/stat_'.date('Y-m-d').'.log';
  $headers = get_iqiyi_request_headers($cookies);
  //http://mp-api.iqiyi.com/income/api/1.0/comic/detail/export?agenttype=252&startDate=2017-03-01&endDate=2018-01-05&hasPage=0&bookId=221970070&type=1&pageSize=3000
  $startDate = '2017-03-01'; //时间跨度太大 考虑只取最近两个月数据
  $startDate = date('Y-m-d',strtotime('-2month'));
  $endDate = date('Y-m-d');
  $url = 'http://mp-api.iqiyi.com/income/api/1.0/comic/detail/export?agenttype=252&startDate='.$startDate.'&endDate='.$endDate.'&hasPage=0&bookId='.$ctid.'&type=1&pageSize=3000';
  $getret = http_get_si($url,30,$headers);
  $newfile = sprintf('/tmp/income_%d_%s.xls', $ctid, md5($getret));
  //error_log(date('Y-m-d H:i:s').'aiqiyi_3_xls:'.$newfile.PHP_EOL,3,$logFile);//debug
  file_put_contents($newfile,$getret);
  $stat = array();
  try{
    $objReader = PHPExcel_IOFactory::createReader('Excel5');
    $objPHPExcel = $objReader->load($newfile);
    $sheet = $objPHPExcel->getSheet(0);
    $highestRow = $sheet->getHighestRow();           //取得总行数
    $highestColumn = $sheet->getHighestColumn(); //取得总列数
    $highestColumn = PHPExcel_Cell::columnIndexFromString($highestColumn);
    for($i=$highestRow; $i>=3; --$i)
    {
      $cell3 = (string)$sheet->getCellByColumnAndRow(2, $i)->getValue();
      $cell8 = (string)$sheet->getCellByColumnAndRow(3, $i)->getValue();
      $stat[$cell8] = array('total'=>$cell3);
    }
    unlink($newfile);
  }catch(Exception $e){
    var_dump($e);
  }

  return $stat;

}/*}}}*/

function createcartoon_for_iqiyi($ctinfo,$cookies)
{/*{{{*/
  global $logger;
  global $corrtags;
  global $corrsubs;
  global $qytags;
  global $qysubs;
  //$action = '/MyComic/doCreateComic';
  $headers = get_request_headers($cookies);

  $ctrow = array();
  $ctrow['title'] = $ctinfo['ctname'];
  $ctrow['briefDescription'] = $ctinfo['ctdesc'];
  $ctrow['promptDescription'] = $ctinfo['ctnotice'];
  $ctrow['progress'] = 2;
  $ctrow['readOrder'] = 1;
  $types = array(MAN_VECTOR_PAGE=>'1', MAN_VECTOR_TIAO=>'2', MAN_VECTOR_CHA=>'3');
  $ctrow['type'] = $types[$ctinfo['ctvector']];
  $ctrow['authorizeStatus'] = 1;
  if($ctinfo['csreleasetype'] == 5)
    $ctrow['authorizeStatus'] = 2;
  $catids = array();
  $catids[] = $qytags[$corrtags[$ctinfo['cttid1']][SOURCE_AIQIYI]]; //104;
  $catids[] = $qytags[$corrtags[$ctinfo['cttid2']][SOURCE_AIQIYI]]; //105;
  foreach($ctinfo['ctsubinfos'] as $idx=>$info)
  {
    if(count($catids) < 5)
    {
      if(isset($corrsubs[$info['ctsuid']]))
      {
        if(isset($qysubs[$corrsubs[$info['ctsuid']][SOURCE_AIQIYI]]))
          $catids[] = $qysubs[$corrsubs[$info['ctsuid']][SOURCE_AIQIYI]];
      }
    }
    else
      break;
  }
  $ctrow['categoryIds'] = $catids;//array(1000227,1000228,1000185,1000186);
  $ctrow['publishCoverImageUrl'] = '';
  $ctrow['exempt'] = '';
  $ctrow['imageProduceFlag'] = true;

  $imguploadurl = 'http://upload.iqiyi.com/request_image_upload';
  if(!empty($ctinfo['ctverticalimage'])){
    $newimgfile = downimg_to_local($ctinfo['ctverticalimage']);
    $imginfo = getimagesize($newimgfile);
    $params = array('filetype'=>substr($imginfo['mime'],6), 'filesize'=>filesize($newimgfile), 'role'=>'lego_image', 'location'=>'bj', 'producttype'=>'ppc', 'businessType'=>'image');
    $getret = http_get_si($imguploadurl.'?'.http_build_query($params), 30, $headers);
    qLogInfo($logger, sprintf('createcartoon_for_iqiyi request_image_upload request %s %s', $imguploadurl, json_encode($params)));
    qLogInfo($logger, sprintf('createcartoon_for_iqiyi request_image_upload response %s', json_encode($getret)));
    $getret = json_decode($getret, true);
    $fileid = $getret['data']['file_id'];

    //{"data":{"file_path":"swift://bjyunlou7.oss.qiyi.storage:8080|bjyunlou7.oss.qiyi.storage:8080/v1/AUTH_09cfe9c2859a43a28a0ebad201fb0e8d/201712/material/video/2017/12/17/0a/e0/a9/57856b7ce89d48caaf124b0d3c7cdcb2.png","httpInnerUrl":"http://bjyunlou7.oss.qiyi.storage:8080/v1/AUTH_09cfe9c2859a43a28a0ebad201fb0e8d/201712/material/video/2017/12/17/0a/e0/a9/57856b7ce89d48caaf124b0d3c7cdcb2.png","httpOuterUrl":"http://d.pan.iqiyi.com/file/lego_image/TPyJNUi49ew0WkyxvQEiautqxEG-db7jzr8PPkD12-dHJWLaNODlVJKCSL27OZiwx0ytZaVrxzTOkTfvGImkYA"},"code":"A00000","msg":"success"}
    $imguploadurl = 'http://upload.iqiyi.com/image_upload';
    $params = array('fileid'=>$fileid, 'imagefile'=>sprintf("@%s", $newimgfile),'businessType'=>'image','range'=>'NaN-NaN');
    $postret = http_post_si($imguploadurl,$params,$headers);
    qLogInfo($logger, sprintf('createcartoon_for_iqiyi image_upload request %s %s', $imguploadurl, json_encode($params)));
    qLogInfo($logger, sprintf('createcartoon_for_iqiyi image_upload response %s', $postret));
    $postret = json_decode($postret, true);
    $ctrow['bookImageData'] = array(array('materials'=>array(array('location'=>$postret['data']['file_path'], 'url'=>$postret['data']['httpOuterUrl'], 'width'=>$imginfo[0], 'height'=>$imginfo[1])), 'images'=>array(array('materialLocation'=>$postret['data']['file_path'],'editInfo'=>array('cutPosition'=>sprintf('0,0,%s,%s',$imginfo[1],$imginfo[0] ),'scale'=>'3:4')))));

    $uinfourl = 'http://mp-api.iqiyi.com/comics/api/1.0/authors?authorName='.$ctinfo['ctauthorname'];
    $getret = http_get_si($uinfourl,30,$headers);
    qLogInfo($logger, sprintf('createcartoon_for_iqiyi image_upload request %s', $uinfourl));
    qLogInfo($logger, sprintf('createcartoon_for_iqiyi image_upload response %s', $getret));
    $getret = json_decode($getret, true);
    if(empty($getret['data']))
      $getret['data'] = array(array('authorName'=>$ctinfo['ctauthorname'],'authorQipuId'=>''));
    $ctrow['authors'] = $getret['data'];

    qLogInfo($logger, sprintf("createcartoon_for_iqiyi params %s",json_encode($ctrow)));
    $submiturl = 'http://mp-api.iqiyi.com/comics/api/1.0/comic';
    $ret = '{"code":0,"errorReason":null,"context":null,"debug_note":null,"debug_info":null,"msg":"success","data":{"qipuId":229630070,"bookId":166020,"bookTitle":"冬至与小满"}}';
    $headers[] = 'Content-Type: application/json';
    $postret = http_post_si($submiturl, decodeUnicode(json_encode($ctrow)), $headers);
    $postret = json_decode($postret, true);
  }else{
    $postret = array('code'=>1, 'msg'=>'请上传封面');
  }

  return $postret;
}/*}}}*/

function createsection_for_iqiyi($sectinfo, $cookies)
{/*{{{*/
  global $logger;
  $headers = get_request_headers($cookies);

  $ctsrow = array();
  $ctsrow['id'] = '';
  $ctsrow['bookQipuId'] = $sectinfo['cssourcebakid'];
  $ctsrow['title'] = $sectinfo['ctsname'];
  $ctsrow['chapterSequence'] = $sectinfo['ctssort'];
  $ctsrow['isDraft'] = 0;
  $ctsrow['timing'] = '';
  if(!empty($sectinfo['ctrrreleasetime']))
    $ctsrow['timing'] = date("Y-m-d H:i:s",strtotime($sectinfo['ctrrreleasetime']));
  $ctsrow['progress'] = 0;

  $ctscontent = $sectinfo['ctscontent'];
  $ctscontent = json_decode($ctscontent, true);
  $imguploadsucc = true;
  foreach($ctscontent as $idx=>$img)
  {
    $imgurl = $img['imgurl'];
    $imguploadurl = 'http://upload.iqiyi.com/request_image_upload';
    $newimgfile = downimg_to_local($imgurl);
    if(empty($newimgfile)){
      $imguploadsucc = false;
      break;
    }else{
      $imginfo = getimagesize($newimgfile);
      $params = array('filetype'=>substr($imginfo['mime'],6), 'filesize'=>filesize($newimgfile), 'role'=>'lego_image', 'location'=>'bj', 'producttype'=>'ppc', 'businessType'=>'image');
      $getret = http_get_si($imguploadurl.'?'.http_build_query($params), 30, $headers);
      qLogInfo($logger, sprintf('createsection_for_iqiyi request_image_upload request %s %s', $imguploadurl, json_encode($params)));
      qLogInfo($logger, sprintf('createsection_for_iqiyi request_image_upload response %s', json_encode($getret)));
      $getret = json_decode($getret, true);
      $fileid = $getret['data']['file_id'];

      $imguploadurl = 'http://upload.iqiyi.com/image_upload';
      $params = array('fileid'=>$fileid, 'imagefile'=>sprintf("@%s", $newimgfile),'businessType'=>'image','range'=>'NaN-NaN');
      $postret = http_post_si($imguploadurl,$params,$headers);
      qLogInfo($logger, sprintf('createsection_for_iqiyi image_upload request %s %s', $imguploadurl, json_encode($params)));
      qLogInfo($logger, sprintf('createsection_for_iqiyi image_upload response %s', json_encode($getret)));
      $postret = json_decode($postret, true);
      //$ctrow['bookImageData'] = array(array('materials'=>array(array('location'=>$postret['data']['file_path'], 'url'=>$postret['data']['httpOuterUrl'], 'width'=>$imginfo[0], 'height'=>$imginfo[1])), 'images'=>array(array('materialLocation'=>$postret['data']['file_path'],'editInfo'=>array('cutPosition'=>sprintf('0,0,%s,%s',$imginfo[1],$imginfo[0] ),'scale'=>'3:4')))));
      $ctsimg = array('sequence'=>$idx+1, 'imageName'=>substr($newimgfile,strrpos($newimgfile,'/')+1),
        'image'=>array('materials'=>array(array('location'=>$postret['data']['file_path'],'url'=>$postret['data']['httpOuterUrl'],'width'=>$imginfo[0], 'height'=>$imginfo[1]))),
        'percent'=>100,'fileId'=>$fileid,'uploading'=>false,'sortId'=>$idx
      );
      $ctsrow['chapterContentImage'][] = $ctsimg;
    }
  }
  $ctsrow['chapterDelContentImage'] = array();
  if($imguploadsucc){
    qLogInfo($logger, sprintf('createsection_for_iqiyi params %s', json_encode($ctsrow)));
    $url = 'http://mp-api.iqiyi.com/comics/api/1.0/chapter';
    $headers[] = 'Content-Type: application/json';
    $postret = http_post_si($url, decodeUnicode(json_encode($ctsrow)), $headers);
  }else{
    $postret = json_encode(array('code'=>1,'msg'=>'章节图片上传失败，请稍后重试'));
  }

  return json_decode($postret, true);
}/*}}}*/

function get_cartooninfo_for_iqiyi_from_website($ctname,$ctauthor)
{/*{{{*/
  $ctinfo = array();
  $url = 'http://www.iqiyi.com/manhua/'.http_build_query(array('search-keyword'=>$ctname));
  $cnt = http_get_si($url);

  $lpos = strpos($cnt, 'stacksList');
  if($lpos !== false)
  {
    $rpos = strpos($cnt, "mod-page", $lpos);
    $htstr = substr($cnt, $lpos, $rpos-$lpos-1);
    $str = str_replace(array("\r\n", "\r", "\n"), '', $htstr);

    $patt = '/stacksBookCover.*?href="(.*?)".*?title="(.*?)".*?>([^<>]*?)<\/em/';
    preg_match_all($patt, $str, $ret);
    foreach($ret[0] as $idx=>$val)
    {
      if(($ctname==$ret[2][$idx]) && ($ctauthor==$ret[3][$idx]))
      {
        $ctinfo['ctname'] = $ret[2][$idx];
        $ctinfo['ctauthor'] = $ret[3][$idx];
        $ctinfo['url'] = 'http://www.iqiyi.com'.$ret[1][$idx];
        break;
      }
    }
    if(!empty($ctinfo))
    {
      sleep(2);
      $cnt = http_get_si($ctinfo['url']);
      $lpos = strpos($cnt, 'chapter-container');
      $rpos = strpos($cnt, "</ol>", $lpos);
      $htstr = substr($cnt, $lpos, $rpos-$lpos-1);
      $patt = '/itemcata-title.*?>(.*?)</';
      $str = str_replace(array("\r\n", "\r", "\n"), '', $htstr);
      preg_match_all($patt, $str, $ret);
      $ctsrows = array();
      foreach($ret[0] as $idx=>$row)
      {
        $ctsrows[] = array('ctsname'=>$ret[1][$idx]);
      }
      $ctinfo['sectionlist'] = $ctsrows;
    }
  }

  return $ctinfo;
}/*}}}*/
/*}}}*/

/*{{{ base */
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

function http_get_header_si($url)
{
  $ret = '';
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  curl_setopt($ch, CURLOPT_TIMEOUT, 15);
  curl_setopt($ch, CURLOPT_NOBODY, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
  curl_setopt($ch, CURLOPT_NOSIGNAL, 1 );
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36');
  $ret = curl_exec($ch);

  $info = curl_getinfo($ch);

  curl_close($ch);
  return $info;
}

function http_post_si($url, $data, $headers=array())
{/*{{{*/
  $ch = curl_init();    
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, true);

  if(is_array($data)){
    foreach($data as $k=>$v){
      if(strpos($v,'@') === 0){
        if (class_exists('\CURLFile')) {// 这里用特性检测判断php版本
          curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
          $data[$k] = new \CURLFile(substr($v,1));//>=5.5
        } else {
          if (defined('CURLOPT_SAFE_UPLOAD')) {
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
          }
        }
      }
    }
  }

  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  //curl_setopt($ch, CURLOPT_COOKIEJAR, get_cookie_file()); 
  //curl_setopt($ch, CURLOPT_COOKIEFILE, get_cookie_file());
  //curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36');
  curl_setopt($ch, CURLINFO_HEADER_OUT, true);
  if(!empty($headers))
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  else
  {
    curl_setopt($ch, CURLOPT_COOKIEJAR, get_cookie_file()); 
    curl_setopt($ch, CURLOPT_COOKIEFILE, get_cookie_file());
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36');
  }
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
  curl_setopt($ch, CURLOPT_NOSIGNAL, 1 );
  $res = curl_exec($ch);
  if(!curl_errno($ch))
  {
    $info = curl_getinfo($ch);
    $httpHeaderSize = $info['header_size'];  //header字符串体积
    $pHeader = substr($res, 0, $httpHeaderSize); //获得header字符串
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

    $res = substr($res, $httpHeaderSize);
    if($rartype == 'gzip')
    {
      $res = gzdecode($res);
    }
  }

  curl_close($ch);
  return $res;
}/*}}}*/

function http_post_si_for_kk($url, $data)
{/*{{{*/
  $ch = curl_init();    
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, true);
  if(is_array($data)){
    foreach($data as $k=>$v){
      if(strpos($v,'@') === 0){
        if (class_exists('\CURLFile')) {// 这里用特性检测判断php版本
          curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
          $data[$k] = new \CURLFile(substr($v,1));//>=5.5
        } else {
          if (defined('CURLOPT_SAFE_UPLOAD')) {
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
          }
        }
      }
    } 
  }
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  curl_setopt($ch, CURLINFO_HEADER_OUT, true);
  curl_setopt($ch, CURLOPT_COOKIEJAR, get_cookie_file()); 
  curl_setopt($ch, CURLOPT_COOKIEFILE, get_cookie_file());
  $headers = array(
    "Connection: keep-alive",
    "Accept: application/json, text/plain, */*",
    "Content-Type: application/json;charset=UTF-8",
    "X-Requested-With: XMLHttpRequest",
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36',
    "Referer: http://www.kuaikanmanhua.com/author/admin/home/1814/chapter/new?step=1",
    "Accept-Encoding: gzip, deflate",
    "Accept-Language: zh-CN,zh;q=0.9",
  );
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
  curl_setopt($ch, CURLOPT_NOSIGNAL, 1 );
  $res = curl_exec($ch);
  if(!curl_errno($ch))
  {
    $info = curl_getinfo($ch);
    $httpHeaderSize = $info['header_size'];  //header字符串体积
    $pHeader = substr($res, 0, $httpHeaderSize); //获得header字符串
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

    $res = substr($res, $httpHeaderSize);
    if($rartype == 'gzip')
    {
      $res = gzdecode($res);
    }
  }

  curl_close($ch);
  return $res;
}/*}}}*/

function http_post_header_si($url, $data, $headers=array())
{
  $ch = curl_init();    
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  //curl_setopt($ch, CURLOPT_COOKIEJAR, get_cookie_file()); 
  //curl_setopt($ch, CURLOPT_COOKIEFILE, get_cookie_file());
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
  curl_setopt($ch, CURLOPT_NOSIGNAL, 1 );
  //curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36');
  curl_setopt($ch, CURLOPT_HEADER, TRUE);    //表示需要response header
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
  $info = curl_getinfo($ch);
  $info['headinfo'] = $ret;
  curl_close($ch);
  return $info;
}

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

function get_iqiyi_request_headers($cookies)
{/*{{{*/
  $headers = array(
    'Accept: application/json, text/javascript, */*; q=0.01',
    'Origin: http://mp.iqiyi.com',
    'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36',
    'Referer: http://mp.iqiyi.com/writings/comic/add',
    'Accept-Encoding: gzip, deflate',
    'Accept-Language: zh-CN,zh;q=0.8',
    'Cookie: '.$cookies
  );

  return $headers;
}/*}}}*/

function get_cookie_file($name='')
{
  $sessid = session_id();
  if(empty($sessid))
    $sessid = getmypid();
  if(strlen($name) > 0)
    $sessid .= sprintf('_%s', $name);
  return sprintf('/tmp/cookie_'.$sessid.'.txt');
}

function delete_cookie_file()
{
  $sessfile = get_cookie_file();
  if(file_exists($sessfile))
    unlink($sessfile);
}

function getcookie_from_cookiefile($cookiefile)
{
  $c = file_get_contents($cookiefile);
  $b = false;
  $coo = '';
  $lines = explode("\n", $c);
  foreach($lines as $line)
  {
    if($b)
    {
      if(strlen($line) > 0)
      {
        $words = explode("\t", $line);
        $coo .= sprintf("%s=%s;", $words[5],$words[6]);
      }
    }
    else
    {
      if(strlen($line) == 0)
      {
        $b = true;
      }
    }
  }
  return $coo;
}

function downimg_to_local($url)
{
  if(empty($url))
  {
    $a = debug_backtrace();
    var_dump($a);
  }
  if(strpos('-origin',$url) === false)
    $url = $url.'-origin';//加原图保护后使用origin样式访问原图
  $imgfile = substr($url, strrpos($url,'/')+1);
  $newimgfile = sprintf('/tmp/%s',$imgfile);
  //$suff = 'png';
  $imgcnt = file_get_contents($url);
  if($imgcnt){
    file_put_contents($newimgfile, $imgcnt);
    $imginfo = getimagesize($newimgfile);
    $suff = substr($imginfo['mime'],6);
    if($suff == 'jpeg')
      $suff = 'jpg';
    rename($newimgfile, $newimgfile.'.'.$suff);
    return $newimgfile.'.'.$suff;
  }else{
    return '';
  }
}

function img_frompng_to_jpg($imgpath)
{
  $newimgpath = $imgpath.'.jpg';
  $img = imagecreatefrompng($imgpath);
  imagejpeg($img,$newimgpath);
  return $newimgpath;
}

function decodeUnicode($str)
{
  return preg_replace_callback('/\\\\u([0-9a-f]{4})/i',
    create_function(
      '$matches',
      'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'
    ),
  $str);
}

/*}}}*/


?>
