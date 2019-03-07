<?php
/*
 * 用途：处理前端用户请求
 * 作者：1520683535@qq.com
 * 时间：2018-11-06
 * */
$base_dir = dirname(__FILE__).'/../../../';
require_once($base_dir.'inc/init.php');
require_once($base_dir.'inc/util_for_imgupload.php');
require_once($base_dir.'model/clsUserinfos.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsNoticeinfos.php');
require_once($base_dir.'model/clsThirduserinfos.php');
require_once($base_dir.'model/clsFunc.php');

$ajaxret = array('retno'=>RETNO_FAIL);
$logger = 'meizizi.admin.func';

$params = $_POST;
$paramsGet = $_GET;
$type = GetItemFromArray($params, 'type');
if(isset($paramsGet['type']))
    $type = GetItemFromArray($paramsGet, 'type');
$user = new Userinfos();
$notice = new Noticeinfos();
$tu = new Thirduserinfos();
$cart = new Cartooninfos();
$func = new Func();
$sect = new CartoonSectioninfos();
$uinfo = $user->IsLogin();
if($uinfo !== false) {
    $uid = $uinfo['uid'];
    if ($type == 'zhuanyishuju') {
      $sql = 'select * from thirduserandcartooninfos';
      $data = $cart->ExecuteRead($sql);
      foreach ($data as $car) {
        $sectionsql = sprintf('select * from cartoonsectioninfos where ctid=%d and ctsstate!=%d and ctsparentid=0 and ctsvip=5 order by ctssort limit 1',$car['ctid'],STATE_DEL);
          $sections = $cart->ExecuteRead($sectionsql);
        $insertsql = sprintf('insert into thirduserfeesection (`ctid`,`ctsid`,`type`,`created_at`) VALUES (%d,%d,1,%d)',$car['ctid'],$sections[0]['ctsid'],time());
          $cart->ExecuteSql($insertsql);
      }
      echo 'success';die;
    }
    if ($type == 'thirduserfeesection') {
      $tuid = GetItemFromArray($params, 'tuid');
      $ctsid = GetItemFromArray($params, 'ctsid');
      $ctid = GetItemFromArray($params, 'ctid');

      $exist = sprintf('select * from `thirduserfeesection` WHERE `tuid` = %d AND `ctid` = %d LIMIT 1',$tuid, $ctid);

      if ($cart->ExecuteRead($exist)) {
        $updateSql = sprintf('UPDATE `thirduserfeesection` SET `ctsid`= %d WHERE `tuid`= %d and `ctid` = %d',$ctsid,$tuid,$ctid);
          $cart->ExecuteSql($updateSql);
      } else {

          $sql = sprintf('INSERT INTO `thirduserfeesection` (`tuid`, `ctid`, `ctsid`, `created_at`) VALUES (%d, %d, %d, %s)',$tuid, $ctid,$ctsid,  time());
          $incomes = $cart->ExecuteSql($sql);
      }

      $ajaxret['retno'] = RETNO_SUCC;
      $ajaxret['msg'] = '修改成功';
    }
    elseif ($type == 'gettucinfobytucid') {
        $tucid = GetItemFromArray($params,'tucid');
        $tucinfo = $tu->GetTucinfoByTucid($tucid);
        $sql = sprintf('select * from `thirduserfeesection` WHERE `tuid` = %d AND `ctid` = %d LIMIT 1',$tucinfo['tuid'], $tucinfo['ctid']);

        $exist = $cart->ExecuteRead($sql);
        if ($exist) {
          $tucinfo['ctsid'] = $exist[0]['ctsid'];
        } else {
            $sql = sprintf('select * from `thirduserfeesection` WHERE `type` = 1 AND `ctid` = %d LIMIT 1', $tucinfo['ctid']);
            $exist = $cart->ExecuteRead($sql);
            if ($exist) {
                $tucinfo['ctsid'] = $exist[0]['ctsid'];
            } else {
                $tucinfo['ctsid'] = '';
            }
        }

        $ctsinfos = array();
        if($tucinfo){
            $ctsinfos = $sect->GetCartoonSectionInfosByCtid($tucinfo['ctid']);
            $tucinfo['tucsectionlist'] = json_decode($tucinfo['tucsectionlist'], true);
        }
        $ajaxret['retno'] = RETNO_SUCC;
        $ajaxret['result'] = array('ctsinfos'=>$ctsinfos, 'tucinfo'=>$tucinfo);
    }
    elseif ($type == 'downimg') {
        $ctsid = intval(GetItemFromArray($paramsGet,'ctsid'));
        $pfid = intval(GetItemFromArray($paramsGet,'pfid'));

        if(!empty($pfid)){
            $ctsinfo = $sect->GetSectInfoByCtsidAndPfid($ctsid, $pfid);
            if(empty($ctsinfo)){
                echo '无'.$ctsinfo['ctsname'] .= '-'.$sources[$pfid].'数据';
                exit;
            }
        }else{
            $ctsinfo = $cart->find($ctsid,'cartoonsectioninfos','cts');
        }

        $imgurlinfos = array();
        {
            if(!empty($pfid))
                $ctsinfo['ctsname'] .= '-'.$sources[$pfid];
            $ctsname = iconv("utf-8","gbk",str_replace(['\\','/','<','>',':','*','"','|','?'],'',$ctsinfo['ctsname']));

            $imgurl = json_decode($ctsinfo['ctscontent'], true);
//            var_dump($imgurl);die;
            $tmppath = sprintf('/tmp/%s/', time());
            exec(sprintf('rm -r %s >/dev/null 2>&1', $tmppath),$ret);
            mkdir($tmppath);
            $zipfile = '/tmp/'.$ctsname.'.zip';
            $zip = new ZipArchive();
            $zip->open($zipfile,ZipArchive::CREATE);
//            var_dump($imgurl);die;
            foreach($imgurl as $idx=>$value)
            {
                $filename = '';
                if(isset($value['filename'])){
                    $pathinfo = pathinfo($value['imgurl']);
                    if(empty($pathinfo['extension']))
                        $pathinfo['extension'] = 'jpg';
                    $filename = $tmppath.sprintf('%d.%s', $idx, $pathinfo['extension']);
                }
//                    $filename = $tmppath.$value['filename'];

                else{
                    $pathinfo = pathinfo($value['imgurl']);
                    if(empty($pathinfo['extension']))
                        $pathinfo['extension'] = 'jpg';
                    $filename = $tmppath.sprintf('%d.%s', $idx, $pathinfo['extension']);
                }
                if($uid == 33){
                    file_put_contents($filename, file_get_contents($value['imgurl']));
                }else{
                    file_put_contents($filename, file_get_contents($value['imgurl'].'-origin'));
                }
                $zip->addFile($filename,basename($filename));
            }
            $zip->close();
            header('Content-Disposition:attachment;filename='.$ctsname.'.zip');
            echo file_get_contents($zipfile);
            unlink($zipfile);
            exec(sprintf('rm -r %s >/dev/null 2>&1', $tmppath),$ret);
        }
    }
}
else
{
  $ajaxret['retno'] = RETNO_NOTLOGIN;
  $ajaxret['msg'] = '登陆超时';
}

echo json_encode($ajaxret);
if($uinfo !== false)
  $ajaxret['uid'] = $uid;
qLogInfo($logger, json_encode($ajaxret));
?>
