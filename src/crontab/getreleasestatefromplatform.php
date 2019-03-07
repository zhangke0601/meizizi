<?php
/*
 * 用途：查询漫画发布状态
 * 作者：feb1234@163.com
 * 时间：2017-11-22
 * */
$base_dir = dirname(__FILE__).'/../../';
require_once($base_dir.'config/config.php');
require_once($base_dir.'inc/util_for_simulate.php');
require_once($base_dir.'inc/util_for_meizizi.php');
require_once($base_dir.'model/clsUserinfos.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsPlatforminfos.php');

$logger = 'meizizicon.cron.getreleasestatefromplatform';
qlogConfig($base_dir.'config/qlog.cfg');
$user = new Userinfos();
$cart = new Cartooninfos();
$sect = new CartoonSectioninfos();
$pf = new Platforminfos();

$uinfos = $user->getinfos();
$pfinfos = $pf->getinfos();

foreach($uinfos as $uinfo)
{
  $uid = $uinfo['uid'];
  foreach($pfinfos as $pfinfo)
  {
    $pfid = $pfinfo['pfid'];
    $upfinfo = $pf->existUserAndPlatform($uid,$pfid);
    if(!empty($upfinfo))
    {
      $username = $upfinfo['upfusername'];
      $password = $upfinfo['upfpassword'];
      if(empty($username)){
        $username = $pfinfo['pfusername'];
        $password = $pfinfo['pfpassword'];
      }
      
      if($pfid == SOURCE_KUAIKAN)
      {/*{{{*/
        test_account_for_kuaikan($username,$password);
        $list = get_cartoonlist_for_kuaikan();
        foreach($list['data'] as $row)
        {
          $ctsourceid = $row['id'];
          if(empty($row['sectionlist']))
            continue;
          $csinfo = $cart->CartoonSelfExistForSourceAndSourceid($pfid, $ctsourceid);
          if($csinfo)
          {
            $ctinfo = $cart->find($csinfo['ctid']);
            foreach($row['sectionlist']['list'] as $subrow)
            {
              if($subrow['status'] == 'rejected')
              {
                $ctsinfo = $cart->SectionSelfExistForSourceAndSourceid($ctinfo['ctid'], SOURCE_KUAIKAN, $subrow['id'], $subrow['title']);
                if(!empty($ctsinfo))
                {
                  $ctrrinfo = $cart->GetReleseInfoBySectionAndPlatform($ctsinfo['ctsid'], SOURCE_KUAIKAN);
                  if(!empty($ctrrinfo))
                  {
                    $sectinfo = get_cartoonsectioninfo_for_kuaikan($subrow['id']);
                    $reason = array('comment'=>$sectinfo['comment']);
                    foreach($sectinfo['images'] as $r)
                    {
                      foreach($r as $r1)
                      {
                        if(!empty($r1['tags']))
                          $reason['tags'][] = $r1;
                      }
                    }
                    $cart->update($ctrrinfo['ctrrid'], array('ctrrstate'=>STATE_AUTHFAIL,'ctrrreason'=>json_encode($reason)), 'ctrr','cartoonreleaserecordinfos');
                  }
                }
              }
            }
          }
        }

      }/*}}}*/
      elseif($pfid == SOURCE_TENCENT)
      {/*{{{*/
        $cookies = $cart->GetLatestCookiesByUidAndPfid($uid, $pfid);
        if($cookies)
        {
          $list = get_cartoonlist_for_tencent($cookies);
          foreach($list as $cinfo)
          {
            $ctinfo = $cart->CartoonSelfExistForSourceAndSourceid($pfid, $cinfo['ctsourceid']);
            if(empty($ctinfo))
              continue;
            $ctid = $ctinfo['ctid'];
            foreach($cinfo['sectionlist'] as $subrow)
            {
              $ctsinfo = $cart->SectionSelfExistForSourceAndSourceid($ctinfo['ctid'], SOURCE_TENCENT, $subrow['ctssourceid'], $subrow['ctsname']);
              if(!empty($ctsinfo))
              {
                $ctrrinfo = $cart->GetReleseInfoBySectionAndPlatform($ctsinfo['ctsid'], SOURCE_TENCENT);
                if(!empty($ctrrinfo))
                {
                  if(($subrow['ctsprogress']=='审核通过') && ($ctrrinfo['ctrrstate']!=STATE_AUTHSUCC))
                  {
                    $cart->update($ctrrinfo['ctrrid'], array('ctrrstate'=>STATE_AUTHSUCC), 'ctrr', 'cartoonreleaserecordinfos');
                  }
                }
              }
            }
          }

        }
      }/*}}}*/
      elseif($pfid == SOURCE_WANGYI)
      {/*{{{*/
        test_account_for_wangyi($username, $password);
        $ctlist = get_cartoonlist_for_wangyi();
        $msgs = get_cartoonmessage_for_wangyi();
        $ctinfo = array();
        foreach($ctlist as $idx=>$row)
        {
          $csinfo = $cart->CartoonSelfExistForSourceAndSourceid(SOURCE_WANGYI, $row['ctsourceid']);
          if(!empty($csinfo))
          {
            $ctinfo = $cart->find($csinfo['ctid']);
            if(($row['ctstate1']=='已退稿') && ($csinfo['csstate']!=STATE_AUTHFAIL))
            {
              //$ctinfo = $cart->find($csinfo['ctid']);
              $reason = '';
              foreach($msgs as $msg)
              {
                if((strpos($msg['content'],'抱歉')===false) && (strpos($msg['content'],'系统拒绝')===false))
                  continue;

                if(strpos($msg['content'], $ctinfo['ctname']) !== false)
                {
                  $reason = $msg['content'];
                  break;
                }
              }
              $cart->update($csinfo['csid'], array('csstate'=>STATE_AUTHFAIL,'csreason'=>$reason), 'cs', 'cartoonsourceinfos');
              qLogInfo($logger, sprintf("update cartoonsourceinfos %d state=%d", $csinfo['csid'], STATE_AUTHFAIL));
            }
          }
          if(!empty($ctinfo)){
            foreach($row['sectionlist'] as $subrow){
              $name = $subrow['ctsname'];
              $ctsinfo = $cart->SectionSelfExistForSourceAndSourceid($ctinfo['ctid'], SOURCE_WANGYI, $subrow['ctssourceid'], $name);
              if($ctsinfo){
                if(strpos($subrow['ctsprogress'], '审核拒') !== false){
                  $ctsid = $ctsinfo['ctsid'];
                  $ctrrinfo = $cart->GetReleseInfoBySectionAndPlatform($ctsinfo['ctsid'], SOURCE_WANGYI);
                  if($ctrrinfo){
                    foreach($msgs as $msg){
                      if(strpos($msg['content'],'已审核通过') !== false)
                        continue;
                      if(strpos($msg['content'], $name) !== false)
                      {
                        $reason = $msg['content'];
                        $cart->update($ctrrinfo['ctrrid'], array('ctrrstate'=>STATE_AUTHFAIL,'ctrrreason'=>$reason), 'ctrr','cartoonreleaserecordinfos');
                        break;
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }/*}}}*/
      elseif($pfid == SOURCE_ZHANGYUE)
      {/*{{{*/
        $succ = test_account_for_zhangyue($username,$password);
        if($succ)
        {
          $ctlist = get_cartoonlist_for_zhangyue();
          foreach($ctlist['data']['rows'] as $idx=>$row)
          {
            $ctinfo = $cart->CartoonSelfExistForSourceAndSourceid(SOURCE_ZHANGYUE, $row['book_id']);
            if(!empty($ctinfo))
            {
              foreach($row['sectionlist']['data']['rows'] as $subrow)
              {
                $ctsinfo = $cart->SectionSelfExistForSourceAndSourceid($ctinfo['ctid'], SOURCE_ZHANGYUE, $subrow['id'], $subrow['chapter_name']);
                if(!empty($ctsinfo))
                {
                  $ctrrinfo = $cart->GetReleseInfoBySectionAndPlatform($ctsinfo['ctsid'], SOURCE_ZHANGYUE);
                  if(!empty($ctrrinfo))
                  {
                    if(($subrow['status']==0) && ($ctrrinfo['ctrrstate']!=STATE_AUTHSUCC))
                    {
                      $cart->update($ctrrinfo['ctrrid'], array('ctrrstate'=>STATE_AUTHSUCC), 'ctrr', 'cartoonreleaserecordinfos');
                    }
                  }
                }
              }
            }
          }
        }
      }/*}}}*/
      elseif($pfid == SOURCE_MANHUADAO)
      {/*{{{*/
        $succ = test_account_for_manhuadao($username,$password);
        if($succ)
        {
          $ctlist = get_cartoonlist_for_manhuadao();
          foreach($ctlist as $idx=>$row)
          {
            $ctinfo = $cart->CartoonSelfExistForSourceAndSourceid(SOURCE_MANHUADAO, $row['ctsourceid']);
            if(!empty($ctinfo))
            {
              foreach($row['sectionlist'] as $subrow)
              {
                $ctsinfo = $cart->SectionSelfExistForSourceAndSourceid($ctinfo['ctid'], SOURCE_MANHUADAO, $subrow['ctssourceid'], $subrow['ctsname']);
                if(!empty($ctsinfo))
                {
                  $ctrrinfo = $cart->GetReleseInfoBySectionAndPlatform($ctsinfo['ctsid'], SOURCE_MANHUADAO);
                  if(!empty($ctrrinfo))
                  {
                    if(($subrow['ctsstatus']=='通过') && ($ctrrinfo['ctrrstate']!=STATE_AUTHSUCC))
                    {
                      $cart->update($ctrrinfo['ctrrid'], array('ctrrstate'=>STATE_AUTHSUCC), 'ctrr', 'cartoonreleaserecordinfos');
                    }
                  }
                }
              }
            }
          }
        }
      }/*}}}*/
      elseif($pfid == SOURCE_AIQIYI)
      {/*{{{*/
        $cookies = $cart->GetLatestCookiesByUidAndPfid($uid, $pfid);
        if($cookies)
        {
          $list = get_cartoonlist_for_iqiyi($cookies);
          foreach($list as $row)
          {
            $ctinfo = $cart->CartoonSelfExistForSourceAndSourceid(SOURCE_AIQIYI, $row['id']);
            if(!empty($ctinfo))
            {
              if($row['status'] == 1) //已上线
              {
                foreach($row['sectionlist'] as $subrow)
                {
                  $name = $subrow['title'];
                  $ctsinfo = $cart->SectionSelfExistForSourceAndSourceid($ctinfo['ctid'], SOURCE_AIQIYI, $subrow['id'], $name);
                  if(!empty($ctsinfo))
                  {
                    $ctsid = $ctsinfo['ctsid'];
                    $ctrrinfo = $cart->GetReleseInfoBySectionAndPlatform($ctsinfo['ctsid'], SOURCE_AIQIYI);
                    if(!empty($ctrrinfo))
                    {
                      if(($subrow['auditStatusName']=='审核通过') && ($ctrrinfo['ctrrstate']!=STATE_AUTHSUCC))
                      {
                        $cart->update($ctrrinfo['ctrrid'], array('ctrrstate'=>STATE_AUTHSUCC), 'ctrr', 'cartoonreleaserecordinfos');
                      }
                      
                      if(($subrow['publishStatus']==1) && ($ctrrinfo['ctrrstate']!=STATE_OVER))
                      {
                        $cart->update($ctrrinfo['ctrrid'], array('ctrrstate'=>STATE_OVER), 'ctrr', 'cartoonreleaserecordinfos');
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }/*}}}*/
    }
  }
}
 

/*{{{ 更新漫画状态 */
$csinfos = $cart->GetUnCartoonSourceInfos();
foreach($csinfos as $csinfo)
{
  $ctid = $csinfo['ctid'];
  $pfid = $csinfo['cssource'];
  $pfinfo  = $cart->find($pfid,'platforminfos','pf');
  $ctinfo = $cart->find($csinfo['ctid']);
  $upfinfo = $pf->existUserAndPlatform($ctinfo['uid'], $pfid);
  //$upfinfo = $cart->find($upfid,'userandplarforminfos','upf');
  $username = $upfinfo['upfusername'];
  $password = $upfinfo['upfpassword'];
  if(empty($username)){
    $username = $pfinfo['pfusername'];
    $password = $pfinfo['pfpassword'];
  }
  if($csinfo['csstate'] == STATE_UPLOADED)
  {
    if($pfid == SOURCE_ZHANGYUE)
    {/*{{{*/
      test_account_for_zhangyue($username,$password);
      $cartlist = get_cartoonlist_for_zhangyue();
      foreach($cartlist['data']['rows'] as $idx=>$row)
      {
        if($ctinfo['ctname'] == $row['book_name'])
        {
          if(($csinfo['csstate'] < STATE_AUTHSUCC) && (!empty($row['book_id']))){
            $cart->update($csinfo['csid'],array('csstate'=>STATE_AUTHSUCC,'cssourceid'=>$row['book_id'],'cssourcebakid'=>$csinfo['cssourceid']),'cs','cartoonsourceinfos');
            $ctimginfos = $cart->getinfos(sprintf('ccpstate=%d and ctid=%d', STATE_NOR, $ctid),'cartooncoverandplatforminfos');
            $ctimginfos = SetKeyFromArray($ctimginfos,'pfid');
            $ctinfo['ctverticalimage'] = $ctimginfos[SOURCE_ZHANGYUE]['ccpverticalimg'];
            $ctinfo['cssourcebakid'] = $csinfo['cssourcebakid'];
            $submitret = updatecartooncover_for_zhangyue($ctinfo);
            qLogInfo($logger, sprintf("updatecartooncover_for_zhangyue %d %s", $csinfo['ctid'], json_encode($submitret)));
            break;
          }
        }
      }
    }/*}}}*/
    elseif($pfid == SOURCE_KUAIKAN)
    {/*{{{*/
    }/*}}}*/
    elseif($pfid == SOURCE_MANHUADAO)
    {/*{{{*/
    }/*}}}*/
    elseif($pfid == SOURCE_BUKA)
    {/*{{{*/
    }/*}}}*/
    elseif($pfid == SOURCE_TENCENT)
    {/*{{{*/
    }/*}}}*/
    elseif($pfid == SOURCE_AIQIYI)
    {/*{{{*/
    }/*}}}*/
    elseif($pfid == SOURCE_WANGYI)
    {/*{{{*/
      test_account_for_wangyi($username, $password);
      $ctlist = get_cartoonlist_for_wangyi();
      foreach($ctlist as $idx=>$row)
      {
        $ctinfo = $cart->CartoonSelfExistForSourceAndSourceid(SOURCE_WANGYI, $row['ctsourceid']);
        if(!empty($ctinfo))
        {
        }
      }
    }/*}}}*/
    elseif($pfid == SOURCE_U17)
    {/*{{{*/
    }/*}}}*/
  }
}
/*}}}*/

/*{{{ 更新章节状态 */
$ctrrinfos = $cart->GetUnReleaseRecordInfos();
foreach($ctrrinfos as $info)
{
  $ctid  = $info['ctid'];
  $ctsid = $info['ctsid'];
  $pfid  = $info['pfid'];
  $upfid = $info['upfid'];
  $pfinfo  = $cart->find($pfid,'platforminfos','pf');
  $upfinfo = $cart->find($upfid,'userandplarforminfos','upf');
  $username = $upfinfo['upfusername'];
  $password = $upfinfo['upfpassword'];
  if(empty($username)){
    $username = $pfinfo['pfusername'];
    $password = $pfinfo['pfpassword'];
  }
  $csinfo = $cart->GetCartoonReleaseInfo($ctid, $pfid);



}
/*}}}*/

start_checkstate();

?>
