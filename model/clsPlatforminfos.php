<?php
/*
 * 用途：发布平台相关操作
 * 作者：feb1234@163.com
 * 时间：2015-10-11
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/baseModel.php');

class Platforminfos extends BaseModel
{

  function __construct()
  {/*{{{*/
    parent::__construct();
    $this->idkey = "pfid";
    $this->tablename = "platforminfos";
    $this->fieldpre = 'pf';
  }/*}}}*/

  public function getUserAndPlatforminfos($uid)
  {/*{{{*/
    $strsql = sprintf('select * from userandplatforminfos where uid=%d', $uid);
    return $this->db->executeRead($strsql);
  }/*}}}*/

  public function existUserAndPlatform($uid, $pfid)
  {/*{{{*/
    $strsql = sprintf('select * from userandplatforminfos where uid=%d and pfid=%d', $uid, $pfid);
    return $this->db->getOneRecord($strsql);
  }/*}}}*/

  public function GetAllPlatformInfos($s)
  {/*{{{*/
    $where = 'pfstate!='.STATE_DEL;
    if($s)
      $where .= sprintf(' and pfname like "%%%s%%"', mysql_real_escape_string($s,$this->db->link));
    return $pfinfos = $this->getinfos($where,'','pfsort ');
  }/*}}}*/

  public function GetPlatformSyncInfosByState($uid,$pfid,$s)
  {/*{{{*/
    $infos = $this->getinfos(sprintf('uid=%d and pfid=%d and passtate=%d',$uid,$pfid,$s),'platformaccountsyncinfos');
    return $infos;
  }/*}}}*/

  public function AddPlatformSyncInfo($pasinfo)
  {/*{{{*/
    return $this->add($pasinfo, 'platformaccountsyncinfos');
  }/*}}}*/

  public function GetPlatformSyncInfosByParam()
  {/*{{{*/
    $infos = $this->getinfos(sprintf('passtate=%d',STATE_NOR),'platformaccountsyncinfos');
    return $infos;
  }/*}}}*/

  public function GetUnprocPlatformSyncInfos()
  {/*{{{*/
    $infos = $this->getinfos(sprintf('passtate=%d',STATE_NOR),'platformaccountsyncinfos');
    return $infos;
  }/*}}}*/

  public function GetInvalidCookieInfos()
  {/*{{{*/
    $upfinfos = $this->getinfos(sprintf('upfcookiesstate=%d and pfid in (%d,%d,%d,%d)',STATE_INVALID, SOURCE_TENCENT,SOURCE_AIQIYI,SOURCE_WEIBO,SOURCE_U17), 'userandplatforminfos', 'upfid');
    return $upfinfos;
  }/*}}}*/

};

?>
