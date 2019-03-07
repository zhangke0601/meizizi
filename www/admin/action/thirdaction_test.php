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
require_once($base_dir.'model/clsUserinfos.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsPlatforminfos.php');
require_once($base_dir.'model/clsThirduserinfos.php');

$ajaxret = array('code'=>400);
$logger = 'meizizi.admin.third';
qLogConfig($base_dir.'config/qlog.cfg');

$params = $_POST;
qLogInfo($logger, json_encode($params));
$type = GetItemFromArray($params, 'type');
$user = new Userinfos();
$cart = new Cartooninfos();
$sect = new CartoonSectioninfos();
$tu = new Thirduserinfos();

$accesstoken = GetItemFromArray($params,'accesstoken');
$tuinfo = $tu->GetTuinfoByAccesstoken($accesstoken);

if($tuinfo)
{
  $tuid = $tuinfo['tuid'];
  unset($params['type']);
  if($type == 'getallcartoon')
  {/*{{{*/
    if($tuinfo['tuauthenddate'] >= date('Y-m-d')){
      $tucinfos = $tu->GetCartooninfosByTuid($tuid);
      $ctinfos = array();
      foreach($tucinfos as $info){
        $ctinfo = $cart->find($info['ctid']);
        $uid = $ctinfo['uid'];
        $tuuinfo = $tu->GetTuustate($tuid, $uid);
        if($tuuinfo['tuuauthenddate'] >= date('Y-m-d')){
          $uinfo = $user->find($uid);
          $ct = array('ctid'=>$ctinfo['ctid'],'cpid'=>$tuuinfo['tuucpid'],
            'ctname'=>$ctinfo['ctname'], 'ctdesc'=>$ctinfo['ctdesc'],
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
              foreach($ctsinfos as $ctsinfo){
                $row = array('ctsid'=>$ctsinfo['ctsid'], 'ctsname'=>$ctsinfo['ctsname'],
                  'ctscover'=>$ctsinfo['ctscover'], 'ctsfee'=>$ctsinfo['ctsvip'],
                  'ctssort'=>$ctsinfo['ctssort'], 'ctscontent'=> $ctsinfo['ctscontent'],
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

echo json_encode($ajaxret);
if($tuinfo)
  $ajaxret['tuid'] = $tuid;
qLogInfo($logger, json_encode($ajaxret));
?>
