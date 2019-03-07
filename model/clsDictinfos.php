<?php
/*
 * 用途：字典相关操作
 * 作者：feb1234@163.com
 * 时间：2017-10-26
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/baseModel.php');

class Dictinfos extends BaseModel
{

  function __construct()
  {/*{{{*/
    parent::__construct();
    $this->idkey = "pfid";
    $this->tablename = "platforminfos";
    $this->fieldpre = 'pf';
  }/*}}}*/

  public function GetTagInfoByCttid($cttid)
  {/*{{{*/
    return $this->find($cttid, 'cartoontaginfos','ctt');
  }/*}}}*/
  public function GetTagInfoByName($name)
  {/*{{{*/
    return $this->getoneinfo(sprintf('cttname="%s"', mysql_real_escape_string($name,$this->db->link)), 'cartoontaginfos');
  }/*}}}*/
  public function AddCartoonAndTag($ctid,$cttid)
  {/*{{{*/
    return $this->SetRelation('ctid',$ctid,'cttid',$cttid,'cartoonandtaginfos');
  }/*}}}*/

  public function GetTypeInfoByCttpid($cttpid)
  {/*{{{*/
    return $this->find($cttpid, 'cartoontypeinfos','cttp');
  }/*}}}*/
  public function GetSubjectInfoByCtsuid($ctsuid)
  {/*{{{*/
    return $this->find($ctsuid, 'cartoonsubjectinfos','ctsu');
  }/*}}}*/

  public function GetTagInfosByParams($p,$s)
  {/*{{{*/
    $where = 'cttstate!='.STATE_DEL;
    if($s)
      $where.= sprintf(' and cttname like "%%%s%%"',mysql_real_escape_string($s,$this->db->link));

    $p = intval($p);
    if($p < 1)
      $p = 1;
    $limitl = ($p-1)*COUNT_PER_PAGE;
    $limitr = COUNT_PER_PAGE;

    return $this->getinfos($where,'cartoontaginfos','cttsort, cttid desc','',$limitl, $limitr);

  }/*}}}*/

  public function GetTypeInfosByParams($p,$s)
  {/*{{{*/
    $where = 'cttpstate!='.STATE_DEL;
    if($s)
      $where.= sprintf(' and cttpname like "%%%s%%"',mysql_real_escape_string($s,$this->db->link));

    $p = intval($p);
    if($p < 1)
      $p = 1;
    $limitl = ($p-1)*COUNT_PER_PAGE;
    $limitr = COUNT_PER_PAGE;

    return $this->getinfos($where,'cartoontypeinfos',' cttpsort , cttpid desc','',$limitl, $limitr);

  }/*}}}*/

  public function GetSubjectInfosByParams($p,$s)
  {/*{{{*/
    $where = 'ctsustate!='.STATE_DEL;
    if($s)
      $where.= sprintf(' and ctsuname like "%%%s%%"',mysql_real_escape_string($s,$this->db->link));

    $p = intval($p);
    if($p < 1)
      $p = 1;
    $limitl = ($p-1)*COUNT_PER_PAGE;
    $limitr = COUNT_PER_PAGE;

    return $this->getinfos($where,'cartoonsubjectinfos','ctsusort , ctsuid desc','',$limitl, $limitr);

  }/*}}}*/
};

?>
