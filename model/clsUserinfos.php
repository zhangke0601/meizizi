<?php
/*
 * 用途：用户相关操作
 * 作者：feb1234@163.com
 * 时间：2015-10-11
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/baseModel.php');

class Userinfos extends BaseModel
{
  function __construct()
  {/*{{{*/
    parent::__construct();
    $this->idkey = "uid";
    $this->tablename = "userinfos";
    $this->fieldpre = 'u';
  }/*}}}*/

  function GetComicInfosByParams($s,$p)
  {/*{{{*/
    $where = sprintf('ustate!=%d and utype=%d',STATE_DEL,USERTYPE_MANHUASHI);
    if($s)
      $where .= sprintf(' and (urealname like "%%%s%%" or umobile="%s")', mysql_real_escape_string($s,$this->db->link), mysql_real_escape_string($s,$this->db->link));
    $p = intval($p);
    if($p < 1)
      $p = 1;
    $limitl = ($p-1)*COUNT_PER_PAGE;
    $limitr = COUNT_PER_PAGE;

    return $this->getinfos($where,'','','',$limitl, $limitr);
  }/*}}}*/

  function GetComicInfosByUpuidAndParams($upuid,$s,$p)
  {/*{{{*/
    $where = sprintf('utype=%d and upuid=%d and ustate!=%d',USERTYPE_MANHUASHI,$upuid,
        STATE_DEL);
    if($s)
      $where .= sprintf(' and urealname like "%%%s%%"',mysql_real_escape_string($s,$this->db->link));
    if($p < 1)
      $p = 1;
    $limitl = ($p-1)*COUNT_PER_PAGE;
    $limitr = COUNT_PER_PAGE;
    return $this->getinfos($where,'','','',$limitl, $limitr);
  }/*}}}*/

  function GetManagerinfosByUpuidAndParams($upuid,$p,$s='')
  {/*{{{*/
    $where = sprintf('utype=%d and upuid=%d and ustate!=%d',USERTYPE_STUDIOMANAGE,$upuid,
        STATE_DEL);
    if($s)
      $where .= sprintf(' and (urealname like "%%%s%%" or uname like "%%%s%%"  )',mysql_real_escape_string($s,$this->db->link), mysql_real_escape_string($s,$this->db->link));
    $p = intval($p);
    if($p < 1)
      $p = 1;
    $limitl = ($p-1)*COUNT_PER_PAGE;
    $limitr = COUNT_PER_PAGE;

    return $this->getinfos($where,'','','',$limitl, $limitr);
  }/*}}}*/

  function GetStudioInfosByParams($s,$p)
  {/*{{{*/
    $where = sprintf('ustate!=%d and utype=%d',STATE_DEL,USERTYPE_STUDIO);
    if($s)
      $where .= sprintf(' and (urealname like "%%%s%%" or umobile="%s")', mysql_real_escape_string($s,$this->db->link), mysql_real_escape_string($s,$this->db->link));
    $p = intval($p);
    if($p < 1)
      $p = 1;
    $limitl = ($p-1)*COUNT_PER_PAGE;
    $limitr = COUNT_PER_PAGE;

    return $this->getinfos($where,'','','',$limitl, $limitr);
  }/*}}}*/

  function recordlogin($ulinfo)
  {/*{{{*/
    return $this->add($ulinfo, 'userlogininfos');
  }/*}}}*/

  function GetLoginInfosByUid($uid)
  {/*{{{*/
    return $this->getinfos(sprintf('uid=%d',$uid),'userlogininfos','ulid desc', '', 0, 20);
  }/*}}}*/

  function GetLoginWeekTimesByUid($uid)
  {/*{{{*/
    $w = date('w');
    if($w == 0)
      $date = date('Y-m-d', time()-6*24*3600);
    else
      $date = date('Y-m-d', time()-($w-1)*24*3600);
    list($count, $ts) = $this->getinfos(sprintf('uid=%d and ulcreatetime>="%s"',$uid, $date),'userlogininfos','ulid desc', '', 0, 20);
    return $count;
  }/*}}}*/

  function GetLoginMonthTimesByUid($uid)
  {/*{{{*/
    list($count, $ts) = $this->getinfos(sprintf('uid=%d and ulcreatetime>="%s"',$uid, date("Y-m-01")),'userlogininfos','ulid desc', '', 0, 20);
    return $count;
  }/*}}}*/

  function GetLoginYearTimesByUid($uid)
  {/*{{{*/
    list($count, $ts) = $this->getinfos(sprintf('uid=%d and ulcreatetime>="%s"',$uid, date("Y-01-01")),'userlogininfos','ulid desc', '', 0, 20);
    return $count;
  }/*}}}*/

  function GetGroupInfoByUgid($ugid)
  {/*{{{*/
    return $this->find($ugid,'usergroupinfos','ug');
  }/*}}}*/

  function GetStudioGroupInfoBySugid($sugid)
  {/*{{{*/
    return $this->find($sugid,'studiousergroupinfos','sug');
  }/*}}}*/

  function GetGroupInfos($s)
  {/*{{{*/
    $where = 'ugstate!='.STATE_DEL;
    if(!empty($s))
      $where .= sprintf(' and ugname like "%%%s%%"', mysql_real_escape_string($s,$this->db->link));
    $uginfos = $this->getinfos($where,'usergroupinfos','ugid desc');
    foreach($uginfos as $idx=>$info)
    {
      if($uginfos[$idx]['ugfuncinfos'])
        $uginfos[$idx]['ugfuncinfos'] = json_decode($info['ugfuncinfos'],true);
      else
        $uginfos[$idx]['ugfuncinfos'] = array();
    }
    return $uginfos;
  }/*}}}*/

  function GetStudioGroupInfosByUid($uid)
  {
    $suginfos = $this->getinfos(sprintf('sugstate!=%d and uid=%d',STATE_DEL,$uid),'studiousergroupinfos','sugid desc');
    foreach($suginfos as $idx=>$info)
    {
      if($suginfos[$idx]['sugfuncinfos'])
        $suginfos[$idx]['sugfuncinfos'] = json_decode($info['sugfuncinfos'],true);
      else
        $suginfos[$idx]['sugfuncinfos'] = array();
    }
    return $suginfos;
  }

  function GetStudioGroupInfosByParams($uid,$p,$search='')
  {
    $p = intval($p);
    if($p<1)
      $p = 1;
    $limitl = ($p-1)*COUNT_PER_PAGE;
    $limitr = COUNT_PER_PAGE;
    $where = sprintf('sugstate!=%d and uid=%d',STATE_DEL,$uid);
    if(strlen($search))
      $where .= sprintf(' and sugname like "%%%s%%"',mysql_real_escape_string($search,$this->db->link));
    $suginfos = $this->getinfos($where,'studiousergroupinfos','sugid desc','',$limitl,$limitr);

    foreach($suginfos[1] as $idx=>$info)
    {
      if($suginfos[1][$idx]['sugfuncinfos'])
        $suginfos[1][$idx]['sugfuncinfos'] = json_decode($info['sugfuncinfos'],true);
      else
        $suginfos[1][$idx]['sugfuncinfos'] = array();
    }
    return $suginfos;
  }

  function AddGroupAuth($ugid,$funcs)
  {/*{{{*/
    return $this->update($ugid,array('ugfuncinfos'=>json_encode($funcs)), 'ug','usergroupinfos');
  }/*}}}*/
  function AddStudioGroupAuth($sugid,$funcs)
  {/*{{{*/
    return $this->update($sugid,array('sugfuncinfos'=>json_encode($funcs)), 'sug','studiousergroupinfos');
  }/*}}}*/
  function GetAuthUserInfos()
  {/*{{{*/
    $auinfos = $this->getinfos(sprintf('utype=%d and ustate!=%d',USERTYPE_AUTH,STATE_DEL ));
    return $auinfos;
  }/*}}}*/
  function GetAuthUserInfosByParams($p,$search='')
  {/*{{{*/
    $p = intval($p);
    if($p < 1)
      $p = 1;
    $limitl = ($p-1)*COUNT_PER_PAGE;
    $limitr = COUNT_PER_PAGE;
    $where = sprintf('utype=%d and ustate!=%d',USERTYPE_AUTH,STATE_DEL );
    if(strlen($search))
      $where .= sprintf(' and uname like "%%%s%%"',mysql_real_escape_string($search,$this->db->link));
    $auinfos = $this->getinfos($where,'','uid desc','',$limitl,$limitr);
    return $auinfos;
  }/*}}}*/
  function SetAuthState($uid,$state)
  {/*{{{*/
    return $this->update($uid, array('ustate'=>$state,'ugroupid'=>0));
  }/*}}}*/


  function comicRooms()
  {
      $where = sprintf('utype=%d and ustate!=%d',USERTYPE_STUDIO,STATE_DEL );
      $users = $this->getinfos($where,'','uid desc');
      return $users;
  }
};

?>
