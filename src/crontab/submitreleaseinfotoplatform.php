<?php
/*
 * 用途：提交发布信息到平台
 * 作者：feb1234@163.com
 * 时间：2017-11-20
 * */
$base_dir = dirname(__FILE__).'/../../';
require_once($base_dir.'config/config.php');
require_once($base_dir.'inc/util_for_simulate.php');
require_once($base_dir.'inc/util_for_meizizi.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsPlatforminfos.php');

$logger = 'meizizicon.cron.submitrelease';
qlogConfig($base_dir.'config/qlog.cfg');
$cart = new Cartooninfos();
$sect = new CartoonSectioninfos();
$pf = new PlatformInfos();

$exist = false;
$ctrrinfos = $cart->GetUnReleaseRecordInfos();
foreach($ctrrinfos as $info)
{
  $ctid  = $info['ctid'];
  $ctsid = $info['ctsid'];
  $pfid  = $info['pfid'];
  $upfid = $info['upfid'];
  $pfinfo  = $cart->find($pfid,'platforminfos','pf');
  $upfinfo = $cart->find($upfid,'userandplatforminfos','upf');
  $username = $upfinfo['upfusername'];
  $password = $upfinfo['upfpassword'];
  if(empty($username)){
    $username = $pfinfo['pfusername'];
    $password = $pfinfo['pfpassword'];
  }
  $csinfo = $cart->GetCartoonReleaseInfo($ctid, $pfid);
  if(empty($csinfo))
  {
    $csinfo = array('ctid'=>$ctid,'cssource'=>$pfid);
    $csid = $cart->add($csinfo, 'cartoonsourceinfos');
    $csinfo = $cart->find($csid,'cartoonsourceinfos','cs');
  }

  //if($ctid != 204356)
  //  continue;
  //if(!in_array($info['ctid'],array(454810,454805,454804)))
  //if(!in_array($info['ctid'],array(454832,454831,454830,44702,44701,454833,454865,454804,204356,454825,44700,454849,455323,455322,454850,454812,454810,454805,44699,479247,204357,479251,479248,479260,480975,480974,454803,481132,454835,454841,454844,481445)))
  //  continue;

  //if($pfid != SOURCE_ZHANGYUE)
  //  $exist = true;

  /*{{{ 上传漫画信息 */
  if($csinfo['csstate']==STATE_NOR)
  {
    $ctinfo = $cart->find($ctid);
    $ctinfo['csreleasetype'] = $csinfo['csreleasetype'];
    $ctinfo['csfirstrelease'] = $csinfo['csfirstrelease'];
    $ctimginfos = $cart->getinfos(sprintf('ccpstate=%d and ctid=%d', STATE_NOR, $ctid),'cartooncoverandplatforminfos');
    $ctimginfos = SetKeyFromArray($ctimginfos,'pfid');
    if($pfid == SOURCE_ZHANGYUE)
    {/*{{{ 掌阅 */
      $succ = test_account_for_zhangyue($username, $password);
      if($succ){
        //$ctinfo['ctverticalimage'] = $ctimginfos[SOURCE_ZHANGYUE]['ccpverticalimg'];
        $subinfo = createcartoon_for_zhangyue($ctinfo);
        qLogInfo($logger, sprintf('createcartoon_for_zhangyue %s %s', json_encode($ctinfo), json_encode($subinfo)));
        if($subinfo['status'] == 200)
        {
          $cart->update($csinfo['csid'],array('cssourceid'=>$subinfo['data'],'csstate'=>STATE_UPLOADED),'cs','cartoonsourceinfos');
          $auditinfo = submitcartoonaudit_for_zhangyue($subinfo['data']);
          qLogInfo($logger, sprintf('submitcartoonaudit_for_zhangyue %s %s', $subinfo['data'], json_encode($auditinfo)));
        }
        else
        {
          $cart->update($csinfo['csid'],array('csstate'=>STATE_UPLOADFAIL,'csreason'=>$subinfo['msg']),'cs','cartoonsourceinfos');
        }
      }else{
        $cart->update($csinfo['csid'],array('csstate'=>STATE_UPLOADFAIL,'csreason'=>'登录失败，请检查账号密码是否正确'),'cs','cartoonsourceinfos');
      }
    }/*}}}*/
    elseif($pfid == SOURCE_MANHUADAO)
    {/*{{{ 漫画岛 */
      $ctinfo['ctverticalimage'] = $ctimginfos[SOURCE_MANHUADAO]['ccpverticalimg'];
      $succ = test_account_for_manhuadao($username, $password);
      if($succ){
        $subinfo = createcartoon_for_manhuadao($ctinfo);
        qLogInfo($logger, sprintf('createcartoon_for_manhuadao %s %s', json_encode($ctinfo), json_encode($subinfo)));
        if($subinfo['Statu'] == 1)
        {
          $cart->update($csinfo['csid'],array('csstate'=>STATE_UPLOADED,'cssourceid'=>$subinfo['Data']),'cs','cartoonsourceinfos');
        }
        else
        {
          $cart->update($csinfo['csid'],array('csstate'=>STATE_UPLOADFAIL,'csreason'=>$subinfo['Msg']),'cs','cartoonsourceinfos');
        }
      }else{
        $cart->update($csinfo['csid'],array('csstate'=>STATE_UPLOADFAIL,'csreason'=>'登录失败'),'cs','cartoonsourceinfos');
      }
    }/*}}}*/
    elseif($pfid == SOURCE_BUKA)
    {/*{{{ 布卡 */
      continue;
      test_account_for_buka($username, $password);
      $subinfo = createcartoon_for_buka($ctinfo);
      qLogInfo($logger, sprintf('createcartoon_for_buka %s %s', json_encode($ctinfo), json_encode($subinfo)));
      if(($subinfo) && ($subinfo['ret']==0))
      {
        $cart->update($csinfo['csid'],array('csstate'=>STATE_UPLOADED,'cssourceid'=>$subinfo['mid']),'cs','cartoonsourceinfos');
      }
      else
      {
        $cart->update($csinfo['csid'],array('csstate'=>STATE_UPLOADFAIL,'csreason'=>'未知原因'),'cs','cartoonsourceinfos');
      }
    }/*}}}*/
    elseif($pfid == SOURCE_WANGYI)
    {/*{{{ 网易 */
      $succ = test_account_for_wangyi($username, $password);
      if($succ){
        $ctinfo['ctverticalimage'] = $ctimginfos[SOURCE_WANGYI]['ccpverticalimg'];
        $ctinfo['cthorizontalimage'] = $ctimginfos[SOURCE_WANGYI]['ccphorizontalimg'];
        $nctid = createcartoon_for_wangyi($ctinfo);
        qLogInfo($logger, sprintf('createcartoon_for_wangyi %s %s', json_encode($ctinfo), $nctid));
        if(!empty($nctid))
        {
          $cart->update($csinfo['csid'],array('csstate'=>STATE_UPLOADED,'cssourceid'=>$nctid),'cs','cartoonsourceinfos');
        }
        else
        {
          $cart->update($csinfo['csid'],array('csstate'=>STATE_UPLOADFAIL,'csreason'=>'未知原因'),'cs','cartoonsourceinfos');
        }
      }else{
        $cart->update($csinfo['csid'],array('csstate'=>STATE_UPLOADFAIL,'csreason'=>'登录失败，请检查账号密码是否正确'),'cs','cartoonsourceinfos');
      }
    }/*}}}*/
    elseif($pfid == SOURCE_TENCENT)
    {/*{{{ 腾讯 */
      //$cookies = test_account_for_tencent($username, $password);
      //$cookies = $info['ctrrcookies'];//test_account_for_tencent($username, $password);
      $exist = true;
      $cookies = '';
      if($upfinfo['upfcookiesstate'] == STATE_COOKIES){
        if(get_loginstate_for_tencent($upfinfo['upfcookies']))
          $cookies = $upfinfo['upfcookies'];
      }else{
        $pf->update($upfinfo['upfid'], array('upfcookiesstate'=>STATE_INVALID,'upfupdatetime'=>date('Y-m-d H:i:s')), 'upf', 'userandplatforminfos');
        continue;
      }

      if(!empty($cookies))
      {
        $ctinfo['ctverticalimage'] = $ctimginfos[SOURCE_TENCENT]['ccpverticalimg'];
        $ctinfo['cthorizontalimage'] = $ctimginfos[SOURCE_TENCENT]['ccphorizontalimg'];
        $ret = createcartoon_for_tencent($ctinfo, $cookies);
        qLogInfo($logger, sprintf('createcartoon_for_tencent %s %s', json_encode($ctinfo), json_encode($ret)));
        if(!empty($ret['ctid']))
        {
          $cart->update($csinfo['csid'],array('csstate'=>STATE_UPLOADED,'cssourceid'=>$ret['ctid']),'cs','cartoonsourceinfos');
        }
        else
        {
          $cart->update($csinfo['csid'],array('csstate'=>STATE_UPLOADFAIL,'csreason'=>$ret['msg']),'cs','cartoonsourceinfos');
        }
      }
    }/*}}}*/
    elseif($pfid == SOURCE_AIQIYI)
    {/*{{{ 爱奇艺 */
      //$cookies = $info['ctrrcookies'];//test_account_for_iqiyi($username, $password);
      $exist = true;
      $cookies = '';
      if($upfinfo['upfcookiesstate'] == STATE_COOKIES){
        if(get_loginstate_for_iqiyi($upfinfo['upfcookies'])){
          $cookies = $upfinfo['upfcookies'];
        }else{
          $pf->update($upfinfo['upfid'], array('upfcookiesstate'=>STATE_INVALID,'upfupdatetime'=>date('Y-m-d H:i:s')), 'upf', 'userandplatforminfos');
          continue;
        }
      }else{
        $pf->update($upfinfo['upfid'], array('upfcookiesstate'=>STATE_INVALID,'upfupdatetime'=>date('Y-m-d H:i:s')), 'upf', 'userandplatforminfos');
        continue;
      }
      if(!empty($cookies))
      {
        $ctinfo['ctverticalimage'] = $ctimginfos[SOURCE_AIQIYI]['ccpverticalimg'];
        $subinfo = createcartoon_for_iqiyi($ctinfo, $cookies);
        qLogInfo($logger, sprintf('createcartoon_for_iqiyi %s %s', json_encode($ctinfo), json_encode($subinfo)));
        if($subinfo['code'] === 0)
        {
          if(isset($subinfo['data']['bookId']))
          {
            $bookId = $subinfo['data']['bookId'];
            $bakId = $subinfo['data']['qipuId'];
          /*$ctlist = get_cartoonlist_for_iqiyi($cookies);
          foreach($ctlist['data'] as $row)
          {
            if($row['id'] == $bookId)
            {
              $bakId = $row['qipuId'];
              break;
            }
          }*/

            $cart->update($csinfo['csid'],array('csstate'=>STATE_UPLOADED,'cssourceid'=>$subinfo['data']['bookId'],'cssourcebakid'=>$bakId),'cs','cartoonsourceinfos');
          }
        }
        else
        {
          $cart->update($csinfo['csid'],array('csstate'=>STATE_UPLOADFAIL,'csreason'=>$subinfo['msg']),'cs','cartoonsourceinfos');
        }
      }
    }/*}}}*/
  }
  /*}}}*/

  /*{{{ 判断该章节之前是否上传成功 */
  $newctrrinfos = $cart->GetReleaseRecordInfosByCtid($ctid);
  $exist = false;
  foreach($newctrrinfos as $nctrrinfo)
  {
    //$info['ctrrid']
    if($info['ctrrid'] > $nctrrinfo['ctrrid'])
    {
      if(in_array($nctrrinfo['ctrrstate'], array(STATE_NOR,STATE_FAIL)))
      {
        $exist = true;
        break;
      }
    }
  }
  //if($exist)
  //  continue;
  /*}}}*/

  /*{{{ 上传漫画章节信息 */
  $csinfo = $cart->find($csinfo['csid'],'cartoonsourceinfos','cs');
  if($info['ctrrstate'] == STATE_NOR)
  {
    $ctinfo = $cart->find($ctid);
    $sectinfo = $sect->find($ctsid);
    $sectinfo['ctvector'] = $ctinfo['ctvector'];
    $sectinfo['cssourceid'] = $csinfo['cssourceid'];
    $sectinfo['cssourcebakid'] = $csinfo['cssourcebakid'];
    $sectinfo['ctrrreleasetime'] = $info['ctrrreleasetime'];
    $sectinfo['ctprices'] = $ctinfo['ctprices'];
    $tailframeinfos = array();
    if(!empty($sectinfo['ctstailframeinfos']))
      $tailframeinfos = json_decode($sectinfo['ctstailframeinfos'], true);
    $nctsinfo = $sect->GetSectInfoByCtsidAndPfid($ctsid, $pfid);
    if(!empty($nctsinfo)){
      $sectinfo['ctscontent'] = $nctsinfo['ctscontent'];
      $sectinfo['ctscover'] = $nctsinfo['ctscover'];
      $sectinfo['ctsplatformcoverinfos'] = json_encode((object)array());
    }
    if(isset($tailframeinfos[$pfid])){
      $ctscontent = json_decode($sectinfo['ctscontent'], true);
      $ctscontent[] = $tailframeinfos[$pfid];
      $sectinfo['ctscontent'] = json_encode($ctscontent);
    }

    if($pfid == SOURCE_ZHANGYUE)
    {/*{{{ 掌阅 */
      if($csinfo['csstate'] == STATE_AUTHSUCC)
      {
        $succ = test_account_for_zhangyue($username, $password);
        if($succ){
          //$nctsinfo = $sect->GetSectInfoByCtsidAndPfid($ctsid, $pfid);
          //if(!empty($nctsinfo))
          //  $sectinfo['ctscontent'] = $nctsinfo['ctscontent'];
          $submitret = createsection_for_zhangyue($sectinfo);
          qLogInfo($logger, sprintf('createsection_for_zhangyue %s %s', json_encode($sectinfo), json_encode($submitret)));
          if($submitret['status'] == 200)
          {
            $cart->update($info['ctrrid'],array('ctrrpfsectionid'=>$submitret['data']['chapter_id'],'ctrrstate'=>STATE_UPLOADED), 'ctrr', 'cartoonreleaserecordinfos');
            $ctrrreleasetime = $info['ctrrreleasetime'];
            if(empty($ctrrreleasetime))
            {
              $pubret = submitcartoonsectionrelease_for_zhangyue($submitret['data']['chapter_id']);
              qLogInfo($logger, sprintf('submitcartoonsectionrelease_for_zhangyue %s %s', $submitret['data']['chapter_id'], json_encode($pubret)));
            }
            else
            {
              $pubret = submitcartoonsectionrelease_for_zhangyue($submitret['data']['chapter_id'],$sectinfo['cssourceid'],date("Y-m-d H:i:s", strtotime($ctrrreleasetime)));
              qLogInfo($logger, sprintf('submitcartoonsectionrelease_for_zhangyue %s %s', $submitret['data']['chapter_id'], json_encode($pubret)));
            }
          }
          else
          {
            $cart->update($info['ctrrid'],array('ctrrreason'=>$submitret['msg'],'ctrrstate'=>STATE_UPLOADFAIL), 'ctrr', 'cartoonreleaserecordinfos');
          }
        }else{
          $cart->update($info['ctrrid'],array('ctrrreason'=>'登录失败，请检查账号密码是否正确','ctrrstate'=>STATE_UPLOADFAIL), 'ctrr', 'cartoonreleaserecordinfos');
        }
      }
    }/*}}}*/
    elseif($pfid == SOURCE_MANHUADAO)
    {/*{{{ 漫画岛 */
      if(!empty($csinfo['cssourceid'])){
        $succ = test_account_for_manhuadao($username, $password);
        //$nctsinfo = $sect->GetSectInfoByCtsidAndPfid($ctsid, $pfid);
        //if(!empty($nctsinfo)){
        //  $sectinfo['ctscontent'] = $nctsinfo['ctscontent'];
        //  $sectinfo['ctscover'] = $nctsinfo['ctscover'];
        //  $sectinfo['ctsplatformcoverinfos'] = json_encode((object)array());
        //}
        if($succ) {
          $submitret = createsection_for_manhuadao($sectinfo);
          qLogInfo($logger, sprintf('createsection_for_manhuadao %s %s', json_encode($sectinfo), json_encode($submitret)));
          if($submitret['Statu'] == 1)
          {
            $sectlist = get_cartoomsectionlist_for_manhuadao($csinfo['cssourceid']);
            foreach($sectlist as $row)
            {
              if(trim($sectinfo['ctsname']) == trim($row['ctsname']))
              {
                $cart->update($info['ctrrid'],array('ctrrpfsectionid'=>$row['ctssourceid'],'ctrrstate'=>STATE_UPLOADED), 'ctrr', 'cartoonreleaserecordinfos');
                break;
              }
            }
          }
          else
          {
            $cart->update($info['ctrrid'],array('ctrrreason'=>$submitret['Msg'],'ctrrstate'=>STATE_UPLOADFAIL), 'ctrr', 'cartoonreleaserecordinfos');
          }
        }else{
          $cart->update($info['ctrrid'],array('ctrrreason'=>'登录失败','ctrrstate'=>STATE_UPLOADFAIL), 'ctrr', 'cartoonreleaserecordinfos');
        }
      }
    }/*}}}*/
    elseif($pfid == SOURCE_WANGYI)
    {/*{{{ 网易 */
      //$sectinfo = $sect->find($ctsid);
      //$nctsinfo = $sect->GetSectInfoByCtsidAndPfid($ctsid, $pfid);
      //if(!empty($nctsinfo))
      //  $sectinfo['ctscontent'] = $nctsinfo['ctscontent'];
      $sectinfo['cssourceid'] = $csinfo['cssourceid'];
      $sectinfo['ctname'] = $ctinfo['ctname'];
      $succ = test_account_for_wangyi($username, $password);
      if($succ){
        $submitret = createsection_for_wangyi($sectinfo);
        qLogInfo($logger, sprintf('createsection_for_wangyi %s %s', json_encode($sectinfo), json_encode($submitret)));
        if($submitret && (isset($submitret['error']['code'])) && ($submitret['error']['code']==0))
        {
          $sectlist = get_cartoomsectionlist_for_wangyi($csinfo['cssourceid']);
          foreach($sectlist as $idx=>$sinfo)
          {
            $name = trim($sinfo['ctsname']);
            $pos = strpos($name,'：');
            if($pos !== false)
              $name = substr($name, $pos+strlen("："));
            if($name == trim($sectinfo['ctsname']))
            {
              $cart->update($info['ctrrid'],array('ctrrpfsectionid'=>$sinfo['ctssourceid'],'ctrrstate'=>STATE_UPLOADED), 'ctrr', 'cartoonreleaserecordinfos');
              break;
            }
          }
        }
        else
        {
          $cart->update($info['ctrrid'],array('ctrrreason'=>$submitret['error']['message'],'ctrrstate'=>STATE_UPLOADFAIL), 'ctrr', 'cartoonreleaserecordinfos');
        }
      }else{
        $cart->update($info['ctrrid'],array('ctrrreason'=>'登录失败，请检查账号密码是否正确','ctrrstate'=>STATE_UPLOADFAIL), 'ctrr', 'cartoonreleaserecordinfos');
      }
    }/*}}}*/
    elseif($pfid == SOURCE_TENCENT)
    {/*{{{ 腾讯 */
      //$cookies = $info['ctrrcookies'];//test_account_for_tencent($username, $password);
      $exist = true;
      $cookies = '';
      if($upfinfo['upfcookiesstate'] == STATE_COOKIES){
        if(get_loginstate_for_tencent($upfinfo['upfcookies'])){
          $cookies = $upfinfo['upfcookies'];
        }else{
          $pf->update($upfinfo['upfid'], array('upfcookiesstate'=>STATE_INVALID,'upfupdatetime'=>date('Y-m-d H:i:s')), 'upf', 'userandplatforminfos');
          continue;
        }
      }else{
        $pf->update($upfinfo['upfid'], array('upfcookiesstate'=>STATE_INVALID,'upfupdatetime'=>date('Y-m-d H:i:s')), 'upf', 'userandplatforminfos');
        continue;
      }

      //$cookies = test_account_for_tencent($username, $password);
      if(!empty($cookies))
      {
        $sectinfo['cssourceid'] = $csinfo['cssourceid'];
        if(empty($sectinfo['cssourceid']))
          continue;
        $sectinfo['ctrrpfsectionid'] = $info['ctrrpfsectionid'];
        //$nctsinfo = $sect->GetSectInfoByCtsidAndPfid($ctsid, $pfid);
        //if(!empty($nctsinfo)){
        //  $sectinfo['ctscontent'] = $nctsinfo['ctscontent'];
        //  $sectinfo['ctscover'] = $nctsinfo['ctscover'];
        //  $sectinfo['ctsplatformcoverinfos'] = json_encode((object)array());
          //if(!empty($nctsinfo['ctsplatformcoverinfos']))
          //  $sectinfo['ctsplatformcoverinfos'] = $nctsinfo['ctsplatformcoverinfos'];
        //}
        $submitret = createsection_for_tencent($sectinfo, $cookies);
        qLogInfo($logger, sprintf('createsection_for_tencent %s %s', json_encode($sectinfo), json_encode($submitret)));
        if($submitret['code'] == 0)
        {
          $cart->update($info['ctrrid'],array('ctrrpfsectionid'=>$submitret['ctsid'],'ctrrstate'=>STATE_UPLOADED), 'ctrr', 'cartoonreleaserecordinfos');
        }
        else
        {
          $cart->update($info['ctrrid'],array('ctrrreason'=>$submitret['msg'], 'ctrrpfsectionid'=>$submitret['ctsid'],'ctrrstate'=>STATE_UPLOADFAIL), 'ctrr', 'cartoonreleaserecordinfos');
        }
      }
    }/*}}}*/
    elseif($pfid == SOURCE_AIQIYI)
    {/*{{{ 爱奇艺 */
      //$cookies = $info['ctrrcookies'];//test_account_for_iqiyi($username, $password);
      if(empty($csinfo['cssourceid']))
        continue;
      $exist = true;
      $cookies = '';
      if($upfinfo['upfcookiesstate'] == STATE_COOKIES){
        if(get_loginstate_for_iqiyi($upfinfo['upfcookies'])){
          $cookies = $upfinfo['upfcookies'];
        }else{
          $pf->update($upfinfo['upfid'], array('upfcookiesstate'=>STATE_INVALID,'upfupdatetime'=>date('Y-m-d H:i:s')), 'upf', 'userandplatforminfos');
          continue;
        }
      }else{
        $pf->update($upfinfo['upfid'], array('upfcookiesstate'=>STATE_INVALID,'upfupdatetime'=>date('Y-m-d H:i:s')), 'upf', 'userandplatforminfos');
        continue;
      }

      if($cookies && !empty($csinfo['cssourceid']))
      {
        $sectinfo['cssourceid'] = $csinfo['cssourceid'];
        $subinfo = createsection_for_iqiyi($sectinfo, $cookies);
        qLogInfo($logger, sprintf('createsection_for_iqiyi %s %s', json_encode($sectinfo), json_encode($subinfo)));
        if($subinfo['code'] === 0)
        {
          $ctsid = $subinfo['data']['chapterId'];
          $ctsbakid = $subinfo['data']['qipuId'];
          $cart->update($info['ctrrid'],array('ctrrpfsectionid'=>$ctsid,'ctrrpfsectionbakid'=>$ctsbakid,'ctrrstate'=>STATE_UPLOADED), 'ctrr', 'cartoonreleaserecordinfos');
        }
        else
        {
          $cart->update($info['ctrrid'],array('ctrrreason'=>$subinfo['msg'],'ctrrstate'=>STATE_UPLOADFAIL), 'ctrr', 'cartoonreleaserecordinfos');
        }
      }
    }/*}}}*/
    elseif($pfid == SOURCE_KUAIKAN)
    {/*{{{*/
      $succ = test_account_for_kuaikan($username, $password);
      if($succ){
        //$nctsinfo = $sect->GetSectInfoByCtsidAndPfid($ctsid, $pfid);
        //if(!empty($nctsinfo)){
        //  $sectinfo['ctscontent'] = $nctsinfo['ctscontent'];
        //  $sectinfo['ctscover'] = $nctsinfo['ctscover'];
        //  $sectinfo['ctsplatformcoverinfos'] = json_encode((object)array());
        //}
        $submitret = createsection_for_kuaikan($sectinfo);
        qLogInfo($logger, sprintf('createsection_for_kuaikan %s %s', json_encode($sectinfo), json_encode($submitret)));
        if($submitret['code'] == 200){
          $cart->update($info['ctrrid'],array('ctrrpfsectionid'=>$submitret['ctsid'],'ctrrstate'=>STATE_UPLOADED), 'ctrr', 'cartoonreleaserecordinfos');
        }else{
          $cart->update($info['ctrrid'],array('ctrrreason'=>$submitret['msg'],'ctrrstate'=>STATE_UPLOADFAIL), 'ctrr', 'cartoonreleaserecordinfos');
        }
      }else{
        $cart->update($info['ctrrid'],array('ctrrreason'=>'登录失败，请检查账号密码是否正确','ctrrstate'=>STATE_UPLOADFAIL), 'ctrr', 'cartoonreleaserecordinfos');
      }
    }/*}}}*/
  }
  /*}}}*/

}

function sync_tencent($uid, $cookies)
{/*{{{*/
  global $cart;

  $succ = false;
  $ctlist = get_cartoonlist_for_tencent($cookies);
  foreach($ctlist as $idx=>$row)
  {
    $succ = true;
    $csinfo = $cart->CartoonSelfExistForSourceAndSourceid(SOURCE_TENCENT, $row['ctsourceid']);
    if(empty($csinfo))
    {
      $ctinfo = $cart->CartoonSelfExistForName($row['ctname']);
      if(empty($ctinfo))
      {
        $ctinfo = array('ctname'=>$row['ctname'], 'cttype'=>TYPE_SOURCE_USER, 'ctsource'=>SOURCE_TENCENT, 'ctverticalimage'=>$row['ctverticalimage'], 'ctsourceid'=>$row['ctsourceid'],'uid'=>$uid,'ctauthorname'=>$row['ctauthorname'], 'ctimageauthor'=>$row['ctimageauthor'], 'cttextauthor'=>$row['cttextauthor'], 'ctdesc'=>$row['ctdesc'], 'ctprogress'=>($row['ctprogress']=='连载中')?PROGRESS_NORMAL:PROGRESS_OVER);
        $ctid = $cart->add($ctinfo);
        $ctinfo = $cart->find($ctid);
      }

      $ctid = $ctinfo['ctid'];
      $csinfo = array('ctid'=>$ctid, 'cssource'=>SOURCE_TENCENT, 'cssourceid'=>$row['ctsourceid']);
      $cart->add($csinfo,'cartoonsourceinfos');
    }

    foreach($row['sectionlist'] as $subrow)
    {
      $name = $subrow['ctsname'];
      $ctsinfo = $cart->SectionSelfExistForSourceAndSourceid($ctinfo['ctid'], SOURCE_TENCENT, $subrow['ctssourceid'], $name);
      if(empty($ctsinfo))
      {
        $ctsinfo = array('ctid'=>$ctinfo['ctid'], 'ctsname'=>$name, 'ctssource'=>SOURCE_TENCENT, 'ctssourceid'=>$subrow['ctssourceid']);
        $ctsid = $cart->add($ctsinfo, 'cartoonsectioninfos');
        $ctsinfo = $cart->find($ctsid,'cartoonsectioninfos','cts');
      }
      $ctsid = $ctsinfo['ctsid'];
      $ctrrinfo = $cart->GetReleseInfoBySectionAndPlatform($ctsinfo['ctsid'], SOURCE_TENCENT);
      if(empty($ctrrinfo))
      {
        $progress = 0;
        if($subrow['ctsprogress'] == '审核通过')
          $progress = STATE_OVER;
        $ctrrinfo = array('ctid'=>$ctinfo['ctid'], 'ctsid'=>$ctsinfo['ctsid'], 'pfid'=>SOURCE_TENCENT, 'upftype'=>2, 'ctrrpfsectionid'=>$subrow['ctssourceid'], 'ctrrtype'=>RELEASE_TYPE_PLAT, 'ctrrstate'=>$progress);
        $cart->add($ctrrinfo, 'cartoonreleaserecordinfos');
      }

    }
  }
  return $succ;
}/*}}}*/

function sync_aiqiyi($uid, $cookies)
{/*{{{*/
  global $cart;

  $succ = false;
  $ctlist = get_cartoonlist_for_iqiyi($cookies);
  foreach($ctlist as $idx=>$row)
  {
    $succ = true;
    $ctinfo = $cart->CartoonSelfExistForSourceAndSourceid(SOURCE_AIQIYI, $row['id']);
    if(empty($ctinfo))
    {
      $ctinfo = $cart->CartoonSelfExistForName($row['title']);
      if(empty($ctinfo))
      {
        $ctinfo = array('ctname'=>$row['title'], 'cttype'=>TYPE_SOURCE_USER, 'ctsource'=>SOURCE_AIQIYI, 'ctverticalimage'=>$row['coverImageUrl'], 'ctsourceid'=>$row['id'],'uid'=>$pasinfo['uid'],'ctauthorname'=>$row['cpBookAuthorName'], 'ctimageauthor'=>'', 'cttextauthor'=>'', 'ctdesc'=>'', 'ctprogress'=>PROGRESS_NORMAL);
        $ctid = $cart->add($ctinfo);
        $ctinfo = $cart->find($ctid);
      }

      $ctid = $ctinfo['ctid'];
      $csinfo = array('ctid'=>$ctid, 'cssource'=>SOURCE_AIQIYI, 'cssourceid'=>$row['id'], 'cssourcebakid'=>$row['qipuId']);
      $cart->add($csinfo,'cartoonsourceinfos');
    }

    foreach($row['sectionlist'] as $subrow)
    {
      $name = $subrow['title'];
      $ctsinfo = $cart->SectionSelfExistForSourceAndSourceid($ctinfo['ctid'], SOURCE_AIQIYI, $subrow['id'], $name);
      if(empty($ctsinfo))
      {
        $ctsinfo = array('ctid'=>$ctinfo['ctid'], 'ctsname'=>$name, 'ctssource'=>SOURCE_AIQIYI, 'ctssourceid'=>$subrow['id']);
        $ctsid = $cart->add($ctsinfo, 'cartoonsectioninfos');
        $ctsinfo = $cart->find($ctsid,'cartoonsectioninfos','cts');
      }
      $ctsid = $ctsinfo['ctsid'];
      $ctrrinfo = $cart->GetReleseInfoBySectionAndPlatform($ctsinfo['ctsid'], SOURCE_AIQIYI);
      if(empty($ctrrinfo))
      {
        $progress = 0;
        if($subrow['auditStatusName'] == '审核通过')
          $progress = STATE_OVER;
        $ctrrinfo = array('ctid'=>$ctinfo['ctid'], 'ctsid'=>$ctsinfo['ctsid'], 'pfid'=>SOURCE_AIQIYI, 'upftype'=>2, 'ctrrpfsectionid'=>$subrow['id'], 'ctrrpfsectionbakid'=>$subrow['qipuId'], 'ctrrtype'=>RELEASE_TYPE_PLAT, 'ctrrstate'=>$progress);
        $cart->add($ctrrinfo, 'cartoonreleaserecordinfos');
      }

    }
  }
  return $succ;
}/*}}}*/

if($exist)
  start_checkstate();
?>
