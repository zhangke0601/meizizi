<?php
/*
 * 用途：处理前端用户请求
 * 作者：feb1234@163.com
 * 时间：2017-09-09
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
$type = GetItemFromArray($params, 'type');
$user = new Userinfos();
$notice = new Noticeinfos();
$tu = new Thirduserinfos();
$cart = new Cartooninfos();
$func = new Func();
$sect = new CartoonSectioninfos();
$uinfo = $user->IsLogin();
if($uinfo !== false)
{
  $uid = $uinfo['uid'];
  if($type == 'uploadimage')
  {/*{{{*/
    $subtype = GetItemFromArray($params,'subtype');
    $url = UploadImg($_FILES['image']['tmp_name'],$subtype);
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['subtype'] = $subtype;
    $ajaxret['result'] = array('url'=>$url,'filename'=>$_FILES['image']['name']);
    $ajaxret = array_merge($ajaxret, $params);
  }/*}}}*/
  /*  图片上传 */
  elseif($type == 'uploadbase64image' )
  {/*{{{*/
    $image = GetItemFromArray($params,'image');
    $obj = GetItemFromArray($params,'obj');
    //base64图片格式 data:image/jpeg;base64,xxxxxxxxxxxxxxxxxxxxxx
    $i = stripos($image,'/');
    $j = stripos($image,';');
    $g  = substr($image,$i+1,$j-$i-1);
    if($g == 'jpeg')
      $g = 'jpg';
    $base64_body = substr(strstr($image,','),1);
    $data= base64_decode($base64_body );
    $wname = md5($data);
    //var_dump($data);
    $base64name = $wname.'.'.$g;
    $base64name = '/tmp/'.$base64name ;
    file_put_contents($base64name, $data);
    //$base64name = '/tmp/'.$base64name ;
    $ret = UploadImg($base64name,'cartoon');
    if($ret !== false)
    {
      unlink($base64name);
      $ajaxret['imageurl'] = $ret ;
      $ajaxret['obj'] = $obj ;
      $ajaxret['retno'] = RETNO_SUCC ;
    }
    else
    {
      $ajaxret['msg'] = '上传失败';
    }
    //move_uploaded_file($base64name,);
    //$images = imagecreatefromstring($data);
    /* $img = base64_decode($image['base64']);
     file_put_contents('xxx.jpg', $img);*/

  }/*}}}*/
  elseif($type == 'addnotice')
  {/*{{{*/
    unset($params['type']);
    unset($params['nid']);
    $nid = $notice->add($params);
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $nid;
  }/*}}}*/
  elseif($type == 'getnoticeinfobynid')
  {/*{{{*/
    $nid = GetItemFromArray($params,'nid');
    $ninfo = $notice->find($nid);
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $ninfo;
  }/*}}}*/
  elseif($type == 'updatenotice')
  {/*{{{*/
    unset($params['type']);
    $nid = GetItemFromArray($params,'nid');
    unset($params['nid']);
    $notice->update($nid, $params);
    $ajaxret['retno'] = RETNO_SUCC;
  }/*}}}*/
  elseif($type == 'deletenotice')
  {/*{{{*/
    $nid = GetItemFromArray($params,'nid');
    $notice->update($nid,array('nstate'=>STATE_DEL));
    $ajaxret['retno'] = RETNO_SUCC;
  }/*}}}*/
  elseif($type == 'postnotice' )
  {/*{{{*/
    unset($params['type']);
    $nid = GetItemFromArray($params,'nid');
    unset($params['nid']);
    $params['nstate'] = STATE_POSTING;
    $notice->update($nid, $params);
    {
      $ninfo = $notice->find($nid);
      $nposttype = $ninfo['nposttype'];
      $nusertype = $ninfo['nusertype'];
      $nuserlist = str_split_to_int($ninfo['nuserlist'],',');
      $uinfos = array();
      if($nusertype == 1){
        $uinfos = $user->getinfos(sprintf('ustate!=%d', STATE_DEL));
      }else{
        $uinfos = $user->getinfos(sprintf('uid in (%s)', implode(',', $nuserlist)));
      }

      foreach($uinfos as $uinfo){
        $uaninfo = array('uid'=>$uinfo['uid'], 'nid'=>$ninfo['nid']);
        $notice->AddPostNotice($uaninfo);
        if($nposttype == 1){
          sleep(1);
          $ret = ShortMsgForYinghuaAtGonggao($uinfo['umobile']);
          qLogInfo($logger, sprintf("%s %s", $uinfo['umobile'], $ret));
        }
      }

      $notice->update($ninfo['nid'], array('nstate'=>STATE_POST));
    }
    $ajaxret['retno'] = RETNO_SUCC;
  }/*}}}*/
  elseif($type == 'getaccesstoken'){
    /*{{{*/
    $name = GetItemFromArray($params,'name');
    $accesstoken = md5($name.rand().time());
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $accesstoken;
  }/*}}}*/
  elseif($type == 'addthirduser'){
    /*{{{*/
    unset($params['type']);
    $tuid = $tu->add($params);
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $tuid;
  }/*}}}*/
  elseif($type == 'getthirduserinfobytuid'){
    /*{{{*/
    $tuid = GetItemFromArray($params,'tuid');
    $tuinfo = $tu->find($tuid);
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $tuinfo;
  }/*}}}*/
  elseif($type == 'updatethirduser'){
    /*{{{*/
    $tuid = $params['tuid'];
    unset($params['tuid']);
    unset($params['type']);
    $tu->update($tuid,$params);
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $tuid;
  }/*}}}*/
  elseif($type == 'deletethirduser'){
    /*{{{*/
    $tuid = $params['tuid'];
    $tu->update($tuid,array('tustate'=>STATE_DEL));
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $tuid;
  }/*}}}*/
  elseif($type == 'addcartoonforthirduser'){
    /*{{{*/
    unset($params['type']);
    if(empty($params['tucprice']))
      $params['tucprice'] = '49';
    $tucid = $tu->add($params,'thirduserandcartooninfos');
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = array('tucid'=>$tucid,'ctid'=>GetItemFromArray($params,'ctid'));
  }/*}}}*/
  elseif($type == 'updatecartoonforthirduser'){
    /*{{{*/
    $tucid = GetItemFromArray($params,'tucid');
    unset($params['type']);unset($params['tucid']);
    $tu->UpdateTucinfoByTucid($tucid,$params);
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = true;
  }/*}}}*/
  elseif($type == 'getcartooninfosforthirduser'){
    /*{{{*/
    $tuid = GetItemFromArray($params,'tuid');
    $curuid = GetItemFromArray($params,'uid');
    if(empty($curuid))
      $curuid = $uid;
    $tucinfos = $tu->GetCartooninfosByTuid($tuid);
    foreach($tucinfos as $idx=>$info){
      $tucinfos[$idx]['ctinfo'] = $cart->find($info['ctid']);
      /** 自动同步信息 **/
      $sql = sprintf("select * from section_auto_release WHERE thirduser_id = %d AND release_method = 1 AND cartoon_id = %d limit 1", $info['tuid'], $info['ctid']);
      $autoReleaseInfo = $cart->ExecuteRead($sql);
      if(!empty($autoReleaseInfo[0])){
          $autoReleaseInfo[0]['release_weeks'] = trim($autoReleaseInfo[0]['release_weeks'], '[]');
      }
      $tucinfos[$idx]['autoinfo'] = $autoReleaseInfo[0] ?? [];
      $ctinfo = $cart->find($info['ctid']);
      if($ctinfo['uid'] != $curuid){
        unset($tucinfos[$idx]);
      }
    }
    $tucinfos = array_values($tucinfos);
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $tucinfos;
  }/*}}}*/
  elseif($type == 'deletetucinfobytucid'){
    /*{{{*/
    $tucid = GetItemFromArray($params, 'tucid');
    $tu->DeleteTucinfoByTucid($tucid);
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = true;
  }/*}}}*/
  elseif($type == 'gettucinfobytucid'){
    /*{{{*/
    $tucid = GetItemFromArray($params,'tucid');
    $tucinfo = $tu->GetTucinfoByTucid($tucid);
    $ctsinfos = array();
    if($tucinfo){
     // $ctsinfos = $sect->GetCartoonSectionInfosByCtid($tucinfo['ctid']);
      $sql = sprintf("select a.*,b.ctrrid,b.ctrrstate from cartoonsectioninfos a LEFT JOIN cartoonreleaserecordinfos b on b.ctsid = a.ctsid and b.ctrrstate in (25,30) where a.ctid=%d and a.ctsstate!=400 and a.ctsparentid=0 GROUP BY ctsid order by  a.ctssort", $tucinfo['ctid']);

      $ctsinfos = $cart->ExecuteRead($sql);
      $tucinfo['tucsectionlist'] = json_decode($tucinfo['tucsectionlist'], true);
      $tucinfo['tucsectiontimelist'] = json_decode($tucinfo['tucsectiontimelist'], true);
    }
    //查询即将自动发布的章节（如果设置过自动发布）
      $tuid = GetItemFromArray($params,'tuid');
      $sql = sprintf("SELECT tucid,a.tuid,tuname,tucsectionlist,tuauthenddate FROM `thirduserandcartooninfos` a LEFT JOIN thirduserinfos b ON a.tuid=b.tuid WHERE ctid = %d AND a.tuid=%d AND tuctype=5 AND tuauthenddate>CURDATE() AND tucstate<>400 AND tustate<>400", $tucinfo['ctid'], $tuid);

      $released = $cart->ExecuteRead($sql);
      // 可能授权过期 可能之前无手动授权章节 都不应该继续发布
      if(empty($released)){
          $tucinfo['next'] = 0;
      }else{
          $releasedSections = json_decode($released[0]['tucsectionlist'],true);
          $lastReleaseSection = (int)array_pop($releasedSections);
          $tucinfo['next'] = $lastReleaseSection;
      }


    //找出对应授权作品的计费点
      $sql = sprintf('select * from `thirduserfeesection` WHERE `tuid` = %d AND `ctid` = %d and type = 2 LIMIT 1',$tucinfo['tuid'], $tucinfo['ctid']);

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

      //查询自动同步记录
//      $sql = sprintf('select * from `section_auto_release_record` where');


    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = array('ctsinfos'=>$ctsinfos, 'tucinfo'=>$tucinfo);
  }/*}}}*/
  elseif($type == 'savethirduserauthstate'){
    /*{{{*/
    $tuid = GetItemFromArray($params, 'tuid');
    $tuustate = GetItemFromArray($params, 'tuustate');
    $tuuauthenddate = GetItemFromArray($params,'tuuauthenddate');
    $tuuid = $tu->AddTuustate($tuid, $uid, $tuustate, $tuuauthenddate);
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $tuuid;
  }/*}}}*/
  elseif($type == 'gettuusetting'){
    /*{{{*/
    $tuuid = GetItemFromArray($params,'tuuid');
    $tuuinfo = $tu->find($tuuid, 'thirduseranduserinfos', 'tuu');
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $tuuinfo;
  }/*}}}*/
  elseif($type == 'savetuusetting'){
    /*{{{*/
    $tuuid = GetItemFromArray($params,'tuuid');
    unset($params['type']);unset($params['tuuid']);
    $tu->update($tuuid, $params,'tuu', 'thirduseranduserinfos');
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = 1;
  }/*}}}*/
  elseif($type == 'getqudaodesc'){
    /*{{{*/
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $func->GetQudaodesc();
  }/*}}}*/
  elseif($type == 'savequdaodesc'){
    /*{{{*/
    $qudaodesc = GetItemFromArray($params,'qudaodesc');
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $func->SetQudaodesc($qudaodesc);
  }/*}}}*/
  elseif($type == 'savequdaodescview'){
    $user->update($uid, array('uqudaodesc'=>5));
    $ajaxret['retno'] = 0;
  }
  else
  {
    $ajaxret['retno'] = RETNO_INVALIDOPT;
    $ajaxret['msg'] = '无效操作';
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
