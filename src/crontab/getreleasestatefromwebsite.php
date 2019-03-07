<?php
/*
 * 用途：查询漫画发布状态：从网站上获取发布状态
 * 作者：feb1234@163.com
 * 时间：2018-01-18
 * */
$base_dir = dirname(__FILE__).'/../../';
require_once($base_dir.'config/config.php');
require_once($base_dir.'inc/util_for_simulate.php');
require_once($base_dir.'inc/util_for_shortmsg.php');
require_once($base_dir.'model/clsUserinfos.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsPlatforminfos.php');

$logger = 'meizizicon.cron.getreleasestatefromwebsite';
qlogConfig($base_dir.'config/qlog.cfg');
$user = new Userinfos();
$cart = new Cartooninfos();
$sect = new CartoonSectioninfos();
$pf = new Platforminfos();

$ctinfos = $cart->GetAllSelfInfos();

foreach($ctinfos as $ctinfo)
{
  $ctid = $ctinfo['ctid'];
  $ctinfo = $cart->find($ctid);
  $csinfos = $cart->GetCartoonReleaseInfosByCtid($ctid);

  foreach($csinfos as $csinfo)
  {
    $pfid = $csinfo['cssource'];
    $cssourceid = $csinfo['cssourceid'];
    if(empty($cssourceid))
      continue;
    if($pfid != SOURCE_WANGYI)
      continue;

    if($pfid == SOURCE_KUAIKAN)
    {/*{{{*/
    }/*}}}*/
    elseif($pfid == SOURCE_TENCENT)
    {/*{{{*/
      $pcsinfo = get_cartooninfo_for_tencent_from_website($csinfo['cssourceid']);
      foreach($pcsinfo['sectionlist'] as $subrow)
      {
        $ctsinfo = $cart->SectionSelfExistForSourceAndSourceid($ctid, SOURCE_TENCENT, $subrow['ctssourceid'], $subrow['ctsname']);
        if(!empty($ctsinfo))
        {
          $ctrrinfo = $cart->GetReleseInfoBySectionAndPlatform($ctsinfo['ctsid'], SOURCE_TENCENT);
          if(!empty($ctrrinfo))
          {
            if($ctrrinfo['ctrrstate']!=STATE_OVER)
            {
              $cart->update($ctrrinfo['ctrrid'], array('ctrrstate'=>STATE_OVER), 'ctrr', 'cartoonreleaserecordinfos');
            }
          }
        }
      }
    }/*}}}*/
    elseif($pfid == SOURCE_WANGYI)
    {/*{{{*/
      $pcsinfo = get_cartooninfo_for_wangyi_from_website($cssourceid);
      if(!empty($pcsinfo))
      {
        foreach($pcsinfo['sectionlist'] as $subrow)
        {
          $ctsinfo = $cart->SectionSelfExistForSourceAndSourceid($ctid, SOURCE_WANGYI, $subrow['sectionId'], $subrow['title']);
          if(!empty($ctsinfo))
          {
            $ctrrinfo = $cart->GetReleseInfoBySectionAndPlatform($ctsinfo['ctsid'], SOURCE_WANGYI);
            if(!empty($ctrrinfo))
            {
              if($ctrrinfo['ctrrstate']!=STATE_OVER)
              {
                $cart->update($ctrrinfo['ctrrid'], array('ctrrstate'=>STATE_OVER), 'ctrr', 'cartoonreleaserecordinfos');
              }
            }
          }
        }
      }
    }/*}}}*/
    elseif($pfid == SOURCE_ZHANGYUE)
    {/*{{{*/
      ;
    }/*}}}*/
    elseif($pfid == SOURCE_MANHUADAO)
    {/*{{{*/
    }/*}}}*/
    elseif($pfid == SOURCE_AIQIYI)
    {/*{{{*/
      $pcsinfo = get_cartooninfo_for_iqiyi_from_website($ctinfo['ctname'], $ctinfo['ctauthorname']);
      if(!empty($pcsinfo))
      {
        $ctrrinfos = $cart->GetReleaseRecordInfosByCtidAndPfid($ctid, $pfid);
        foreach($ctrrinfos as $ctrrinfo)
        {
          if($ctrrinfo['ctrrstate'] != STATE_OVER)
          {
            $ctsid = $ctrrinfo['ctsid'];
            $ctsinfo = $sect->find($ctsid);
            foreach($pcsinfo['sectionlist'] as $seinfo)
            {
              if(trim($seinfo['ctsname']) == $ctsinfo['ctsname'])
              {
                $cart->update($ctrrinfo['ctrrid'], array('ctrrstate'=>STATE_OVER), 'ctrr', 'cartoonreleaserecordinfos');
                break;
              }
            }
          }
        }
      }
    }/*}}}*/
  }
}
 

$cmd = 'df -h | head -2 | tail -1 | awk \'{print $5}\'';
$strret = exec($cmd);
$strret = trim($strret,'%');
if($strret >= 95)
{
  $mobile = '15910350235';
  $ret = ShortMsgForYinghuaAtCipanFull($mobile);
  qLogInfo($logger, sprintf("%s %s", $mobile, $ret));
}

?>
