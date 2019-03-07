<?php
/*
 * 用途：处理前端用户请求
 * 作者：feb1234@163.com
 * 时间：2017-09-09
 *
 * TODO：新增或修改漫画信息时，判断横版和竖版封面个数
 * */
$base_dir = dirname(__FILE__).'/../../../';
require_once($base_dir.'inc/init.php');
require_once($base_dir.'inc/PHPExcel/PHPExcel.php');
require_once($base_dir.'inc/util_for_shortmsg.php');
require_once($base_dir.'inc/util_for_excel.php');
require_once($base_dir.'inc/util_for_meizizi.php');
require_once($base_dir.'config/config.php');
require_once($base_dir.'model/clsUserinfos.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsPlatforminfos.php');
require_once($base_dir.'model/clsThirduserinfos.php');

$ajaxret = array('retno'=>RETNO_FAIL);
$logger = 'meizizi.admin.cartoon';
qLogConfig($base_dir.'config/qlog.cfg');

$params = $_POST;
qLogInfo($logger, json_encode($params));
$type = GetItemFromArray($params, 'type');
$user = new Userinfos();
$cart = new Cartooninfos();
$sect = new CartoonSectioninfos();
$pf = new Platforminfos();
$tu = new Thirduserinfos();
$uinfo = $user->IsLogin();
if($uinfo !== false)
{
  $uid = $uinfo['uid'];
  /*{{{ 过滤封面 */
  $platformverticalsizeinfos = array(
    array('pfid'=>1,'name'=>'掌阅','size'=>'600*800','width'=>'600','heigth'=>'800'),
    //array('pfid'=>2,'name'=>'快看','size'=>'430*570','width'=>'430','heigth'=>'570'),
    array('pfid'=>3,'name'=>'漫画岛','size'=>'300*400','width'=>'300','heigth'=>'400'),
    //array('pfid'=>4,'name'=>'布卡','size'=>'430*570','width'=>'','heigth'=>''),
    array('pfid'=>5,'name'=>'腾讯','size'=>'630*840','width'=>'630','heigth'=>'840'),
    array('pfid'=>6,'name'=>'爱奇艺','size'=>'300*400','width'=>'300','heigth'=>'400'),
    array('pfid'=>7,'name'=>'网易','size'=>'300*420','width'=>'300','heigth'=>'420'),
    array('pfid'=>8,'name'=>'有妖气','size'=>'635*835','width'=>'635','heigth'=>'835'),
    array('pfid'=>11,'name'=>'微博','size'=>'210*285','width'=>'210','heigth'=>'285'),
  );
  $platformhorizontalsizeinfos = array(
    //array('pfid'=>2,'name'=>'快看','size'=>'1280*800','width'=>'1280','heigth'=>'800'),
    array('pfid'=>5,'name'=>'腾讯','size'=>'1500*880','width'=>'1500','heigth'=>'880'),
    array('pfid'=>7,'name'=>'网易','size'=>'640*360','width'=>'640','heigth'=>'360'),
    array('pfid'=>8,'name'=>'有妖气','size'=>'1235*775','width'=>'1235','heigth'=>'775'),
    array('pfid'=>11,'name'=>'微博','size'=>'750*424','width'=>'750','heigth'=>'424'),
  );
  $pfinfos = $pf->getinfos('pfstate=0');
  $upfinfos = array();
  foreach($pfinfos as $idx=>$pfinfo)
  {
    $upfinfo = $pf->existUserAndPlatform($uid,$pfinfo['pfid']);
    if($upfinfo)
    {
      $upfinfo['pfinfo'] = $pfinfo;
      $upfinfos[] = $upfinfo;
    }
  }
  $upfinfos = SetKeyFromArray($upfinfos,'pfid');

  foreach($platformverticalsizeinfos as $idx=>$row)
  {
    if(!isset($upfinfos[$row['pfid']]))
    {
      unset($platformverticalsizeinfos[$idx]);
    }
  }
  $platformverticalsizeinfos = array_values($platformverticalsizeinfos);
  foreach($platformhorizontalsizeinfos as $idx=>$row)
  {
    if(!isset($upfinfos[$row['pfid']]))
    {
      unset($platformhorizontalsizeinfos[$idx]);
    }
  }
  $platformhorizontalsizeinfos = array_values($platformhorizontalsizeinfos);
  /*}}}*/

  $utype = $uinfo['utype'];
  unset($params['type']);
  if($type == 'addcartooninfo')
  {/*{{{*/
    //$cttype1 = GetItemFromArray($params,'cttype1');
    //$cttype2 = GetItemFromArray($params,'cttype2');
    $cttypes = GetItemFromArray($params,'cttypes');
    $cttypes = json_decode($cttypes, true);
    $ctsubs  = GetItemFromArray($params,'ctsubjects');
    $ctsubs  = json_decode($ctsubs, true);
    $ctfirstrelease = GetItemFromArray($params,'ctfirstrelease');
    $platformverticalinfos = GetItemFromArray($params,'platformverticalinfos');
    $platformhorizontalinfos= GetItemFromArray($params,'platformhorizontalinfos');
    $platformvertical_infos = json_decode($platformverticalinfos,true);
    $platformhorizontal_infos = json_decode($platformhorizontalinfos,true);
    unset($params['platformverticalinfos']);
    unset($params['platformhorizontalinfos']);
    unset($params['cttype1']);unset($params['cttype2']);
    unset($params['cttypes']);
    unset($params['ctsubjects']);
    unset($params['ctid']);
    unset($params['samectsid']);
    $params['uid'] = $uid;
    if($utype == USERTYPE_AUTH)
      $params['cttype'] = TYPE_SOURCE_MANAGER;
    elseif($utype == USERTYPE_MANHUASHI)
      $params['cttype'] = TYPE_SOURCE_USER;
    elseif($utype == USERTYPE_STUDIO)
      $params['cttype'] = TYPE_SOURCE_USER;
    $params['ctprices'] = get_prices_from_params($params);
    if((compare_coverinfos($platformverticalsizeinfos,$platformvertical_infos)) && (compare_coverinfos($platformhorizontalsizeinfos,$platformhorizontal_infos)))
    {
      $ctid = $cart->add($params);
      if($ctid > 0)
      {
        foreach($cttypes as $cttpid)
          $cart->SetRelation('ctid',$ctid,'cttpid',$cttpid,'cartoonandtypeinfos','catp');
        foreach($ctsubs as $ctsuid)
          $cart->SetRelation('ctid',$ctid,'ctsuid',$ctsuid,'cartoonandsubjectinfos','cas');
        $pfimginfos = array();
        foreach($platformvertical_infos as $idx=>$platformvertical_info)
        {
          $pfimginfos[$idx]['verticalimg'] = $platformvertical_info['verticalimg'];
        }
        foreach($platformhorizontal_infos as $idx=>$platformhorizontal_info)
        {
          $pfimginfos[$idx]['horizontalimg'] = $platformhorizontal_info['horizontalimg'];
        }
        foreach($pfimginfos as $idx=>$pfimginfo)
        {
          $horizontalimg = $verticalimg = '';
          if(isset($pfimginfo['horizontalimg']))
            $horizontalimg = $pfimginfo['horizontalimg'];
          if(isset($pfimginfo['verticalimg']))
            $verticalimg = $pfimginfo['verticalimg'];
          $ccpinfo = array('ctid'=>$ctid,'pfid'=>$idx,'ccpverticalimg'=>$verticalimg,'ccphorizontalimg'=>$horizontalimg);
          if(strlen($horizontalimg) || strlen($verticalimg))
            $cart->Add($ccpinfo,'cartooncoverandplatforminfos');
        }
        $ajaxret['retno'] = RETNO_SUCC;
        $ajaxret['result'] = $ctid;
      }
      else
      {
        $ajaxret['msg'] = '提交失败';
      }
    }
    else
    {
      $ajaxret['msg'] = '请确认封面是否上传';
    }

  }/*}}}*/
  elseif($type == 'updatecartooninfo')
  {/*{{{*/
    //$cttype1 = GetItemFromArray($params,'cttype1');
    //$cttype2 = GetItemFromArray($params,'cttype2');
    $cttypes = GetItemFromArray($params,'cttypes');
    $cttypes = json_decode($cttypes, true);
    $ctsubs = GetItemFromArray($params,'ctsubjects');
    $ctsubs = json_decode($ctsubs, true);
    $ctfirstrelease = GetItemFromArray($params,'ctfirstrelease');
    $platformverticalinfos = GetItemFromArray($params,'platformverticalinfos');
    $platformhorizontalinfos= GetItemFromArray($params,'platformhorizontalinfos');
    $platformvertical_infos = json_decode($platformverticalinfos,true);
    $platformhorizontal_infos = json_decode($platformhorizontalinfos,true);
    unset($params['platformverticalinfos']);
    unset($params['platformhorizontalinfos']);
    unset($params['cttype1']);unset($params['cttype2']);
    unset($params['cttypes']);
    unset($params['ctsubjects']);
    $ctid = $params['ctid'];
    unset($params['ctid']);

    $samectsid = $params['samectsid'];
    unset($params['samectsid']);

    $params['ctupdatetime'] = date('Y-m-d H:i:s');

    $params['ctprices'] = get_prices_from_params($params);
    //if((count($platformvertical_infos)>=0) && (count($platformhorizontal_infos)>=0))
    if((compare_coverinfos($platformverticalsizeinfos,$platformvertical_infos)) && (compare_coverinfos($platformhorizontalsizeinfos,$platformhorizontal_infos)))
    {
      $succ = $cart->update($ctid,$params);
      $cart->SetRelationStateByLkey('ctid',$ctid,'cat',STATE_DEL,'cartoonandtaginfos');
      foreach($cttypes as $cttpid)
        $cart->SetRelation('ctid',$ctid,'cttpid',$cttpid,'cartoonandtypeinfos','catp');
      $cart->SetRelationStateByLkey('ctid',$ctid,'cas',STATE_DEL,'cartoonandsubjectinfos');
      foreach($ctsubs as $ctsuid)
        $cart->SetRelation('ctid',$ctid,'ctsuid',$ctsuid,'cartoonandsubjectinfos','cas');
      if($succ)
      {
        if(!empty($samectsid)){
            $exist = sprintf('select * from `thirduserfeesection` WHERE `type` = 1 AND `ctid` = %d LIMIT 1',$ctid);

            if ($cart->ExecuteRead($exist)) {
                $updateSql = sprintf('UPDATE `thirduserfeesection` SET `ctsid`= %d WHERE `type`= 1 and `ctid` = %d',$samectsid,$ctid);
                $cart->ExecuteSql($updateSql);
            } else {
                $sql = sprintf('INSERT INTO `thirduserfeesection` ( `ctid`, `ctsid`, `created_at`,`type`) VALUES (%d, %d, %s,1)', $ctid,$samectsid,  time());
                $incomes = $cart->ExecuteSql($sql);
            }


        }

        $cart->update($ctid,array('ccpstate'=>STATE_DEL),'ct','cartooncoverandplatforminfos');
        $pfimginfos = array();
        foreach($platformvertical_infos as $idx=>$platformvertical_info)
        {
          $pfimginfos[$idx]['verticalimg'] = $platformvertical_info['verticalimg'];
        }
        foreach($platformhorizontal_infos as $idx=>$platformhorizontal_info)
        {
          $pfimginfos[$idx]['horizontalimg'] = $platformhorizontal_info['horizontalimg'];
        }
        foreach($pfimginfos as $idx=>$pfimginfo)
        {
          $horizontalimg = $verticalimg = '';
          if(isset($pfimginfo['horizontalimg']))
            $horizontalimg = $pfimginfo['horizontalimg'];
          if(isset($pfimginfo['verticalimg']))
            $verticalimg = $pfimginfo['verticalimg'];
          $ccpinfo = array('ctid'=>$ctid,'pfid'=>$idx,'ccpverticalimg'=>$verticalimg,'ccphorizontalimg'=>$horizontalimg);
          if(strlen($horizontalimg) || strlen($verticalimg))
            $cart->Add($ccpinfo,'cartooncoverandplatforminfos');
        }
        $ajaxret['retno'] = RETNO_SUCC;
        $ajaxret['result'] = $succ;
      }
      else
      {
        $ajaxret['msg'] = '更新失败';
      }
    }
    else
      $ajaxret['msg'] = '请上传平台图片信息';
  }/*}}}*/
  elseif($type == 'deletecartoon')
  {/*{{{*/
    $ctid = GetItemFromArray($params,'ctid');
    $cart->update($ctid,array('ctstate'=>STATE_DEL));
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = true;
  }/*}}}*/
  elseif($type == 'getcartooninfosbykey'){
    /*{{{*/
    $key = GetItemFromArray($params,'key');
    $tuid = GetItemFromArray($params,'tuid');
    $key = trim($key);
    $cartinfos = array();
    $tucinfos = $tu->GetCartooninfosByTuid($tuid);
    if(true){
      $tucinfos = SetKeyFromArray($tucinfos,'ctid');
      $cartinfos = $cart->GetInfosByKey($key, $uid);
      foreach($cartinfos as $idx=>$info){

        $ctsinfos = $sect->GetCartoonSectionInfosByCtid($info['ctid']);
        $ctsvip = 0;
        foreach($ctsinfos as $ctsinfo){
          if($ctsinfo['ctsvip'] != 0){
            $ctsvip = $ctsinfo['ctsvip'];
            break;
          }
        }
        $cartinfos[$idx]['ctsvip'] = $ctsvip;

        if(isset($tucinfos[$info['ctid']])){
          unset($cartinfos[$idx]);
        }
        if($info['uid'] != $uid)
          unset($cartinfos[$idx]);
      }
      $cartinfos = array_values($cartinfos);
    }
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $cartinfos;
  }/*}}}*/
  elseif($type == 'addsectionforcartoon')
  {/*{{{*/
    // 添加或修改自动发布任务
    //saveAutoReleaseRec($sect,$params);
    unset($params['ctsid']);
    $specversionstr = GetItemFromArray($params,'specversions');
    unset($params['specversions']);
    $ctsid = $sect->add($params);
    if($ctsid > 0)
    {
      if(!empty($specversionstr)){
        $specversions = json_decode($specversionstr, true);
        foreach($specversions as $pfid=>$row){
          $ctsinfo = $params;
          $ctsinfo['ctscontent'] = json_encode(cutimage_to_ctscontent($row));
          $ctsinfo['ctssource'] = $pfid;
          $ctsinfo['ctssourceid'] = '';
          $ctsinfo['ctsstate'] = 0;
          $ctsinfo['ctsparentid'] = $ctsid;
          $nctsid = $sect->add($ctsinfo);
        }
      }
      $ajaxret['retno'] = RETNO_SUCC;
      $ajaxret['result'] = $ctsid;
    }
    else
    {
      $ajaxret['msg'] = '保存失败';
    }
  }/*}}}*/
  elseif($type == 'updatesectionforcartoon')
  {/*{{{*/
    // 添加或修改自动发布任务
    //saveAutoReleaseRec($sect,$params);
    unset($params['ctid']);
    $specversionstr = GetItemFromArray($params, 'specversions');
    unset($params['specversions']);
    $ctsid = GetItemFromArray($params,'ctsid');

      $params['ctsupdatetime'] = date('Y-m-d H:i:s');
    $succ = $sect->update($ctsid, $params);
    if($succ)
    {
      if(!empty($specversionstr)){
        $specversions = json_decode($specversionstr, true);
        foreach($specversions as $pfid=>$row){
          $nctsinfo = $sect->GetSectInfoByCtsidAndPfid($ctsid, $pfid);
          unset($params['ctsid']);
          unset($params['ctscreatetime']);
          unset($params['ctsupdatetime']);
          if(empty($nctsinfo)){
            $nctsinfo = $params;
            $nctsinfo['ctscontent'] = json_encode(cutimage_to_ctscontent($row));
            $nctsinfo['ctssource'] = $pfid;
            $nctsinfo['ctssourceid'] = '';
            $nctsinfo['ctsstate'] = 0;
            $nctsinfo['ctsparentid'] = $ctsid;
            $sect->add($nctsinfo);
          }else{
            $ctscontentstr = $nctsinfo['ctscontent'];
            if(empty($ctscontentstr))
              $ctscontent = array();
            else
              $ctscontent = json_decode($ctscontentstr, true);
            $ninfo['ctscontent'] = json_encode(cutimage_to_ctscontent($row,$ctscontent));
            $sect->update($nctsinfo['ctsid'], $ninfo);
            //$succ = $sect->update($ctsid, $params);
          }
        }
      }
      $ajaxret['retno'] = RETNO_SUCC;
      $ajaxret['result'] = $succ;
    }
    else
    {
      $ajaxret['msg'] = '保存失败';
    }
  }/*}}}*/
  elseif($type == 'deletesectioninfo')
  {/*{{{*/
    $ctsid = GetItemFromArray($params,'ctsid');
    $succ = $sect->update($ctsid, array('ctsstate'=>STATE_DEL));
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $succ;
  }/*}}}*/
  elseif($type == 'getsectioninfosbyctid'){
    /*{{{*/
    $ctid = GetItemFromArray($params,'ctid');
    $tuid = GetItemFromArray($params,'tuid');
    $ctinfo = $cart->find($ctid);
    $ctsinfos = $sect->GetCartoonSectionInfosByCtid($ctid);
    $tucinfo = (object)array();
    if($tuid){
      $tucinfo = $tu->GetCartooninfoByTuidAndCtid($tuid, $ctid);
      if($tucinfo)
        $tucinfo['tucsectionlist'] = json_decode($tucinfo['tucsectionlist'], true);
    }
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = array('ctinfo'=>$ctinfo, 'ctsinfos'=>$ctsinfos, 'tucinfo'=>$tucinfo);
  }/*}}}*/
  elseif($type == 'getsectioncopyinfos'){
    /*{{{*/
    $ctid = GetItemFromArray($params,'ctid');
    $ctsid = GetItemFromArray($params,'ctsid');
    $cpinfos = $cart->GetSectionCopyInfosByCtsid($ctsid);
    foreach($cpinfos as $idx=>$info){
      $cpinfos[$idx]['ctssourcename'] = $sources[$info['ctssource']];
    }
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = array('ctid'=>$ctid,'ctsid'=>$ctsid,'copyinfos'=>$cpinfos);
  }/*}}}*/
  elseif($type == 'unreleasesectioninfo')
  {/*{{{*/
    $ctid = GetItemFromArray($params,'ctid');
    $sectinfos = $sect->getinfos(sprintf('ctid=%d and ctsstate!=%d and ctsparentid=0', $ctid, STATE_DEL),'','ctssort');
    foreach($sectinfos as $idx=>$info)
    {
      $rinfos = $cart->GetReleaseRecordInfosByCtsid($info['ctsid']);
      $sectinfos[$idx]['rinfos'] = $rinfos;
      $desc = '';
      foreach($rinfos as $rinfo)
      {
        $desc .= sprintf('%s(%s)', $sources[$rinfo['pfid']], $rinfo['ctrrstate']==STATE_AUTHSUCC?'已成功':'在处理');
      }
      if(empty($desc))
        $desc = '(暂无发布)';
      $sectinfos[$idx]['desc'] = $desc;
    }
    $csinfos = $cart->GetCartoonReleaseInfosByCtid($ctid);
    $csreleasetype = 0;
    if((count($csinfos)==1) && ($csinfos[0]['csreleasetype']==5))
    {
      $pfid = $csinfos[0]['cssource'];
      $upfinfo = $pf->getoneinfo(sprintf('uid=%d and pfid=%d', $uid, $pfid), 'userandplatforminfos');
      $csreleasetype = $upfinfo['upfid'];
    }
    $result['csreleasetype'] = $csreleasetype;
    $result['sectinfos'] = $sectinfos;
    $result['csinfos'] = $csinfos;
    $result['after24time'] = date('Y-m-d H:i:s',time()+24*3600+30);

    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $result;
  }/*}}}*/
  elseif($type == 'setreleasetimeforcartoon')
  {/*{{{*/
  }/*}}}*/
  elseif($type == 'submitrelease')
  {/*{{{*/
    $ctid = GetItemFromArray($params,'ctid');
    $ctsid = GetItemFromArray($params,'ctsid');
    $upfidstr = GetItemFromArray($params,'upfids');
    $sectionids = GetItemFromArray($params,'sectionids');
    $rinfos = $cart->GetReleaseRecordInfosByCtsid($ctsid);
    if(empty($rinfos))
    {
      $csreleasetype = GetItemFromArray($params,'csreleasetype');
      $ctrrreleasetime = GetItemFromArray($params,'ctrrreleasetime');
      $csprevtype = GetItemFromArray($params,'csprevtype', 0);
      $csprevvalue = GetItemFromArray($params,'csprevvalue','');
      $firstids = GetItemFromArray($params,'firstids');
      $ids = explode(',', $upfidstr);
      $firstids = str_split_to_int($firstids,',');//explode(',', $firstids);
      $upfids = str_split_to_int($upfidstr,',');
      $ctsids = str_split_to_int($sectionids, ',');
      $ctrinfo = array('uid'=>$uid,'ctid'=>$ctid,'ctsid'=>$ctsid);
      $ctrid = $cart->add($ctrinfo, 'cartoonreleaseinfos');
      if($csreleasetype > 0)
      {
        $upfid = $csreleasetype;
        $upfinfo = $pf->find($upfid,'userandplatforminfos','upf');
        $csinfo = $cart->GetCartoonReleaseInfo($ctid, $upfinfo['pfid']);
        if(empty($csinfo))
        {
          $csinfo = array('ctid'=>$ctid,'cssource'=>$upfinfo['pfid'],'csreleasetime'=>$csreleasetime, 'csreleasetype'=>5);
          $csid = $cart->add($csinfo, 'cartoonsourceinfos');
        }
        foreach($ctsids as $ctsid){
          $ctrrinfos = $cart->getinfos(sprintf('pfid=%d and ctsid=%d', $upfinfo['pfid'], $ctsid), 'cartoonreleaserecordinfos');
          if(empty($ctrrinfos)){
            $ctrrinfo = array('ctrid'=>$ctrid,'ctid'=>$ctid, 'ctsid'=>$ctsid, 'pfid'=>$upfinfo['pfid'],'upfid'=>$upfinfo['upfid'], 'upftype'=>$upfinfo['upftype'], 'ctrrpfsectionid'=>'', 'ctrrtype'=>RELEASE_TYPE_USER, 'ctrrstate'=>0,'ctrrreleasetime'=>$ctrrreleasetime);
            $cart->add($ctrrinfo, 'cartoonreleaserecordinfos');
          }
        }
      }
      else
      {
        foreach($upfids as $upfid)
        {
          $upfinfo = $pf->find($upfid,'userandplatforminfos','upf');
          $csinfo = $cart->GetCartoonReleaseInfo($ctid, $upfinfo['pfid']);
          if(empty($csinfo))
          {
            $csfirstrelease = 0;
            if(in_array($upfid, $firstids))
              $csfirstrelease = 5;
            $csinfo = array('ctid'=>$ctid,'cssource'=>$upfinfo['pfid'],'csreleasetime'=>$csreleasetime, 'csreleasetype'=>0,'csfirstrelease'=>$csfirstrelease, 'csprevtype'=>$csprevtype,'csprevvalue'=>$csprevvalue);
            $csid = $cart->add($csinfo, 'cartoonsourceinfos');
          }

          foreach($ctsids as $ctsid)
          {
            $ctrrinfos = $cart->getinfos(sprintf('pfid=%d and ctsid=%d', $upfinfo['pfid'], $ctsid), 'cartoonreleaserecordinfos');
            if(empty($ctrrinfos))
            {
              $ctrrinfo = array('ctrid'=>$ctrid,'ctid'=>$ctid, 'ctsid'=>$ctsid, 'pfid'=>$upfinfo['pfid'],'upfid'=>$upfinfo['upfid'], 'upftype'=>$upfinfo['upftype'], 'ctrrpfsectionid'=>'', 'ctrrtype'=>RELEASE_TYPE_USER, 'ctrrstate'=>0,'ctrrreleasetime'=>$ctrrreleasetime);
              $cart->add($ctrrinfo, 'cartoonreleaserecordinfos');
            }
          }
        }
      }
      $ajaxret['retno'] = RETNO_SUCC;
      $ajaxret['msg'] = '提交成功';
    }
    else
    {
      $ajaxret['msg'] = '已发布';
    }
  }/*}}}*/
  elseif($type == 'startreleaseforcartoon')
  {/*{{{*/
  }/*}}}*/
  elseif($type == 'getreleaseinfosbyctidandstate')
  {/*{{{*/
    $ctid = GetItemFromArray($params,'ctid');
    $state = GetItemFromArray($params,'state');
    $ctrrinfos = $cart->GetReleaseInfosByCtidAndState($ctid, $state);
    $pfinfos = $pf->getinfos();
    $pfinfos = SetKeyFromArray($pfinfos,'pfid');
    foreach($ctrrinfos as $idx=>$info)
    {
      $ctrrinfos[$idx]['ctsinfo'] = $sect->find($info['ctsid']);
      $ctrrinfos[$idx]['pfname'] = $pfinfos[$info['pfid']]['pfname'];
      $ctrrinfos[$idx]['ctrrstatename'] = $releasestates[$info['ctrrstate']];
      if($info['pfid'] == SOURCE_KUAIKAN)
      {
        $reason = $info['ctrrreason'];
        $reasondata = json_decode($reason, true);
        if(is_null($reasondata)){
        }else{
          $reason = $reasondata['comment'].'<br>';
          foreach($reasondata['tags'] as $tag)
          {
            $reason .= '<img src="'.$tag['image'].'"/>';
            $reason .= $tag['tags'][0]['comment'];
          }
          $ctrrinfos[$idx]['ctrrreason'] = $reason;
        }
      }
    }
    $sinfos = $cart->GetSourceInfosByCtid($ctid);
    foreach($sinfos as $sinfo)
    {
      if(($state==$sinfo['csstate']) && ($sinfo['csstate'] == STATE_UPLOADFAIL))
      {
        $sinfo['ctinfo'] = $cart->find($ctid);
        $sinfo['pfname'] = $pfinfos[$sinfo['cssource']]['pfname'];
        $sinfo['csstatename'] = $releasestates[$sinfo['csstate']];
        $ctrrinfos[] = $sinfo;
      }
      if(($state==$sinfo['csstate']) && ($sinfo['csstate'] == STATE_AUTHFAIL))
      {
        $sinfo['ctinfo'] = $cart->find($ctid);
        $sinfo['pfname'] = $pfinfos[$sinfo['cssource']]['pfname'];
        $sinfo['csstatename'] = $releasestates[$sinfo['csstate']];
        $ctrrinfos[] = $sinfo;
      }
    }
    if(in_array($state, array(STATE_UPLOADED, STATE_AUTHSUCC, STATE_OVER)))
    {
      $newctrrinfos = array();
      foreach($ctrrinfos as $idx=>$info)
      {
        $ctsid = $info['ctsid'];
        if(!isset($newctrrinfos[$ctsid]))
          $newctrrinfos[$ctsid] = $info;
        else
          $newctrrinfos[$ctsid]['pfname'] .= sprintf('/%s', $info['pfname']);
      }
      $ctrrinfos = array_values($newctrrinfos);
    }

    $ajaxret['retno'] = 0;
    $ajaxret['result'] = array('ctinfo'=>$cart->find($ctid),'ctrrinfos'=>$ctrrinfos,'state'=>$state);
  }/*}}}*/
  elseif($type == 'getreasonbyid')
  {/*{{{*/
    $ty = GetItemFromArray($params,'ty');
    $rid = GetItemFromArray($params,'rid');
    if($ty == 'ctrr')
    {
      $ctrrinfo = $cart->find($rid, 'cartoonreleaserecordinfos', 'ctrr');
      $reason = $ctrrinfo['ctrrreason'];
      if($ctrrinfo['pfid'] == SOURCE_KUAIKAN)
      {
        $reason = $ctrrinfo['ctrrreason'];
        $reasondata = json_decode($reason, true);
        if(is_null($reasondata)){
        }else{
          $reason = $reasondata['comment'].'<br>';
          foreach($reasondata['tags'] as $tag)
          {
            $reason .= '<img src="'.$tag['image'].'" style="width:100%"/>';
            $reason .= $tag['tags'][0]['comment'];
          }
        }
      }
    }
    else if($ty == 'cs')
    {
      $csinfo = $cart->find($rid, 'cartoonsourceinfos', 'cs');
      $reason = $csinfo['csreason'];
    }
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result']['reason'] = $reason;
  }/*}}}*/
  elseif($type == 'getreleaseinfosbyctsid')
  {/*{{{*/
    $ctsid = GetItemFromArray($params,'ctsid');
    $ctrrinfos = $cart->GetReleaseInfosByCtsid($ctsid);
    $pfinfos = $pf->getinfos();
    $pfinfos = SetKeyFromArray($pfinfos,'pfid');
    foreach($ctrrinfos as $idx=>$info)
    {
      $ctrrinfos[$idx]['ctsinfo'] = $sect->find($info['ctsid']);
      $ctrrinfos[$idx]['pfname'] = $pfinfos[$info['pfid']]['pfname'];
      $ctrrinfos[$idx]['ctrrstatename'] = $releasestates[$info['ctrrstate']];
    }
    $ajaxret['retno'] = 0;
    $ajaxret['result'] = array('ctsinfo'=>$sect->find($ctsid),'ctrrinfos'=>$ctrrinfos);
  }/*}}}*/
  elseif($type == 'reopenrelease')
  {/*{{{*/
    $ctrrid = GetItemFromArray($params,'ctrrid');
    $cart->update($ctrrid,array('ctrrstate'=>STATE_NOR),'ctrr','cartoonreleaserecordinfos');
    $ajaxret['retno'] = 0;
  }/*}}}*/
  elseif($type == 'reopencartoonrelease')
  {/*{{{*/
    $csid = GetItemFromArray($params,'csid');
    $cart->update($csid,array('csstate'=>STATE_NOR),'cs','cartoonsourceinfos');
    $ajaxret['retno'] = 0;
  }/*}}}*/
  elseif($type == 'getstatbyctid')
  {/*{{{*/
    $ctid = GetItemFromArray($params,'ctid');
    $sdinfos = $cart->GetSelfDataInfosByCtid($ctid);
    $stat = array();
    $curday = date('Y-m-d'); $curdayin = 0; $curdayhit = 0; $curdaytu = 0; $curdaycang = 0;
    $preday = date('Y-m-d',time()-24*3600); $predayin = 0; $predayhit =0; $predaytu = 0; $predaycang = 0;
    $curmon = date('Y-m'); $curmonin = 0; $curmonhit = 0; $curmontu = 0; $curmoncang = 0;
    $total = 0; $totalhit = 0; $totaltu = 0; $totalcang = 0;
    $pfids = array();
    $newsdinfos = array();
    foreach($sdinfos as $sdinfo)
    {
      $pfids[$sdinfo['pfid']] = 1;
      $day = $sdinfo['ctsdday'];
      if($day == $curday){
        $curdayin += $sdinfo['ctsddayincome'];
        $curdayhit += $sdinfo['ctsddaybrowsercount'];
        $curdaytu += $sdinfo['ctsddaytucaocount'];
        $curdaycang += $sdinfo['ctsddaycollectcount'];
      }
      if($day == $preday){
        $predayin += $sdinfo['ctsddayincome'];
        $predayhit += $sdinfo['ctsddaybrowsercount'];
        $predaytu += $sdinfo['ctsddaytucaocount'];
        $predaycang += $sdinfo['ctsddaycollectcount'];
      }
      if(strpos($day,$curmon) === 0){
        $curmonin += $sdinfo['ctsddayincome'];
        $curmonhit += $sdinfo['ctsddaybrowsercount'];
        $curmontu += $sdinfo['ctsddaytucaocount'];
        $curmoncang += $sdinfo['ctsddaycollectcount'];
      }
      $total += $sdinfo['ctsddayincome'];
      $totalhit += $sdinfo['ctsddaybrowsercount'];
      $totaltu += $sdinfo['ctsddaytucaocount'];
      $totalcang += $sdinfo['ctsddaycollectcount'];
      $newsdinfos[$day][$sdinfo['pfid']] = $sdinfo;
    }
    $pfinfos = $pf->getinfos();
    foreach($pfinfos as $idx=>$pfinfo)
    {
      if(!isset($pfids[$pfinfo['pfid']]))
        unset($pfinfos[$idx]);
    }

    $pfinfos = array_values($pfinfos);
    $stat['income']['curday'] = $curdayin;
    $stat['income']['preday'] = $predayin;
    $stat['income']['curmon'] = $curmonin;
    $stat['income']['total'] = $total;
    $stat['hit']['curday'] = $curdayhit;
    $stat['hit']['preday'] = $predayhit;
    $stat['hit']['curmon'] = $curmonhit;
    $stat['hit']['total'] = $totalhit;
    $stat['tu']['curday'] = $curdaytu;
    $stat['tu']['preday'] = $predaytu;
    $stat['tu']['curmon'] = $curmontu;
    $stat['tu']['total'] = $totaltu;
    $stat['cang']['curday'] = $curdaycang;
    $stat['cang']['preday'] = $predaycang;
    $stat['cang']['curmon'] = $curmoncang;
    $stat['cang']['total'] = $totalcang;
   // $stat['sdinfos'] = $newsdinfos;
    $stat['sdinfos'] = array_reverse($newsdinfos);
    $stat['pfinfos'] = $pfinfos;
    $stat['ctinfo'] = $cart->find($ctid);
    $stat['tabs'] = array('总收入','浏览', '吐槽', '收藏');
    $ajaxret['retno'] = 0;
    $ajaxret['result'] = $stat;
  }/*}}}*/
  elseif($type == 'getmonthstatbyctid')
  {/*{{{*/
    $ctid = GetItemFromArray($params,'ctid');
    $sdinfos = $cart->GetSelfDataInfosByCtid($ctid);
    $stat = array();
    $curday = date('Y-m-d'); $curdayin = 0; $curdayhit = 0; $curdaytu = 0; $curdaycang = 0;
    $preday = date('Y-m-d',time()-24*3600); $predayin = 0; $predayhit =0; $predaytu = 0; $predaycang = 0;
    $curmon = date('Y-m'); $curmonin = 0; $curmonhit = 0; $curmontu = 0; $curmoncang = 0;
    $total = 0; $totalhit = 0; $totaltu = 0; $totalcang = 0;
    $pfids = array();
    $newsdinfos = array();
    foreach($sdinfos as $sdinfo)
    {
      $pfids[$sdinfo['pfid']] = 1;
      $day = $sdinfo['ctsdday'];
      if($day == $curday){
        $curdayin += $sdinfo['ctsddayincome'];
        $curdayhit += $sdinfo['ctsddaybrowsercount'];
        $curdaytu += $sdinfo['ctsddaytucaocount'];
        $curdaycang += $sdinfo['ctsddaycollectcount'];
      }
      if($day == $preday){
        $predayin += $sdinfo['ctsddayincome'];
        $predayhit += $sdinfo['ctsddaybrowsercount'];
        $predaytu += $sdinfo['ctsddaytucaocount'];
        $predaycang += $sdinfo['ctsddaycollectcount'];
      }
      if(strpos($day,$curmon) === 0){
        $curmonin += $sdinfo['ctsddayincome'];
        $curmonhit += $sdinfo['ctsddaybrowsercount'];
        $curmontu += $sdinfo['ctsddaytucaocount'];
        $curmoncang += $sdinfo['ctsddaycollectcount'];
      }
      $total += $sdinfo['ctsddayincome'];
      $totalhit += $sdinfo['ctsddaybrowsercount'];
      $totaltu += $sdinfo['ctsddaytucaocount'];
      $totalcang += $sdinfo['ctsddaycollectcount'];
      $month = substr($day,0,7);
      if(!isset($newsinfos[$month]))
        $newsinfos[$month] = array();
      if(!isset($newsinfos[$month][$sdinfo['pfid']]))
        $newsinfos[$month][$sdinfo['pfid']] = array('month'=>$month,'ctsdmonincome'=>0,'ctsdmonbrowsercount'=>0,'ctsdmontucaocount'=>0,'ctsdmoncollectcount'=>0);
      $newsdinfos[$month][$sdinfo['pfid']]['ctsdmonincome'] += $sdinfo['ctsddayincome'];
      $newsdinfos[$month][$sdinfo['pfid']]['ctsdmonbrowsercount'] += $sdinfo['ctsddaybrowsercount'];
      $newsdinfos[$month][$sdinfo['pfid']]['ctsdmontucaocount'] += $sdinfo['ctsddaytucaocount'];
      $newsdinfos[$month][$sdinfo['pfid']]['ctsdmoncollectcount'] += $sdinfo['ctsddaycollectcount'];
    }
    $pfinfos = $pf->getinfos();
    foreach($pfinfos as $idx=>$pfinfo)
    {
      if(!isset($pfids[$pfinfo['pfid']]))
        unset($pfinfos[$idx]);
    }

    $pfinfos = array_values($pfinfos);
    $stat['income']['curday'] = $curdayin;
    $stat['income']['preday'] = $predayin;
    $stat['income']['curmon'] = $curmonin;
    $stat['income']['total'] = $total;
    $stat['hit']['curday'] = $curdayhit;
    $stat['hit']['preday'] = $predayhit;
    $stat['hit']['curmon'] = $curmonhit;
    $stat['hit']['total'] = $totalhit;
    $stat['tu']['curday'] = $curdaytu;
    $stat['tu']['preday'] = $predaytu;
    $stat['tu']['curmon'] = $curmontu;
    $stat['tu']['total'] = $totaltu;
    $stat['cang']['curday'] = $curdaycang;
    $stat['cang']['preday'] = $predaycang;
    $stat['cang']['curmon'] = $curmoncang;
    $stat['cang']['total'] = $totalcang;
//    $stat['sdinfos'] = $newsdinfos;
      $stat['sdinfos'] = array_reverse($newsdinfos);
    $stat['pfinfos'] = $pfinfos;
    $stat['ctinfo'] = $cart->find($ctid);
    $stat['tabs'] = array('总收入','浏览', '吐槽', '收藏');
    $stat['tabs'] = array('总收入');
    $ajaxret['retno'] = 0;
    $ajaxret['result'] = $stat;
  }/*}}}*/
  elseif($type == 'getdaystatbyctid')
  {/*{{{*/
    $ctid = GetItemFromArray($params,'ctid');
    $mon  = GetItemFromArray($params,'mon');
    if($ctid > 0)
      $sdinfos = $cart->GetSelfDataInfosByCtid($ctid);
    else{
        if($uinfo['utype'] == USERTYPE_STUDIOMANAGE){
            //工作室管理员
            $sdinfos = $cart->GetSelfDataInfosByUid($uinfo['upuid']);
        }else{
            $sdinfos = $cart->GetSelfDataInfosByUid($uid);
        }
    }

    $stat = array();
    $curday = date('Y-m-d'); $curdayin = 0; $curdayhit = 0; $curdaytu = 0; $curdaycang = 0;
    $preday = date('Y-m-d',time()-24*3600); $predayin = 0; $predayhit =0; $predaytu = 0; $predaycang = 0;
    $curmon = date('Y-m'); $curmonin = 0; $curmonhit = 0; $curmontu = 0; $curmoncang = 0;
    $total = 0; $totalhit = 0; $totaltu = 0; $totalcang = 0;
    $pfids = array();
    $newsdinfos = array();
    foreach($sdinfos as $sdinfo)
    {
      $pfids[$sdinfo['pfid']] = 1;
      $day = $sdinfo['ctsdday'];
      if(strpos($day,$mon) !== 0)
        continue;
      if($day == $curday){
        $curdayin += $sdinfo['ctsddayincome'];
        $curdayhit += $sdinfo['ctsddaybrowsercount'];
        $curdaytu += $sdinfo['ctsddaytucaocount'];
        $curdaycang += $sdinfo['ctsddaycollectcount'];
      }
      if($day == $preday){
        $predayin += $sdinfo['ctsddayincome'];
        $predayhit += $sdinfo['ctsddaybrowsercount'];
        $predaytu += $sdinfo['ctsddaytucaocount'];
        $predaycang += $sdinfo['ctsddaycollectcount'];
      }
      if(strpos($day,$curmon) === 0){
        $curmonin += $sdinfo['ctsddayincome'];
        $curmonhit += $sdinfo['ctsddaybrowsercount'];
        $curmontu += $sdinfo['ctsddaytucaocount'];
        $curmoncang += $sdinfo['ctsddaycollectcount'];
      }
      $total += $sdinfo['ctsddayincome'];
      $totalhit += $sdinfo['ctsddaybrowsercount'];
      $totaltu += $sdinfo['ctsddaytucaocount'];
      $totalcang += $sdinfo['ctsddaycollectcount'];
      //$newsdinfos[$day][$sdinfo['pfid']] = $sdinfo;

      if(!isset($newsdinfos[$day]))
        $newsdinfos[$day] = array();
      if(!isset($newsdinfos[$day][$sdinfo['pfid']]))
        $newsdinfos[$day][$sdinfo['pfid']] = array('ctsddayincome'=>0,'ctsddaybrowsercount'=>0,'ctsddaytucaocount'=>0,'ctsddaycollectcount'=>0);
      $newsdinfos[$day][$sdinfo['pfid']]['ctsddayincome'] += $sdinfo['ctsddayincome'];
      $newsdinfos[$day][$sdinfo['pfid']]['ctsddaybrowsercount'] += $sdinfo['ctsddaybrowsercount'];
      $newsdinfos[$day][$sdinfo['pfid']]['ctsddaytucaocount'] += $sdinfo['ctsddaytucaocount'];
      $newsdinfos[$day][$sdinfo['pfid']]['ctsddaycollectcount'] += $sdinfo['ctsddaycollectcount'];
    }
    $pfinfos = $pf->getinfos();
    foreach($pfinfos as $idx=>$pfinfo)
    {
      if(!isset($pfids[$pfinfo['pfid']]))
        unset($pfinfos[$idx]);
    }

    $pfinfos = array_values($pfinfos);
    $stat['income']['curday'] = $curdayin;
    $stat['income']['preday'] = $predayin;
    $stat['income']['curmon'] = $curmonin;
    $stat['income']['total'] = $total;
    $stat['hit']['curday'] = $curdayhit;
    $stat['hit']['preday'] = $predayhit;
    $stat['hit']['curmon'] = $curmonhit;
    $stat['hit']['total'] = $totalhit;
    $stat['tu']['curday'] = $curdaytu;
    $stat['tu']['preday'] = $predaytu;
    $stat['tu']['curmon'] = $curmontu;
    $stat['tu']['total'] = $totaltu;
    $stat['cang']['curday'] = $curdaycang;
    $stat['cang']['preday'] = $predaycang;
    $stat['cang']['curmon'] = $curmoncang;
    $stat['cang']['total'] = $totalcang;
//    $stat['sdinfos'] = $newsdinfos;
      $stat['sdinfos'] = array_reverse($newsdinfos);
    $stat['pfinfos'] = $pfinfos;
    $stat['ctinfo'] = $cart->find($ctid);
    $stat['tabs'] = array('总收入','浏览', '吐槽', '收藏');
    $stat['tabs'] = array('总收入');
    $stat['i'] = GetItemFromArray($params,'i');
    $ajaxret['retno'] = 0;
    $ajaxret['result'] = $stat;
  }/*}}}*/
  elseif($type == 'getstatfortotal')
  {/*{{{*/
    if($uinfo['utype'] == USERTYPE_STUDIOMANAGE){
        //工作室管理员 查询所属工作室的数据
        $sdinfos = $cart->GetSelfDataInfosByUid($uinfo['upuid']);
    }else{
        $sdinfos = $cart->GetSelfDataInfosByUid($uid);
    }
    $stat = array();
    $curday = date('Y-m-d'); $curdayin = 0; $curdayhit = 0; $curdaytu = 0; $curdaycang = 0;
    $preday = date('Y-m-d',time()-24*3600); $predayin = 0; $predayhit =0; $predaytu = 0; $predaycang = 0;
    $curmon = date('Y-m'); $curmonin = 0; $curmonhit = 0; $curmontu = 0; $curmoncang = 0;
    $total = 0; $totalhit = 0; $totaltu = 0; $totalcang = 0;
    $pfids = array();
    $daysdinfos = array();
    $monsdinfos = array();
    foreach($sdinfos as $sdinfo)
    {
      $pfids[$sdinfo['pfid']] = 1;
      $day = $sdinfo['ctsdday'];
      if($day == $curday){
        $curdayin += $sdinfo['ctsddayincome'];
        $curdayhit += $sdinfo['ctsddaybrowsercount'];
        $curdaytu += $sdinfo['ctsddaytucaocount'];
        $curdaycang += $sdinfo['ctsddaycollectcount'];
      }
      if($day == $preday){
        $predayin += $sdinfo['ctsddayincome'];
        $predayhit += $sdinfo['ctsddaybrowsercount'];
        $predaytu += $sdinfo['ctsddaytucaocount'];
        $predaycang += $sdinfo['ctsddaycollectcount'];
      }
      if(strpos($day,$curmon) === 0){
        $curmonin += $sdinfo['ctsddayincome'];
        $curmonhit += $sdinfo['ctsddaybrowsercount'];
        $curmontu += $sdinfo['ctsddaytucaocount'];
        $curmoncang += $sdinfo['ctsddaycollectcount'];
      }
      $total += $sdinfo['ctsddayincome'];
      $totalhit += $sdinfo['ctsddaybrowsercount'];
      $totaltu += $sdinfo['ctsddaytucaocount'];
      $totalcang += $sdinfo['ctsddaycollectcount'];

      $month = substr($day,0,7);
      if(!isset($monsdinfos[$month]))
        $monsdinfos[$month] = array();
      if(!isset($monsdinfos[$month][$sdinfo['pfid']]))
        $monsdinfos[$month][$sdinfo['pfid']] = array('month'=>$month,'ctsdmonincome'=>0,'ctsdmonbrowsercount'=>0,'ctsdmontucaocount'=>0,'ctsdmoncollectcount'=>0);
      $monsdinfos[$month][$sdinfo['pfid']]['ctsdmonincome'] += $sdinfo['ctsddayincome'];
      $monsdinfos[$month][$sdinfo['pfid']]['ctsdmonbrowsercount'] += $sdinfo['ctsddaybrowsercount'];
      $monsdinfos[$month][$sdinfo['pfid']]['ctsdmontucaocount'] += $sdinfo['ctsddaytucaocount'];
      $monsdinfos[$month][$sdinfo['pfid']]['ctsdmoncollectcount'] += $sdinfo['ctsddaycollectcount'];

      $daysdinfos[$day][$sdinfo['pfid']] = $sdinfo;
    }
    $pfinfos = $pf->getinfos();
    foreach($pfinfos as $idx=>$pfinfo)
    {
      if(!isset($pfids[$pfinfo['pfid']]))
        unset($pfinfos[$idx]);
    }

    $pfinfos = array_values($pfinfos);
    $stat['income']['curday'] = $curdayin;
    $stat['income']['preday'] = $predayin;
    $stat['income']['curmon'] = $curmonin;
    $stat['income']['total'] = $total;
    $stat['hit']['curday'] = $curdayhit;
    $stat['hit']['preday'] = $predayhit;
    $stat['hit']['curmon'] = $curmonhit;
    $stat['hit']['total'] = $totalhit;
    $stat['tu']['curday'] = $curdaytu;
    $stat['tu']['preday'] = $predaytu;
    $stat['tu']['curmon'] = $curmontu;
    $stat['tu']['total'] = $totaltu;
    $stat['cang']['curday'] = $curdaycang;
    $stat['cang']['preday'] = $predaycang;
    $stat['cang']['curmon'] = $curmoncang;
    $stat['cang']['total'] = $totalcang;
    $stat['pfinfos'] = $pfinfos;
    $stat['tabs'] = array('总收入','浏览', '吐槽', '收藏');
    $stat['tabs'] = array('总收入');
    $stat['daysdinfos'] = $daysdinfos;
    $stat['monsdinfos'] = $monsdinfos;
    $ajaxret['retno'] = 0;
    $ajaxret['result'] = $stat;
  }/*}}}*/
  elseif($type == 'downdaystatebyctid')
  {/*{{{*/
    $ctid = GetItemFromArray($params,'ctid');
    $mons = GetItemFromArray($params,'mons');
    $mons = json_decode($mons, true);
    if($ctid > 0)
      $sdinfos = $cart->GetSelfDataInfosByCtid($ctid);
    else{
        if($uinfo['utype'] == USERTYPE_STUDIOMANAGE){
            //工作室管理员
            $sdinfos = $cart->GetSelfDataInfosByUid($uinfo['upuid']);
        }else{
            $sdinfos = $cart->GetSelfDataInfosByUid($uid);
        }
    }
    $stat = array();
    $curday = date('Y-m-d'); $curdayin = 0; $curdayhit = 0; $curdaytu = 0; $curdaycang = 0;
    $preday = date('Y-m-d',time()-24*3600); $predayin = 0; $predayhit =0; $predaytu = 0; $predaycang = 0;
    $curmon = date('Y-m'); $curmonin = 0; $curmonhit = 0; $curmontu = 0; $curmoncang = 0;
    $total = 0; $totalhit = 0; $totaltu = 0; $totalcang = 0;
    $pfids = array();
    $newsdinfos = array();
    foreach($sdinfos as $sdinfo)
    {
      $pfids[$sdinfo['pfid']] = 1;
      $day = $sdinfo['ctsdday'];
      $exist = false;
      foreach($mons as $mon)
      {
        if(strpos($day,$mon) === 0)
        {
          $exist = true;
          break;
        }
      }
      if($exist === false)
        continue;
      if($day == $curday){
        $curdayin += $sdinfo['ctsddayincome'];
        $curdayhit += $sdinfo['ctsddaybrowsercount'];
        $curdaytu += $sdinfo['ctsddaytucaocount'];
        $curdaycang += $sdinfo['ctsddaycollectcount'];
      }
      if($day == $preday){
        $predayin += $sdinfo['ctsddayincome'];
        $predayhit += $sdinfo['ctsddaybrowsercount'];
        $predaytu += $sdinfo['ctsddaytucaocount'];
        $predaycang += $sdinfo['ctsddaycollectcount'];
      }
      if(strpos($day,$curmon) === 0){
        $curmonin += $sdinfo['ctsddayincome'];
        $curmonhit += $sdinfo['ctsddaybrowsercount'];
        $curmontu += $sdinfo['ctsddaytucaocount'];
        $curmoncang += $sdinfo['ctsddaycollectcount'];
      }
      $total += $sdinfo['ctsddayincome'];
      $totalhit += $sdinfo['ctsddaybrowsercount'];
      $totaltu += $sdinfo['ctsddaytucaocount'];
      $totalcang += $sdinfo['ctsddaycollectcount'];
      //$newsdinfos[$day][$sdinfo['pfid']] = $sdinfo;

      if(!isset($newsdinfos[$day]))
        $newsdinfos[$day] = array();
      if(!isset($newsdinfos[$day][$sdinfo['pfid']]))
        $newsdinfos[$day][$sdinfo['pfid']] = array('ctsddayincome'=>0,'ctsddaybrowsercount'=>0,'ctsddaytucaocount'=>0,'ctsddaycollectcount'=>0);
      $newsdinfos[$day][$sdinfo['pfid']]['ctsddayincome'] += $sdinfo['ctsddayincome'];
      $newsdinfos[$day][$sdinfo['pfid']]['ctsddaybrowsercount'] += $sdinfo['ctsddaybrowsercount'];
      $newsdinfos[$day][$sdinfo['pfid']]['ctsddaytucaocount'] += $sdinfo['ctsddaytucaocount'];
      $newsdinfos[$day][$sdinfo['pfid']]['ctsddaycollectcount'] += $sdinfo['ctsddaycollectcount'];
    }
    $pfinfos = $pf->getinfos();
    foreach($pfinfos as $idx=>$pfinfo)
    {
      if(!isset($pfids[$pfinfo['pfid']]))
        unset($pfinfos[$idx]);
    }

    $pfinfos = array_values($pfinfos);
    $stat['income']['curday'] = $curdayin;
    $stat['income']['preday'] = $predayin;
    $stat['income']['curmon'] = $curmonin;
    $stat['income']['total'] = $total;
    $stat['hit']['curday'] = $curdayhit;
    $stat['hit']['preday'] = $predayhit;
    $stat['hit']['curmon'] = $curmonhit;
    $stat['hit']['total'] = $totalhit;
    $stat['tu']['curday'] = $curdaytu;
    $stat['tu']['preday'] = $predaytu;
    $stat['tu']['curmon'] = $curmontu;
    $stat['tu']['total'] = $totaltu;
    $stat['cang']['curday'] = $curdaycang;
    $stat['cang']['preday'] = $predaycang;
    $stat['cang']['curmon'] = $curmoncang;
    $stat['cang']['total'] = $totalcang;
//    $stat['sdinfos'] = $newsdinfos;
      $stat['sdinfos'] = array_reverse($newsdinfos);
    $stat['pfinfos'] = $pfinfos;
    $stat['ctinfo'] = $cart->find($ctid);
    $stat['tabs'] = array('总收入','浏览', '吐槽', '收藏');
    $stat['tabs'] = array('总收入');
    $stat['i'] = GetItemFromArray($params,'i');
    /*{{{ excel输出 */
    $ht = '<html><head>'."\n";
    $ht .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n";
    $ht .= '<style type="text/css">#bowser{padding-left:3px; padding-right:3px; white-space:nowrap}</style>'."\n";
    $ht .= '</head><body>'."\n";
    if($ctid > 0)
      $ht .= '<table class="table"><tr><td>日期</td><td>作品名</td>'."\n";
    else
      $ht .= '<table class="table"><tr><td>日期</td>'."\n";

    foreach($stat['pfinfos'] as $pfinfo)
    {
      $ht .= sprintf('<td>%s</td>', $pfinfo['pfname']);
    }
    $ht .= '<td>合计</td></tr>';
    foreach($stat['sdinfos'] as $j=>$sdinfo)
    {
      $ht .= sprintf('<tr><td>%s</td>', $j);
      if($ctid > 0)
        $ht .= sprintf('<td>%s</td>', $stat['ctinfo']['ctname']);
      $total = 0;
      foreach($stat['pfinfos'] as $k=>$pfinfo)
      {
        $pfid = $pfinfo['pfid'];
        $v = 0;
        if(isset($stat['sdinfos'][$j][$pfid]))
        {
          $val = $stat['sdinfos'][$j][$pfid]['ctsddayincome'];
          if(($val>0) || (strlen($val)))
          {
            if(strlen($val) > 0)
            {
              $total += str_replace(',','',$val);
              $v = str_replace(',','',$val);
            }
            else
            {
              $total += $val;
              $v = $val;
            }
            $v = sprintf("%.2f", $v);
            $ht .= sprintf('<td>%s</td>', $v);
          }
          else
          {
            $ht .= '<td style="text-align:center">-</td>';
          }
        }
        else
        {
          $ht .= '<td style="text-align:center">-</td>';
        }
      }
      $total = sprintf('%.2f', $total);
      $ht .= sprintf('<td>%s</td></tr>', $total);
    }
    $ht .= '</table></body></html>';

    header("Cache-Control: public");
    header ("Content-type: application/x-msexcel");
    Header("Accept-Ranges: bytes");
    header(sprintf('Content-Disposition: attachment; filename=%d.xls', time()));
    header("Pragma:no-cache");
    header("Expires:0");
    echo $ht;
    exit();
    /*}}}*/
  }/*}}}*/
  elseif($type == 'download_detail')
  {/*{{{*/
      set_time_limit(10);
      $pfid = $params['pfid'];
      $month = $params['month'];

      $sqlpf = sprintf('select pfname from platforminfos where pfid = %d limit 1',$pfid);
      $ajaxret['paltinfo'] = $cart->ExecuteRead($sqlpf)[0]['pfname'];
      if($uinfo['utype'] == USERTYPE_STUDIOMANAGE){
          //工作室管理员
          $uid = $uinfo['upuid'];
      }else{
          $uid = $uinfo['uid'];
      }

      $sql = sprintf('select * from cartooninfos where uid = %d and ctstate != 400', $uid);

      $cartoons = $cart->ExecuteRead($sql);
      //print_r($cartoons);exit;
      
      $data = [];
      $first = $month.'-01';
      $last = strtotime($first.' + 1 month - 1 day');
      $last = date('Y-m-d',$last);
      $day = explode('-',$last);
      $headArr = ["日期"];
      foreach ($cartoons as $cartoon){
          $headArr[] = $cartoon['ctname'];

          $dsql = sprintf('select ctsdday,sum(ctsddayincome) as income from cartoonselfdatainfos where ctid=%s and pfid=%d and  ctsdday >= "%s" and ctsdday <= "%s" GROUP BY ctsdday ORDER BY ctsdday asc', $cartoon['ctid'], $pfid, $first,$last);

          $incomes = $cart->ExecuteRead($dsql);

          $incomefinal = [];
          for($i = 1;$i<=$day[2];$i++){
              $incomefinal[] = [
                  'ctsdday' => $month.'-'.str_pad($i,2,"0",STR_PAD_LEFT),
                  'income' => 0
              ];
          }
          if(!empty($incomes)){
              foreach ($incomefinal as &$item){
                  foreach ($incomes as $income){
                      if($item['ctsdday'] == $income['ctsdday'])
                      {
                          $item['income'] = $income['income'];
                          break;
                      }
                  }

              }
          }

          //$data[] = ['ctid' => $cartoon['ctid'], 'ctname' =>$cartoon['ctname'], 'income'=>$incomes, 'fincome'=>$incomefinal];
          $data[] = ['ctid' => $cartoon['ctid'], 'ctname' =>$cartoon['ctname'],  'fincome'=>$incomefinal];
      }

      $excelData = [];
      //提取日期
      $dayArr = [];
      foreach ($data[0]['fincome'] as $dayIncom){
        $dayArr[] = $dayIncom['ctsdday'];
      }

      foreach ($dayArr as $dKey => $day){
          $tmpData[] = $day;
          foreach ($headArr as $key => $headName){
            if($key == 0)continue;
              foreach ($data as $key2 => $ctincom){
                  if($ctincom['ctname'] == $headName){
                      $tmpData[] = $ctincom['fincome'][$dKey]['income'];
                      break;
                  }
              }

          }
          $excelData[] = $tmpData;
          $tmpData = null;
      }

    $fileName= $sources[$pfid].$month.'收入数据';

    getExcel($fileName,$headArr,$excelData);
    exit;
  }
  elseif($type == 'getaccountbyctrrid')
  {/*{{{*/
    $ctrrid = GetItemFromArray($params,'ctrrid');
    $ctrrinfo = $cart->find($ctrrid,'cartoonreleaserecordinfos','ctrr');
    $pfid  = $ctrrinfo['pfid'];
    $upfid = $ctrrinfo['upfid'];
    $pfinfo  = $cart->find($pfid,'platforminfos','pf');
    $upfinfo = $cart->find($upfid,'userandplatforminfos','upf');
    $username = $upfinfo['upfusername'];
    $password = $upfinfo['upfpassword'];
    if(empty($username)){
      $username = $pfinfo['pfusername'];
      $password = $pfinfo['pfpassword'];
    }
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = array('username'=>$username, 'password'=>$password, 'cookies'=>$ctrrinfo['ctrrcookies']);
  }/*}}}*/
  elseif($type == 'setcookiesforctrrid')
  {/*{{{*/
    $ctrrid = GetItemFromArray($params,'ctrrid');
    $cookies = GetItemFromArray($params,'cookies');
    $cart->update($ctrrid,array('ctrrcookies'=>$cookies),'ctrr','cartoonreleaserecordinfos');
    $ajaxret['retno'] = RETNO_SUCC;
  }/*}}}*/
  /* 获取章节图片信息 */
  elseif($type == 'getsectionimgurls')
  {/*{{{*/
    $ctsid = intval(GetItemFromArray($params,'ctsid'));
    $ctsinfo = $cart->find($ctsid,'cartoonsectioninfos','cts');
    if(!empty($ctsinfo))
    {
      $imgurlinfos = array();
      if(strlen($ctsinfo['ctscontent']))
      {
        $imgurl = json_decode($ctsinfo['ctscontent'], true);
        foreach($imgurl as $idx=>$value)
        {
          $imgurlinfos[$idx+1] = $value;
        }
      }
      $ajaxret['retno'] = RETNO_SUCC;
      $ajaxret['imgurl'] = $imgurlinfos;
      $ajaxret['imgirlcount'] = count($imgurlinfos);
      $ajaxret['uid'] = $uid;
    }
    else
      $ajaxret['msg'] = '参数无效';
  }/*}}}*/
  elseif($type == 'downsectionimgs')
  {/*{{{*/
    $ctsid = intval(GetItemFromArray($params,'ctsid'));
    $ctsinfo = $cart->find($ctsid,'cartoonsectioninfos','cts');
    $imgurlinfos = array();
    {
      $imgurl = json_decode(GetItemFromArray($params,'images'), true);
      $tmppath = sprintf('/tmp/%s/', $ctsid);
      exec(sprintf('rm -r %s >/dev/null 2>&1', $tmppath),$ret);
      mkdir($tmppath);
      $zipfile = sprintf('/tmp/%s.zip', $ctsid);
      $zip = new ZipArchive();
      $zip->open($zipfile,ZipArchive::CREATE);
      foreach($imgurl as $idx=>$value)
      {
        $filename = '';
        if(isset($value['filename']))
          $filename = $tmppath.$value['filename'];
        else{
          $pathinfo = pathinfo($value['imgurl']);
          if(empty($pathinfo['extension']))
            $pathinfo['extension'] = 'jpg';
          $filename = $tmppath.sprintf('%d.%s', $idx, $pathinfo['extension']);
        }
        file_put_contents($filename, file_get_contents($value['imgurl'].'-origin'));
        $zip->addFile($filename,basename($filename));
      }
      $zip->close();
      header('Content-Disposition:attachment;filename='.basename($zipfile));
      echo file_get_contents($zipfile);
      unlink($zipfile);
      exec(sprintf('rm -r %s >/dev/null 2>&1', $tmppath),$ret);
    }
  }/*}}}*/
  elseif($type == 'getxishuinfos'){
    /*{{{*/
    $csinfos = array();
    if($uinfo['udataauth'] == 5){
      if($uinfo['utype'] == USERTYPE_STUDIOMANAGE){
          //工作室管理员 查询所属工作室的数据
          $upfinfo = $pf->existUserAndPlatform($uinfo['upuid'],SOURCE_TENCENT);
          $uid = $uinfo['upuid'];
      }else{
          $upfinfo = $pf->existUserAndPlatform($uid,SOURCE_TENCENT);
      }
      if($upfinfo){
        $cartinfos = $cart->getinfos(sprintf('uid=%d', $uid));
        foreach($cartinfos as $ctinfo){
          $csinfo = $cart->CartoonSelfExistForCtidAndSource($ctinfo['ctid'], SOURCE_TENCENT);
          if($csinfo && empty($csinfo['csdividexishu'])){
            $csinfo['pfname'] = $sources[$csinfo['cssource']];
            $csinfo['ctname'] = $ctinfo['ctname'];
            $csinfos[] = $csinfo;
          }
        }
      }
    }
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $csinfos;
  }/*}}}*/
  elseif($type == 'setxishuinfos'){
    /*{{{*/
    $xishues = json_decode(GetItemFromArray($params,'xishues'),true);
    foreach($xishues as $xishu){
      $cart->update($xishu['csid'], array('csdividexishu'=>$xishu['value']),'cs', 'cartoonsourceinfos');
    }
    $ajaxret['retno'] = RETNO_SUCC;
  }/*}}}*/
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

/**
 * 保存自动发布到渠道记录
 * @param $sect
 * @param $params
 */
function saveAutoReleaseRec(&$sect,$params){
    if(!isset($params['release_time']))
      return ;

    // 校验时间合法性
    if(time() > strtotime($params['release_time']))
      return ;

    // 查找是否存在相同章节的记录
    $sql = sprintf('select * from section_auto_release where section_id = %d',$params['ctsid']);
    $exist = $sect->ExecuteRead($sql);
    // 添加新记录
    if(empty($exist)){
        // 检查是否有之前章节更晚发布的
        $sql = sprintf('select * from section_auto_release where section_id < %d and set_time> \'%s\'',$params['ctsid'],$params['release_time']);
        $errorRes = $sect->ExecuteRead($sql);
        if(!empty($errorRes))
          return ;

        // 添加自动发布到渠道任务记录
        if(!empty($params['release_time'])){
            $sql = sprintf('insert into section_auto_release(`cartoon_id`,`section_id`,`set_time`) VALUE (%d,%d,\'%s\')',$params['ctid'],$params['ctsid'],$params['release_time']);
            $sect->ExecuteSql($sql);
        }
    }else{
        // TODO 暂时未考虑更新已发布记录的情况
        // 检查是否有之前章节更晚发布的
        $sql = sprintf('select * from section_auto_release where section_id < %d and set_time> \'%s\'',$params['ctsid'],$params['release_time']);
        $errorRes = $sect->ExecuteRead($sql);
        if(!empty($errorRes))
            return ;
        
        $sql = sprintf('update section_auto_release set `set_time`=\'%s\',`update_time`= \'%s\' where `id` = %d',$params['release_time'],date('Y-m-d H:i:s'),$exist[0]['id']);
        $sect->ExecuteSql($sql);
    }
}
?>
