<?php
/*
 * 用途：处理前端用户请求
 * 作者：feb1234@163.com
 * 时间：2017-09-09
 * */
$base_dir = dirname(__FILE__).'/../../../';
require_once($base_dir.'inc/init.php');
require_once($base_dir.'inc/PHPExcel/PHPExcel.php');
require_once($base_dir.'inc/util_for_shortmsg.php');
require_once($base_dir.'inc/util_for_simulate.php');
require_once($base_dir.'config/config.php');
require_once($base_dir.'model/clsUserinfos.php');
require_once($base_dir.'model/clsPlatforminfos.php');
require_once($base_dir.'model/clsCartooninfos.php');

$ajaxret = array('retno'=>RETNO_FAIL);
$logger = 'meizizi.admin.platform';
qLogConfig($base_dir.'config/qlog.cfg');

$params = $_POST;
qLogInfo($logger, json_encode($params));
$type = GetItemFromArray($params, 'type');
$user = new Userinfos();
$cart = new Cartooninfos();
$plat = new Platforminfos();
$uinfo = $user->IsLogin();
if($uinfo !== false)
{
  $uid = $uinfo['uid'];
  if($type == 'getplatforminfos')
  {/*{{{*/
    $pfinfos = $plat->getinfos();
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $pfinfos;
  }/*}}}*/
  elseif($type == 'getuserplatforminfos')
  {/*{{{*/
    $upfinfos = $plat->getUserAndPlatforminfos($uid);
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $upfinfos;
  }/*}}}*/
  elseif($type == 'saveuserplatforminfos')
  {/*{{{*/
    $pfinfos = json_decode($params['pfinfos'], true);
    foreach($pfinfos as $row)
    {
      $upfinfo = $plat->existUserAndPlatform($uid, $row['pfid']);
      if(empty($upfinfo))
      {
        $data = $row;
        $data['uid'] = $uid;
        $plat->add($data,'userandplatforminfos');
      }
      else
      {
        $plat->update($upfinfo['upfid'], $row, 'upf');
      }
    }
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = true;
  }/*}}}*/
  elseif($type == 'addplatforminfo')
  {/*{{{*/
    unset($params['type']);unset($params['pfid']);
    $pfid = $plat->add($params);
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $pfid;
  }/*}}}*/
  elseif($type == 'updateplatforminfo')
  {/*{{{*/
    $pfid = GetItemFromArray($params,'pfid');
    unset($params['type']);unset($params['pfid']);
    $succ = $plat->update($pfid,$params);
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $succ;
  }/*}}}*/
  elseif($type == 'getplatforminfobypfid')
  {/*{{{*/
    $pfid = GetItemFromArray($params,'pfid');
    $pfinfo = $plat->find($pfid);
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $pfinfo;
  }/*}}}*/
  elseif($type == 'deleteplatform')
  {/*{{{*/
    $pfid = GetItemFromArray($params,'pfid');
    $succ = $plat->setstate($pfid, STATE_DEL);
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $succ;
  }/*}}}*/
  elseif($type == 'testforaccount')
  {/*{{{*/
    $pfid = GetItemFromArray($params,'pfid');
    $pfusername = GetItemFromArray($params,'pfusername');
    $pfpassword = GetItemFromArray($params,'pfpassword');
    if($pfid == SOURCE_ZHANGYUE)
    {
      $succ = test_account_for_zhangyue($pfusername,$pfpassword);
      if($succ)
        $ajaxret['msg'] = '测试成功';
      else
        $ajaxret['msg'] = '账号或密码不正确';
    }
    elseif($pfid == SOURCE_KUAIKAN)
    {
      $succ = test_account_for_kuaikan($pfusername,$pfpassword);
      if($succ)
        $ajaxret['msg'] = '测试成功';
      else
        $ajaxret['msg'] = '账号或密码不正确';
    }
    elseif($pfid == SOURCE_MANHUADAO)
    {
      $succ = test_account_for_manhuadao($pfusername,$pfpassword);
      if($succ)
        $ajaxret['msg'] = '测试成功';
      else
        $ajaxret['msg'] = '账号或密码不正确';
    }
    elseif($pfid == SOURCE_BUKA)
    {
      $succ = test_account_for_buka($pfusername,$pfpassword);
      if($succ)
        $ajaxret['msg'] = '测试成功';
      else
        $ajaxret['msg'] = '账号或密码不正确';
    }
    elseif($pfid == SOURCE_WANGYI)
    {
      $succ = test_account_for_wangyi($pfusername, $pfpassword);
      if($succ)
        $ajaxret['msg'] = '测试成功';
      else
        $ajaxret['msg'] = '账号或密码不正确';
    }
    elseif($pfid == SOURCE_TENCENT)
    {
      /*$succ = test_account_for_tencent($pfusername, $pfpassword);
      if($succ)
        $ajaxret['msg'] = '测试成功';
      else
        $ajaxret['msg'] = '账号或密码不正确';*/
      $ajaxret['msg'] = '该平台不支持测试';
    }
    else
    {
      $ajaxret['msg'] = '该平台不支持';
    }
  }/*}}}*/
  elseif($type == 'synccomic')
  {/*{{{*/
    $pfid = GetItemFromArray($params,'pfid');
    $pfusername = GetItemFromArray($params,'pfusername');
    $pfpassword = GetItemFromArray($params,'pfpassword');
    if($pfid == 1)
    {/*{{{*/
      $succ = test_account_for_zhangyue($pfusername,$pfpassword);
      if($succ)
      {
        $ctlist = get_cartoonlist_for_zhangyue();
        foreach($ctlist['data']['rows'] as $idx=>$row)
        {
          if($row['book_id'] == 0)
            $row['book_id'] = $row['id'];
          $ctinfo = $cart->CartoonSelfExistForSourceAndSourceid(SOURCE_ZHANGYUE, $row['book_id']);
          if(empty($ctinfo))
          {
            $ctinfo = $cart->CartoonSelfExistForName($row['book_name']);
            if(empty($ctinfo))
            {
              $ctinfo = array('ctname'=>$row['book_name'], 'cttype'=>TYPE_SOURCE_USER, 'ctsource'=>SOURCE_ZHANGYUE, 'ctsourceid'=>$row['book_id'],'uid'=>$uid,'ctauthorname'=>$row['author_name'], 'ctprogress'=>($row['continued']==0)?PROGRESS_NORMAL:PROGRESS_OVER);
              $ctid = $cart->add($ctinfo);
              $ctinfo = $cart->find($ctid);
            }

            $ctid = $ctinfo['ctid'];
            $csinfo = array('ctid'=>$ctid, 'cssource'=>SOURCE_ZHANGYUE, 'cssourceid'=>$row['book_id'],'csstate'=>STATE_UPLOADED);
            $cart->add($csinfo,'cartoonsourceinfos');
          }

          foreach($row['sectionlist']['data']['rows'] as $subrow)
          {
            $ctsinfo = $cart->SectionSelfExistForSourceAndSourceid($ctinfo['ctid'], SOURCE_ZHANGYUE, $subrow['id'], $subrow['chapter_name']);
            if(empty($ctsinfo))
            {
              $ctsinfo = array('ctid'=>$ctinfo['ctid'], 'ctsname'=>$subrow['chapter_name'], 'ctssource'=>SOURCE_ZHANGYUE, 'ctssourceid'=>$subrow['id']);
              $ctsid = $cart->add($ctsinfo, 'cartoonsectioninfos');
              $ctsinfo = $cart->find($ctsid,'cartoonsectioninfos','cts');
            }
            $ctrrinfo = $cart->GetReleseInfoBySectionAndPlatform($ctsinfo['ctsid'], SOURCE_ZHANGYUE);
            if(empty($ctrrinfo))
            {
              $ctrrstate = STATE_UPLOADED;
              if($subrow['status'] == 0)
                $ctrrstate = STATE_UPLOADED;
              elseif($subrow['status'] == 1)
                $ctrrstate = STATE_OVER;
              $ctrrinfo = array('ctid'=>$ctinfo['ctid'], 'ctsid'=>$ctsinfo['ctsid'], 'pfid'=>SOURCE_ZHANGYUE, 'upftype'=>2, 'ctrrpfsectionid'=>$subrow['id'], 'ctrrtype'=>RELEASE_TYPE_PLAT, 'ctrrstate'=>$ctrrstate);
              $cart->add($ctrrinfo, 'cartoonreleaserecordinfos');
            }

          }
        }
        $ajaxret['retno'] = RETNO_SUCC;
      }
      else
        $ajaxret['msg'] = '登录失败';
    }/*}}}*/
    elseif($pfid == 2)
    {/*{{{*/
      $succ = test_account_for_kuaikan($pfusername,$pfpassword);
      if($succ)
      {
        $ctlist = get_cartoonlist_for_kuaikan();
        foreach($ctlist['data'] as $idx=>$row)
        {
          $ctinfo = $cart->CartoonSelfExistForSourceAndSourceid(SOURCE_KUAIKAN, $row['id']);
          if(empty($ctinfo))
          {
            $ctinfo = $cart->CartoonSelfExistForName($row['title']);
            if(empty($ctinfo))
            {
              $ctinfo = array('ctname'=>$row['title'], 'cttype'=>TYPE_SOURCE_USER, 'ctsource'=>SOURCE_KUAIKAN, 'ctverticalimage'=>$row['cover_image_url'], 'ctsourceid'=>$row['id'],'uid'=>$uid,'ctauthorname'=>$row['author_name'], 'ctprogress'=>($row['update_status']=='连载中')?PROGRESS_NORMAL:PROGRESS_OVER);
              $ctid = $cart->add($ctinfo);
              $ctinfo = $cart->find($ctid);
            }

            $ctid = $ctinfo['ctid'];
            $csinfo = array('ctid'=>$ctid, 'cssource'=>SOURCE_KUAIKAN, 'cssourceid'=>$row['id'],'csstate'=>STATE_UPLOADED);
            $cart->add($csinfo,'cartoonsourceinfos');
          }

          foreach($row['sectionlist']['list'] as $subrow)
          {
            $ctsinfo = $cart->SectionSelfExistForSourceAndSourceid($ctinfo['ctid'], SOURCE_KUAIKAN, $subrow['id'], $subrow['title']);
            if(empty($ctsinfo))
            {
              $ctsinfo = array('ctid'=>$ctinfo['ctid'], 'ctsname'=>$subrow['title'], 'ctssource'=>SOURCE_KUAIKAN, 'ctssourceid'=>$subrow['id']);
              $ctsid = $cart->add($ctsinfo, 'cartoonsectioninfos');
              $ctsinfo = $cart->find($ctsid,'cartoonsectioninfos','cts');
            }
            $ctrrinfo = $cart->GetReleseInfoBySectionAndPlatform($ctsinfo['ctsid'], SOURCE_KUAIKAN);
            if(empty($ctrrinfo))
            {
              $ctrrstate = STATE_UPLOADED;
              if($subrow['status'] == '')
                $ctrrstate = STATE_UPLOADED;
              elseif($subrow['status'] == 'published')
                $ctrrstate = STATE_OVER;
              $ctrrinfo = array('ctid'=>$ctinfo['ctid'], 'ctsid'=>$ctsinfo['ctsid'], 'pfid'=>SOURCE_KUAIKAN, 'upftype'=>2, 'ctrrpfsectionid'=>$subrow['id'], 'ctrrtype'=>RELEASE_TYPE_PLAT, 'ctrrstate'=>$ctrrstate);
              $cart->add($ctrrinfo, 'cartoonreleaserecordinfos');
            }

          }
        }
        $ajaxret['retno'] = RETNO_SUCC;
      }
      else
        $ajaxret['msg'] = '登录失败';
    }/*}}}*/
    elseif($pfid == 3)
    {/*{{{*/
      $succ = test_account_for_manhuadao($pfusername,$pfpassword);
      if($succ)
      {
        $ctlist = get_cartoonlist_for_manhuadao();
        foreach($ctlist as $idx=>$row)
        {
          $ctinfo = $cart->CartoonSelfExistForSourceAndSourceid(SOURCE_MANHUADAO, $row['ctsourceid']);
          if(empty($ctinfo))
          {
            $ctinfo = $cart->CartoonSelfExistForName($row['ctname']);
            if(empty($ctinfo))
            {
              $ctinfo = array('ctname'=>$row['ctsname'], 'cttype'=>TYPE_SOURCE_USER, 'ctsource'=>SOURCE_MANHUADAO, 'ctverticalimage'=>'', 'ctsourceid'=>$row['ctsourceid'],'uid'=>$uid,'ctauthorname'=>$row['ctauthorname'], 'ctprogress'=>($row['ctprogress']=='连载中')?PROGRESS_NORMAL:PROGRESS_OVER);
              $ctid = $cart->add($ctinfo);
              $ctinfo = $cart->find($ctid);
            }

            $ctid = $ctinfo['ctid'];
            $csinfo = array('ctid'=>$ctid, 'cssource'=>SOURCE_MANHUADAO, 'cssourceid'=>$row['ctsourceid'],'csstate'=>STATE_UPLOADED);
            $cart->add($csinfo,'cartoonsourceinfos');
          }

          foreach($row['sectionlist'] as $subrow)
          {
            $ctsinfo = $cart->SectionSelfExistForSourceAndSourceid($ctinfo['ctid'], SOURCE_MANHUADAO, $subrow['ctssourceid'], $subrow['ctsname']);
            if(empty($ctsinfo))
            {
              $ctsinfo = array('ctid'=>$ctinfo['ctid'], 'ctsname'=>$subrow['ctsname'], 'ctssource'=>SOURCE_MANHUADAO, 'ctssourceid'=>$subrow['ctssourceid']);
              $ctsid = $cart->add($ctsinfo, 'cartoonsectioninfos');
              $ctsinfo = $cart->find($ctsid,'cartoonsectioninfos','cts');
            }
            $ctrrinfo = $cart->GetReleseInfoBySectionAndPlatform($ctsinfo['ctsid'], SOURCE_MANHUADAO);
            if(empty($ctrrinfo))
            {
              $ctrrstate = STATE_UPLOADED;
              if($subrow['ctsstatus'] == '通过')
                $ctrrstate = STATE_UPLOADED;
              $ctrrinfo = array('ctid'=>$ctinfo['ctid'], 'ctsid'=>$ctsinfo['ctsid'], 'pfid'=>SOURCE_MANHUADAO, 'upftype'=>2, 'ctrrpfsectionid'=>$subrow['ctssourceid'], 'ctrrtype'=>RELEASE_TYPE_PLAT, 'ctrrstate'=>$ctrrstate);
              $cart->add($ctrrinfo, 'cartoonreleaserecordinfos');
            }

          }
        }
        $ajaxret['ctlist'] = $ctlist;
        $ajaxret['retno'] = RETNO_SUCC;
      }
      else
        $ajaxret['msg'] = '登录失败';
    }/*}}}*/
    elseif($pfid == 4)
    {/*{{{*/
      $succ = test_account_for_buka($pfusername,$pfpassword);
      if($succ)
      {
        $ctlist = get_cartoonlist_for_buka();
        foreach($ctlist as $idx=>$row)
        {
          $ctinfo = $cart->CartoonSelfExistForSourceAndSourceid(SOURCE_BUKA, $row['mid']);
          if(empty($ctinfo))
          {
            $ctinfo = $cart->CartoonSelfExistForName($row['name']);
            if(empty($ctinfo))
            {
              $ctinfo = array('ctname'=>$row['name'], 'cttype'=>TYPE_SOURCE_USER, 'ctsource'=>SOURCE_BUKA, 'ctverticalimage'=>$row['logo'], 'ctsourceid'=>$row['mid'],'uid'=>$uid,'ctauthorname'=>$row['author'], 'ctprogress'=>($row['status']==0)?PROGRESS_NORMAL:PROGRESS_OVER);
              $ctid = $cart->add($ctinfo);
              $ctinfo = $cart->find($ctid);
            }

            $ctid = $ctinfo['ctid'];
            $csinfo = array('ctid'=>$ctid, 'cssource'=>SOURCE_BUKA, 'cssourceid'=>$row['mid'],'csstate'=>STATE_UPLOADED);
            $cart->add($csinfo,'cartoonsourceinfos');
          }

          foreach($row['sectionlist']['chapters'] as $subrow)
          {
            $name = $subrow['cname'];
            if(!empty($subrow['cid_cn']))
              $name = sprintf("%s %s", $subrow['cid_cn'], $name);
            $ctsinfo = $cart->SectionSelfExistForSourceAndSourceid($ctinfo['ctid'], SOURCE_BUKA, $subrow['cid'], $name);
            if(empty($ctsinfo))
            {
              $ctsinfo = array('ctid'=>$ctinfo['ctid'], 'ctsname'=>$name, 'ctssource'=>SOURCE_BUKA, 'ctssourceid'=>$subrow['cid']);
              $ctsid = $cart->add($ctsinfo, 'cartoonsectioninfos');
              $ctsinfo = $cart->find($ctsid,'cartoonsectioninfos','cts');
            }
            $ctsid = $ctsinfo['ctsid'];
            $ctrrinfo = $cart->GetReleseInfoBySectionAndPlatform($ctsinfo['ctsid'], SOURCE_BUKA);
            if(empty($ctrrinfo))
            {
              $states = array('0'=>STATE_UPLOADED, '1'=>STATE_AUTHSUCC, '2'=>STATE_ONLINE, '3'=>STATE_OVER, '101'=>STATE_AUTHFAIL);
              $ctrrstate = $states[$subrow['status']];
              //if($subrow['status'] == '通过')
              //  $ctrrstate = STATE_UPLOADED;
              $ctrrinfo = array('ctid'=>$ctinfo['ctid'], 'ctsid'=>$ctsinfo['ctsid'], 'pfid'=>SOURCE_BUKA, 'upftype'=>2, 'ctrrpfsectionid'=>$subrow['mid'], 'ctrrtype'=>RELEASE_TYPE_PLAT, 'ctrrstate'=>$ctrrstate);
              $cart->add($ctrrinfo, 'cartoonreleaserecordinfos');
            }

          }
        }
        $ajaxret['retno'] = RETNO_SUCC;
      }
      else
        $ajaxret['msg'] = '登录失败';
    }/*}}}*/
    elseif($pfid == 7)
    {/*{{{*/
      $succ = test_account_for_wangyi($pfusername,$pfpassword);
      if($succ)
      {
        $ctlist = get_cartoonlist_for_wangyi();
        foreach($ctlist as $idx=>$row)
        {
          $ctinfo = $cart->CartoonSelfExistForSourceAndSourceid(SOURCE_WANGYI, $row['ctsourceid']);
          if(empty($ctinfo))
          {
            $ctinfo = $cart->CartoonSelfExistForName($row['ctname']);
            if(empty($ctinfo))
            {
              $ctinfo = array('ctname'=>$row['ctname'], 'cttype'=>TYPE_SOURCE_USER, 'ctsource'=>SOURCE_WANGYI, 'ctverticalimage'=>$row['ctverticalimage'], 'ctsourceid'=>$row['ctsourceid'],'uid'=>$uid,'ctauthorname'=>'', 'ctprogress'=>($row['ctprogress']=='1')?PROGRESS_OVER:PROGRESS_NORMAL);
              $ctid = $cart->add($ctinfo);
              $ctinfo = $cart->find($ctid);
            }

            $ctid = $ctinfo['ctid'];
            $csinfo = array('ctid'=>$ctid, 'cssource'=>SOURCE_WANGYI, 'cssourceid'=>$row['ctsourceid'],'csstate'=>STATE_UPLOADED);
            $cart->add($csinfo,'cartoonsourceinfos');
          }

          foreach($row['sectionlist'] as $subrow)
          {
            $name = $subrow['ctsname'];
            $ctsinfo = $cart->SectionSelfExistForSourceAndSourceid($ctinfo['ctid'], SOURCE_WANGYI, $subrow['ctssourceid'], $name);
            if(empty($ctsinfo))
            {
              $ctsinfo = array('ctid'=>$ctinfo['ctid'], 'ctsname'=>$name, 'ctssource'=>SOURCE_WANGYI, 'ctssourceid'=>$subrow['ctssourceid']);
              $ctsid = $cart->add($ctsinfo, 'cartoonsectioninfos');
              $ctsinfo = $cart->find($ctsid,'cartoonsectioninfos','cts');
            }
            $ctsid = $ctsinfo['ctsid'];
            $ctrrinfo = $cart->GetReleseInfoBySectionAndPlatform($ctsinfo['ctsid'], SOURCE_WANGYI);
            if(empty($ctrrinfo))
            {
              $progress = STATE_UPLOADED;
              if($subrow['ctsprogress'] == '审核过(未发布)')
                $progress = STATE_AUTHSUCC;
              elseif($subrow['ctsprogress'] == '审核过(已发布)')
                $progress = STATE_OVER;
              $ctrrinfo = array('ctid'=>$ctinfo['ctid'], 'ctsid'=>$ctsinfo['ctsid'], 'pfid'=>SOURCE_WANGYI, 'upftype'=>2, 'ctrrpfsectionid'=>$subrow['mid'], 'ctrrtype'=>RELEASE_TYPE_PLAT, 'ctrrstate'=>$progress);
              $cart->add($ctrrinfo, 'cartoonreleaserecordinfos');
            }

          }
        }
        $ajaxret['retno'] = RETNO_SUCC;
      }
      else
        $ajaxret['msg'] = '登录失败';
    }/*}}}*/
    elseif($pfid == SOURCE_TENCENT)
    {/*{{{*/
      $pasinfos = $plat->GetPlatformSyncInfosByState($uid, $pfid, STATE_NOR);
      if(empty($pasinfos))
      {
        $pasinfo = array('uid'=>$uid,'pfid'=>$pfid,'upfusername'=>$pfusername, 'upfpassword'=>$pfpassword);
        $plat->AddPlatformSyncInfo($pasinfo);
      }
      $ajaxret['retno'] = RETNO_SUCC;
      $ajaxret['msg'] = '已提交同步信息';
    }/*}}}*/
    elseif($pfid == SOURCE_AIQIYI)
    {/*{{{*/
      $pasinfos = $plat->GetPlatformSyncInfosByState($uid, $pfid, STATE_NOR);
      if(empty($pasinfos))
      {
        $pasinfo = array('uid'=>$uid,'pfid'=>$pfid,'upfusername'=>$pfusername, 'upfpassword'=>$pfpassword);
        $plat->AddPlatformSyncInfo($pasinfo);
      }
      $ajaxret['retno'] = RETNO_SUCC;
      $ajaxret['msg'] = '已提交同步信息';
    }/*}}}*/
    elseif($pfid == SOURCE_WEIBO){  //同步微博动漫
        $sql = sprintf('select * from userandplatforminfos where uid = %d AND pfid = %d', $uid, $pfid);
        $upinfos = $plat->ExecuteRead($sql);

        //找到全部上传微博的漫画作品
        $sql = 'select * from cartoonsourceinfos where cssource = 11';
        $cartoons = $plat->ExecuteRead($sql);

        foreach ($cartoons as $cartoon) {
            $chapters = get_cartoonlist_for_weibo($upinfos[0]['upfcookies'], $cartoon['cssourceid']);
            $chapters = json_decode($chapters, true);
            if ($chapters['code'] == 0) {
                $ajaxret['msg'] = 'cookie失效';
                $sql = sprintf('update userandplatforminfos set upfcookiesstate = 5 where uid = %d AND pfid = %d', $uid, $pfid);
                $plat->ExecuteSql($sql);
                break;
            } elseif ($chapters['code'] == 1) {
                foreach ($chapters['result'] as $chapter) {
                   $sql = sprintf('update cartoonreleaserecordinfos set ctrrstate = 30 where pfid = 11 and ctrrpfsectionid = %d',$chapter['chapter_id']);
                   if ($chapter['is_access'] > 0) {
                     $plat->ExecuteSql($sql);
                   }
                }
            }
            $ajaxret['msg'] = '同步成功';
        }

    }
    else
      $ajaxret['msg'] = '不支持';
  }/*}}}*/
  elseif($type == 'getaccountbypasid')
  {/*{{{*/
    $pasid = GetItemFromArray($params,'pasid');
    $pasinfo = $plat->find($pasid,'platformaccountsyncinfos','pas');
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $pasinfo;
  }/*}}}*/
  elseif($type == 'getaccountbyupfid')
  {/*{{{*/
    $upfid = GetItemFromArray($params,'upfid');
    $upfinfo = $plat->find($upfid,'userandplatforminfos','upf');
    if(empty($upfinfo['upfusername']))
    {
      $pfinfo = $plat->find($upfinfo['pfid']);
      $upfinfo['upfusername'] = $pfinfo['pfusername'];
      $upfinfo['upfpassword'] = $pfinfo['pfpassword'];
    }
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = $upfinfo;
  }/*}}}*/
  elseif($type == 'setcookiesforpasid')
  {/*{{{*/
    $pasid = GetItemFromArray($params,'pasid');
    $cookies = GetItemFromArray($params,'cookies');
    $plat->update($pasid, array('pascookies'=>$cookies,'passtate'=>STATE_COOKIES),'pas','platformaccountsyncinfos');
    $ajaxret['retno'] = RETNO_SUCC;
  }/*}}}*/
  elseif($type == 'setcookiesforupfid')
  {/*{{{*/
    $upfid = GetItemFromArray($params,'upfid');
    $cookies = GetItemFromArray($params,'cookies');
    $cookies = trim($cookies);
    $plat->update($upfid, array('upfcookies'=>$cookies,'upfcookiesstate'=>STATE_COOKIES),'upf','userandplatforminfos');
    $ajaxret['retno'] = RETNO_SUCC;
  }/*}}}*/
  /* 添加用户平台账户 */
  elseif($type == 'addupfinfo')
  {/*{{{*/
    $pfid  = intval(GetItemFromArray($params,'pfid'));
    $upftype = intval(GetItemFromArray($params,'upftype'));
    $upfusername = GetItemFromArray($params,'upfusername');
    $upfpassword = GetItemFromArray($params,'upfpassword');
    $pfinfo = $pfinfo = $plat->find($pfid);
    if(!empty($pfinfo))
    {
      if($upftype == USERANDPLATFORM_TYPE_MEI)
      {
        $upfusername = '';$upfpassword= '';
      }
      $upfinfo = array('uid'=>$uid,'pfid'=>$pfid,'upftype'=>$upftype,'upfusername'=>$upfusername,
          'upfpassword'=>$upfpassword);
      $ret = $plat->add($upfinfo,'userandplatforminfos');
      $ajaxret['retno'] = RETNO_SUCC;
    }
    else
      $ajaxret['msg'] = '无效参数';
  }/*}}}*/
  /* 获取用户平台账户信息 */
  elseif($type == 'getupfinfo')
  {/*{{{*/
    $upfid = intval(GetItemFromArray($params,'upfid'));
    $upfinfo = $plat->find($upfid,'userandplatforminfos','upf');
    if(!empty($upfinfo))
    {
      $ajaxret['retno'] = RETNO_SUCC;
      $ajaxret['upfinfo'] = $upfinfo;
    }
    else
      $ajaxret['msg'] = '参数无效';
  }/*}}}*/
  /* 修改 用户平台账户信息 */
  elseif($type == 'updateupfinfo')
  {/*{{{*/
    $pfid  = intval(GetItemFromArray($params,'pfid'));
    $upfid = intval(GetItemFromArray($params,'upfid'));
    $upftype = intval(GetItemFromArray($params,'upftype'));
    $upfusername = GetItemFromArray($params,'upfusername');
    $upfpassword = GetItemFromArray($params,'upfpassword');
    $pfinfo = $pfinfo = $plat->find($pfid);
    if(!empty($pfinfo))
    {
      $upf_info = $plat->find($upfid,'userandplatforminfos','upf');
      if(!empty($upf_info))
      {
        if($upftype == USERANDPLATFORM_TYPE_MEI)
        {
          $upfusername = '';$upfpassword= '';
        }
        $upfinfo = array('pfid'=>$pfid,'upftype'=>$upftype,'upfusername'=>$upfusername,
            'upfpassword'=>$upfpassword);
        $ret = $plat->update($upfid,$upfinfo,'upf','userandplatforminfos');
        $ajaxret['retno'] = RETNO_SUCC;
      }
      else
        $ajaxret['msg'] = '参数无效';
    }
    else
      $ajaxret['msg'] = '参数无效';
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
$ajaxret['uid'] = $uid;
qLogInfo($logger, json_encode($ajaxret));
?>
