<?php
/*
 * 用途：从各个平台获取统计信息
 * 作者：feb1234@163.com
 * 时间：2017-12-31
 * */
$base_dir = dirname(__FILE__).'/../../';
require_once($base_dir.'config/config.php');
require_once($base_dir.'inc/util_for_simulate.php');
require_once($base_dir.'inc/util_for_meizizi.php');
require_once($base_dir.'model/clsUserinfos.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsPlatforminfos.php');
ini_set('memory_limit','512M');



$logger = 'meizizicon.cron.getstat';
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
  if(in_array($uid, array(1,2,3,10,11,13,16))){
    continue;
  }
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


      qLogInfo($logger, sprintf("currun\t%d\t%d", $uid, $pfid));
      if($pfid == SOURCE_WANGYI)
      {/*{{{*/
        test_account_for_wangyi($username,$password);
        $list = get_cartoonlist_for_wangyi();
        foreach($list as $row)
        {
          $ctsourceid = $row['ctsourceid'];
          $stat = get_cartoonstat_for_wangyi($ctsourceid);

          $ctinfo = $cart->CartoonSelfExistForSourceAndSourceid($pfid, $ctsourceid);
          if(empty($ctinfo))
            continue;

          $ctid = $ctinfo['ctid'];
          foreach($stat['income'] as $day=>$ms)
          {
            $sdinfo = array('ctid'=>$ctid,'pfid'=>$pfid, 'ctsddayincome'=>$ms['sale']*0.5,'ctsddaysaleincome'=>'', 'ctsddayshangincome'=>'','ctsdday'=>$day);
            $cart->AddSelfDataInfo($sdinfo);
          }

          if(isset($stat['click'])){
            foreach($stat['click'] as $date=>$count)
            {
              $tcount = 0;
              if(isset($stat['tucao'][$date]))
                $tcount = $stat['tucao'][$date];
              $year = date('Y');
              $m = date('m');
              if((strpos($date,'12')===0) && ($m=='01'))
                $day = sprintf('%s-%s', $year-1, $date);
              else
                $day = sprintf("%s-%s", $year, $date);

              $sdinfo = array('ctid'=>$ctid,'pfid'=>$pfid, 'ctsdday'=>$day,'ctsddaybrowsercount'=>$count,'ctsddaytucaocount'=>$tcount);
              $cart->AddSelfDataInfo($sdinfo);
            }
          }

          $ctbrowsercount = $row['ctbrowsercount'];
          $ctcollectcount = $row['ctcollectcount'];
          $sdinfo = array('ctid'=>$ctid,'pfid'=>$pfid,'ctsdday'=>date('Y-m-d'),'ctsdbrowsercount'=>$ctbrowsercount, 'ctsdcollectcount'=>$ctcollectcount);
          $cart->AddSelfDataInfo($sdinfo);
        }
      }/*}}}*/
      elseif($pfid == SOURCE_TENCENT)
      {/*{{{*/
        //$cookies = $cart->GetLatestCookiesByUidAndPfid($uid, $pfid);
        $cookies = '';
        if($upfinfo['upfcookiesstate'] == STATE_COOKIES){
          $cookies = $upfinfo['upfcookies'];
        }else{
          $cartinfos = $cart->getinfos(sprintf('uid=%d', $uid));
          if(count($cartinfos) > 0){
            $pf->update($upfinfo['upfid'], array('upfcookiesstate'=>STATE_INVALID,'upfupdatetime'=>date('Y-m-d H:i:s')), 'upf', 'userandplatforminfos');
          }
          continue;
        }

        if($cookies)
        {
          $list = get_cartoonlist_for_tencent($cookies);
          if(empty($list))
          {
            $pf->update($upfinfo['upfid'], array('upfcookiesstate'=>STATE_INVALID,'upfupdatetime'=>date('Y-m-d H:i:s')), 'upf', 'userandplatforminfos');
            continue;
          }
          foreach($list as $cinfo)
          {
            $ctinfo = $cart->CartoonSelfExistForSourceAndSourceid($pfid, $cinfo['ctsourceid']);
            if(empty($ctinfo))
              continue;

            $ctid = $ctinfo['ctid'];
            $ctbrowsercount = $cinfo['ctbrowsercount'];
            $ctcollectcount = $cinfo['ctcollectcount'];
            $sdinfo = array('ctid'=>$ctid,'pfid'=>$pfid,'ctsdday'=>date('Y-m-d'),'ctsdbrowsercount'=>$ctbrowsercount, 'ctsdcollectcount'=>$ctcollectcount);
            $cart->AddSelfDataInfo($sdinfo);
          }

          $stat = get_cartoonstat_for_tencent($cookies); //day stat
          if(!empty($stat))
          {
            foreach($stat as $day=>$val)
            {
              foreach($val as $iinfo)
              {
                $name = $iinfo[1];
                $ctinfo = $cart->CartoonSelfExistForNameAndUid($name, $uid);
                if(!empty($ctinfo))
                {
                  $ctid = $ctinfo['ctid'];
                  $csinfo = $cart->CartoonSelfExistForCtidAndSource($ctid, SOURCE_TENCENT);
                  if($csinfo && !empty($csinfo['csdividexishu'])){
                    $xishu = 0.75*$csinfo['csdividexishu']/100/100;
                    //$xishu = 0.32/100;
                    $sdinfo = array('ctid'=>$ctid,'pfid'=>$pfid,'ctsddayincome'=>$iinfo[4]*$xishu,'ctsdextra'=>json_encode($iinfo),'ctsdday'=>$day);
                    $cart->AddSelfDataInfo($sdinfo);
                  }
                }
              }

              /*foreach($stat['income'] as $y=>$ms)
              {
                foreach($ms as $m=>$r)
                {
                  if((($r['sale']=='0.00')&&($r['shang']=='0.00')) || ($r['sale']=='未出账单'))
                    ;
                  else
                  {
                    $day = sprintf("%s-%02d-01", $y, $m);
                    $sdinfo = array('ctid'=>$ctid,'pfid'=>$pfid,'ctsddaysaleincome'=>$r['sale'], 'ctsddayshangincome'=>$r['shang'],'ctsdday'=>$day);
                    $cart->AddSelfDataInfo($sdinfo);
                  }
                }
              }*/
            }
          }
        }
      }/*}}}*/
      elseif($pfid == SOURCE_AIQIYI)
      {/*{{{*/
        //$cookies = $cart->GetLatestCookiesByUidAndPfid($uid, $pfid);
        $cookies = '';
        if($upfinfo['upfcookiesstate'] == STATE_COOKIES){
          $cookies = $upfinfo['upfcookies'];
        }else{
          $cartinfos = $cart->getinfos(sprintf('uid=%d', $uid));
          if(count($cartinfos) > 0){
            $pf->update($upfinfo['upfid'], array('upfcookiesstate'=>STATE_INVALID,'upfupdatetime'=>date('Y-m-d H:i:s')), 'upf', 'userandplatforminfos');
          }
          continue;
        }

        if($cookies)
        {
          $list = get_cartoonlist_for_iqiyi($cookies);
          if(empty($list))
          {
            $pf->update($upfinfo['upfid'], array('upfcookiesstate'=>STATE_INVALID,'upfupdatetime'=>date('Y-m-d H:i:s')), 'upf', 'userandplatforminfos');
            continue;
          }
          foreach($list as $info)
          {
            $id = $info['qipuId'];
            $stat = get_cartoonincome_for_iqiyi($id, $cookies);
            if(!empty($stat))
            {
              foreach($stat as $day=>$iinfo)
              {
                $ctinfo = $cart->CartoonSelfExistForSourceAndSourceid($pfid, $info['id']);
                if(empty($ctinfo))
                  continue;

                if($iinfo['total'] > 0){
                  $ctid = $ctinfo['ctid'];
                  $sdinfo = array('ctid'=>$ctid,'pfid'=>$pfid,'ctsddayincome'=>$iinfo['total'],'ctsdday'=>$day);
                  $cart->AddSelfDataInfo($sdinfo);
                }
              }
            }
          }
        }
      }/*}}}*/
      elseif($pfid == SOURCE_ZHANGYUE)
      {/*{{{*/
        test_account_for_zhangyue($username, $password);
        $stat = get_cartoonincome_for_zhangyue('');
        foreach($stat as $cssourceid=>$row)
        {
          $ctinfo = $cart->CartoonSelfExistForSourceAndSourceid($pfid, $cssourceid);
          if(empty($ctinfo))
            continue;

          $ctid = $ctinfo['ctid'];
          foreach($row as $day=>$val)
          {
            if($val > 0)
            {
              $sdinfo = array('ctid'=>$ctid,'pfid'=>$pfid,'ctsddayincome'=>$val,'ctsdday'=>$day);
              $cart->AddSelfDataInfo($sdinfo);
            }
          }
        }

      }/*}}}*/
      elseif($pfid == SOURCE_MANHUADAO)
      {/*{{{*/
        test_account_for_manhuadao($username, $password);
        if(intval(date('d')) < 6){
          $stat = get_cartoonincome_for_manhuadao(date('Y-m', strtotime('-1 month')));
          if(isset($stat['income'])){
            foreach($stat['income'] as $cssourceid=>$row)
            {
              //$ctinfo = $cart->CartoonSelfExistForSourceAndSourceid($pfid, $cssourceid);
              $ctinfo = $cart->CartoonSelfExistForNameAndUid($row['name'], $uid);
              if(empty($ctinfo))
                continue;

              $ctid = $ctinfo['ctid'];
              foreach($row['data'] as $day=>$val)
              {
                if($val > 0)
                {
                  $sdinfo = array('ctid'=>$ctid,'pfid'=>$pfid,'ctsddayincome'=>$val,'ctsdday'=>$day);
                  $cart->AddSelfDataInfo($sdinfo);
                }
              }
            }
          }
        }
        $stat = get_cartoonincome_for_manhuadao();
        if(isset($stat['income'])){
          foreach($stat['income'] as $cssourceid=>$row)
          {
            //$ctinfo = $cart->CartoonSelfExistForSourceAndSourceid($pfid, $cssourceid);
            $ctinfo = $cart->CartoonSelfExistForNameAndUid($row['name'], $uid);
            if(empty($ctinfo))
              continue;

            $ctid = $ctinfo['ctid'];
            foreach($row['data'] as $day=>$val)
            {
              if($val > 0)
              {
                $sdinfo = array('ctid'=>$ctid,'pfid'=>$pfid,'ctsddayincome'=>$val,'ctsdday'=>$day);
                $cart->AddSelfDataInfo($sdinfo);
              }
            }
          }
        }

      }/*}}}*/
    }
  }
}

start_checkstate();

?>
