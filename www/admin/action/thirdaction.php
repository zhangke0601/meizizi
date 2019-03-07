<?php
/*
 * 用途：第三方用户请求
 * 作者：feb1234@163.com
 * 时间：2018-06-02
 *
 * */
$base_dir = dirname(__FILE__).'/../../../';
require_once($base_dir.'inc/init.php');
require_once($base_dir.'inc/PHPExcel/PHPExcel.php');
require_once($base_dir.'inc/util_for_shortmsg.php');
require_once($base_dir.'inc/util_for_meizizi.php');
require_once($base_dir.'config/config.php');
require_once($base_dir.'inc/qiniu_php-sdk-7.0.7/autoload.php');
require_once($base_dir.'model/clsUserinfos.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsPlatforminfos.php');
require_once($base_dir.'model/clsThirduserinfos.php');

$ajaxret = array('code'=>400);
$logger = 'meizizi.admin.third';
//$logger = 'meizizicon.cron.thirduser';
qLogConfig($base_dir.'config/qlog.cfg');

$params = $_POST;
qLogInfo($logger, json_encode($params));

$type = GetItemFromArray($params, 'type');
$user = new Userinfos();
$cart = new Cartooninfos();
$sect = new CartoonSectioninfos();
$tu = new Thirduserinfos();

$accesstoken = GetItemFromArray($params,'accesstoken');
$version = GetItemFromArray($params,'version', '1.0.0');

if($accesstoken == 'a43395434112d0a8b804faa5f42cd897') {
  //  file_put_contents('/home/search/meizizi/third_user/'.date('Y-m-d').'.log', '请求时间：【'.date("Y-m-d,H:i:s").'】'.json_encode($params).PHP_EOL,FILE_APPEND);
}

$tuinfo = $tu->GetTuinfoByAccesstoken($accesstoken);

if($tuinfo)
{
  $tuid = $tuinfo['tuid'];

  //若开启原图保护 必须使用分隔符(-)加样式名称访问处理后的图片
  if(!empty($tuinfo['tuwaterstyle'])) {
      $waterSuffix = '-' . $tuinfo['tuwaterstyle'];
  }else {
      //(新增渠道)如果未在七牛云设置水印样式 直接报错
      $waterSuffix = water($tuinfo['tuwatertext']);
      $ajaxret['msg'] = 'error:empty waterstyle.';
      echo json_encode($ajaxret);
      exit;
  }

  unset($params['type']);
  if($type == 'getallcartoon')
  {/*{{{*/
    if($tuinfo['tuauthenddate'] >= date('Y-m-d')){
//      $tucinfos = $tu->GetCartooninfosByTuid($tuid);
      $sql = 'select a.*,c.cttname from thirduserandcartooninfos a LEFT JOIN cartooninfos b ON a.ctid=b.ctid LEFT JOIN cartoontaginfos c ON c.cttid=b.cttid1 WHERE a.tuid='.$tuid.' ORDER BY a.tuid desc';
      $tucinfos = $tu->ExecuteRead($sql);
      $ctinfos = array();
      foreach($tucinfos as $info){
        $ctinfo = $cart->find($info['ctid']);
        $uid = $ctinfo['uid'];

        //封面加水印(忽略非七牛云图片)
        if(strpos($ctinfo['ctverticalimage'],'qiniu') !== false)
//            $ctinfo['ctverticalimage'] .= $waterSuffix;
            $ctinfo['ctverticalimage'] .= '-origin';
        if(strpos($ctinfo['cthorizontalimage'],'qiniu') !== false)
//            $ctinfo['cthorizontalimage'] .= $waterSuffix;
            $ctinfo['cthorizontalimage'] .= '-origin';

        $tuuinfo = $tu->GetTuustate($tuid, $uid);
        if($tuuinfo['tuuauthenddate'] >= date('Y-m-d')){
          $uinfo = $user->find($uid);
          $ct = array('ctid'=>$ctinfo['ctid'],'cpid'=>$tuuinfo['tuucpid'],
            'ctname'=>$ctinfo['ctname'], 'ctdesc'=>$ctinfo['ctdesc'],'cttname'=>$info['cttname'],'ctvector'=>$manvectors[$ctinfo['ctvector']],
            'ctprice'=>intval($info['tucprice']),
            'ctverticalimage'=>$ctinfo['ctverticalimage'],'cthorizontalimage'=>$ctinfo['cthorizontalimage'],
            'ctcopyright'=>$uinfo['urealname'], 'ctauthorname'=>$ctinfo['ctauthorname'],
            'ctsectioncount'=>$ctinfo['ctsectioncount'], 'ctprogress'=>$ctinfo['ctprogress'],
            'ctcreatetime'=>$ctinfo['ctcreatetime'],'ctupdatetime'=>$ctinfo['ctupdatetime']);
          $ctinfos[] = $ct;
        }
      }
      $ajaxret['code'] = 200;
      $ajaxret['result'] = $ctinfos;
    }else{
      $ajaxret['msg'] = '授权过期';
    }
  }/*}}}*/
  elseif($type == 'getsectionbyctid'){
    /*{{{*/
    if($tuinfo['tuauthenddate'] >= date('Y-m-d')){
      $ctid = GetItemFromArray($params,'ctid');
      $ctinfo = $cart->find($ctid);
      if($ctinfo){
        $uid = $ctinfo['uid'];
        $tuuinfo = $tu->GetTuustate($tuid, $uid);
        if($tuuinfo){
          if($tuuinfo['tuuauthenddate'] >= date('Y-m-d')){
            $tucinfo = $tu->GetTucinfoByTuidAndCtid($tuid,$ctid);
            if($tucinfo){
              $ctsinfos = $sect->GetCartoonSectionInfosByCtid($ctid);
              $data = array();

              // 修改计费点设置后，修改判断方式
              $sql = sprintf('select a.*,b.ctssort from thirduserfeesection a LEFT JOIN cartoonsectioninfos b on a.ctsid=b.ctsid where a.type = 2 and a.ctid = %d and a.tuid = %d limit 1', $ctid, $tuid);
              $result = $cart->ExecuteRead($sql);
              if ($result) {
                  $feesection = $result[0]['ctssort'];
              } else {
                  $sql = sprintf('select a.*,b.ctssort from thirduserfeesection a LEFT JOIN cartoonsectioninfos b on a.ctsid=b.ctsid where a.type = 1 and a.ctid = %d limit 1', $ctid);
                  $result = $cart->ExecuteRead($sql);
                  if ($result) {
                      $feesection = $result[0]['ctssort'];
                  } else {
                      $feesection = 100000;
                  }
              }

              foreach($ctsinfos as $ctsinfo){
                $content = json_decode($ctsinfo['ctscontent'], true);
                if (!empty($content)) {
                  foreach ($content as &$item) {
                    //仅对有图片的链接加水印
                    $item['imgurl'] = empty($item['imgurl']) ? "" : stripslashes($item['imgurl']).$waterSuffix;
                  }
                }
                $content = json_encode($content, JSON_UNESCAPED_SLASHES);
                if($version == '2.0.0'){
                    $content = json_decode($content, true);
                }
                $row = array('ctsid'=>$ctsinfo['ctsid'],
                    'ctsname'=>$ctsinfo['ctsname'],
                  'ctscover'=> empty($ctsinfo['ctscover']) ? "" : $ctsinfo['ctscover'].$waterSuffix,//仅对有图片的链接加水印
//                    'ctsfee'=>$ctsinfo['ctsvip'],
                    'ctsfee'=>$ctsinfo['ctssort'] >= $feesection ? 5 : 0,
                  'ctssort'=>$ctsinfo['ctssort'],
                    'ctscontent'=> $content,
                  'ctscreatetime'=>$ctsinfo['ctscreatetime'],
                  'ctsupdatetime'=>$ctsinfo['ctsupdatetime']);
                if($tucinfo['tuctype'] == 5){
                  $ctsids = array();
                  if(!empty($tucinfo['tucsectionlist'])){
                    $ctsids = json_decode($tucinfo['tucsectionlist'], true);
                    if(in_array($ctsinfo['ctsid'], $ctsids)){
                      $data[] = $row;
                    }
                  }
                }else{
                  $data[] = $row;
                }
              }

              $ajaxret['code'] = 200;
              $ajaxret['result'] = $data;
            }else{
              $ajaxret['msg'] = '该作品没有授权';
            }
          }else{
            $ajaxret['msg'] = '授权过期';
          }
        }else{
          $ajaxret['msg'] = '该作品没有授权';
        }
      }else{
        $ajaxret['msg'] = '没有该作品';
      }
    }else{
      $ajaxret['msg'] = '授权过期';
    }
  }/*}}}*/
  else
  {
    $ajaxret['code'] = 404;
    $ajaxret['msg'] = '无效操作';
  }
}
else
{
  $ajaxret['msg'] = '没有授权';
}

if($accesstoken == 'a43395434112d0a8b804faa5f42cd897') {
  //  file_put_contents('/home/search/meizizi/third_user/'.date('Y-m-d').'.log', '响应结果：【'.date("Y-m-d,H:i:s").'】'.json_encode($ajaxret).PHP_EOL,FILE_APPEND);
}

echo json_encode($ajaxret);
if($tuinfo)
  $ajaxret['tuid'] = $tuid;
//qLogInfo($logger, json_encode($ajaxret));


function water($text)
{
    $text = \Qiniu\base64_urlSafeEncode($text);
    return '?watermark/2/text/'.$text.'/fontsize/1500/dissolve/15';
}
?>
