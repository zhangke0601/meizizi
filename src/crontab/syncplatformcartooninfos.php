<?php
/*
 * 用途：查询同步漫画平台漫画信息
 * 作者：feb1234@163.com
 * 时间：2017-12-29
 * */
$base_dir = dirname(__FILE__).'/../../';
require_once($base_dir.'config/config.php');
require_once($base_dir.'inc/util_for_simulate.php');
require_once($base_dir.'inc/util_for_meizizi.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsPlatforminfos.php');

$logger = 'meizizicon.cron.syncplatform';
qlogConfig($base_dir.'config/qlog.cfg');
$cart = new Cartooninfos();
$sect = new CartoonSectioninfos();
$pf = new Platforminfos();

$pasinfos = $pf->GetUnprocPlatformSyncInfos();
foreach($pasinfos as $idx=>$pasinfo)
{
  $cookies = $pasinfo['pascookies'];
  $pfid = $pasinfo['pfid'];
  $uid = $pasinfo['uid'];

  if($pfid == SOURCE_TENCENT)
  {/*{{{*/
    $cookies = '';
    $upfinfo = $pf->existUserAndPlatform($uid,$pfid);
    if($upfinfo['upfcookiesstate'] == STATE_COOKIES){
      if(get_loginstate_for_tencent($upfinfo['upfcookies']))
        $cookies = $upfinfo['upfcookies'];
      else
        $pf->update($upfinfo['upfid'], array('upfcookiesstate'=>STATE_INVALID,'upfupdatetime'=>date('Y-m-d H:i:s')), 'upf', 'userandplatforminfos');
    }else{
      $pf->update($upfinfo['upfid'], array('upfcookiesstate'=>STATE_INVALID,'upfupdatetime'=>date('Y-m-d H:i:s')), 'upf', 'userandplatforminfos');
      continue;
    }
    if(!empty($cookies))
    {
      $ctlist = get_cartoonlist_for_tencent($cookies);
      foreach($ctlist as $idx=>$row)
      {
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
          $oldcsinfo = $cart->CartoonSelfExistForCtidAndSource($ctid, $pfid);
          if(empty($oldcsinfo)){
            $csinfo['csstate'] = STATE_UPLOADED;
            $cart->add($csinfo,'cartoonsourceinfos');
          }else{
            if(empty($oldcsinfo['cssourceid'])){
              unset($csinfo['ctid']);
              $cart->update($oldcsinfo['csid'], $csinfo);
            }
          }
        }

        foreach($row['sectionlist'] as $subrow)
        {
          $name = $subrow['ctsname'];
          $ctsinfo = $cart->SectionSelfExistForSourceAndSourceid($csinfo['ctid'], SOURCE_TENCENT, $subrow['ctssourceid'], $name);
          if(empty($ctsinfo))
          {
            $ctsinfo = array('ctid'=>$csinfo['ctid'], 'ctsname'=>$name, 'ctssource'=>SOURCE_TENCENT, 'ctssourceid'=>$subrow['ctssourceid']);
            $ctsid = $cart->add($ctsinfo, 'cartoonsectioninfos');
            $ctsinfo = $cart->find($ctsid,'cartoonsectioninfos','cts');
          }
          $ctsid = $ctsinfo['ctsid'];
          $ctrrinfo = $cart->GetReleseInfoBySectionAndPlatform($ctsinfo['ctsid'], SOURCE_TENCENT);
          if(empty($ctrrinfo)){
            $progress = STATE_UPLOADED;
            if($subrow['ctsprogress'] == '审核通过')
              $progress = STATE_OVER;
            $ctrrinfo = array('ctid'=>$csinfo['ctid'], 'ctsid'=>$ctsinfo['ctsid'], 'pfid'=>SOURCE_TENCENT, 'upftype'=>2, 'ctrrpfsectionid'=>$subrow['ctssourceid'], 'ctrrtype'=>RELEASE_TYPE_PLAT, 'ctrrstate'=>$progress);
            $cart->add($ctrrinfo, 'cartoonreleaserecordinfos');
          }else{
            $progress = STATE_UPLOADED;
            if($subrow['ctsprogress'] == '审核通过')
              $progress = STATE_OVER;
            $cart->update($ctrrinfo['ctrrid'], array('ctrrpfsectionid'=>$subrow['ctssourceid'], 'ctrrstate'=>$progress),'ctrr','cartoonreleaserecordinfos');
          }
        }
      }
    }
  }/*}}}*/
  elseif($pfid == SOURCE_AIQIYI)
  {/*{{{*/
    $cookies = '';
    $upfinfo = $pf->existUserAndPlatform($uid,$pfid);
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
      $ctlist = get_cartoonlist_for_iqiyi($cookies);
      foreach($ctlist as $idx=>$row)
      {
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
          $oldcsinfo = $cart->CartoonSelfExistForCtidAndSource($ctid, $pfid);
          if(empty($oldcsinfo)){
            $csinfo['csstate'] = STATE_UPLOADED;
            $cart->add($csinfo,'cartoonsourceinfos');
          }else{
            if(empty($oldcsinfo['cssourceid'])){
              unset($csinfo['ctid']);
              $cart->update($oldcsinfo['csid'], $csinfo);
            }
          }
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
          if(empty($ctrrinfo)){
            $progress = STATE_UPLOADED;
            if($subrow['auditStatusName'] == '审核通过')
              $progress = STATE_OVER;
            $ctrrinfo = array('ctid'=>$ctinfo['ctid'], 'ctsid'=>$ctsinfo['ctsid'], 'pfid'=>SOURCE_AIQIYI, 'upftype'=>2, 'ctrrpfsectionid'=>$subrow['id'], 'ctrrpfsectionbakid'=>$subrow['qipuId'], 'ctrrtype'=>RELEASE_TYPE_PLAT, 'ctrrstate'=>$progress);
            $cart->add($ctrrinfo, 'cartoonreleaserecordinfos');
          }else{
            $progress = STATE_UPLOADED;
            if($subrow['auditStatusName'] == '审核通过')
              $progress = STATE_OVER;
            $cart->update($ctrrinfo['ctrrid'], array( 'ctrrpfsectionid'=>$subrow['id'], 'ctrrpfsectionbakid'=>$subrow['qipuId'], 'ctrrstate'=>$progress), 'ctrr', 'cartoonreleaserecordinfos');

          }
        }
      }
    }
  }/*}}}*/
}

start_checkstate();

?>
