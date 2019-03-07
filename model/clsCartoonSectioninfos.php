<?php
/*
 * 用途：用户相关操作
 * 作者：feb1234@163.com
 * 时间：2015-10-11
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/baseModel.php');

class CartoonSectioninfos extends BaseModel
{
  function __construct()
  {/*{{{*/
    parent::__construct();
    $this->idkey = "ctsid";
    $this->tablename = "cartoonsectioninfos";
    $this->fieldpre = 'cts';
  }/*}}}*/

  public function find($ctid,$tn='',$pre='')
  {/*{{{*/
    $ctsinfo = parent::find($ctid,$tn,$pre);
    if(empty($tn))
    {
      if(empty($ctsinfo['ctsplatformcoverinfos']))
        $ctsinfo['ctsplatformcoverinfos'] = '{}';
    }
    return $ctsinfo;
  }/*}}}*/

  public function GetSectInfoByCtsidAndPfid($ctsid, $pfid)
  {/*{{{*/
    $strsql = sprintf('select * from cartoonsectioninfos where ctsparentid=%d and ctssource=%d and ctsstate<>400 limit 1', $ctsid, $pfid);
    return $this->db->getOneRecord($strsql);
  }/*}}}*/

  public function GetCartoonSectionInfosByParams($ctid,$p,$search='')
  {/*{{{*/
    $p = intval($p);
    if($p<1)
      $p = 1;
    $limitl = ($p-1)*COUNT_PER_PAGE;
    $limitr = COUNT_PER_PAGE;
    $where = sprintf(' ctid=%d and ctsstate!=%d and ctsparentid=0',$ctid,STATE_DEL);
    if(strlen($search))
      $where .= sprintf(' and ctsname like "%%%s%%"',mysql_real_escape_string($search,$this->db->link));

    return $this->getinfos($where,'','ctssort','',$limitl, $limitr);
  }/*}}}*/

  public function GetCartoonSectionInfosByCtid($ctid)
  {/*{{{*/
    $where = sprintf(' ctid=%d and ctsstate!=%d and ctsparentid=0',$ctid,STATE_DEL);

    return $this->getinfos($where,'','ctssort');
  }/*}}}*/

  public function GetSectionCountByCtid($ctid){
    /*{{{*/
    $strsql = sprintf('select count(*) count from cartoonsectioninfos where ctid=%d and ctsstate!=%d', $ctid, STATE_DEL);
    return $this->db->getRowCount($strsql);
  }/*}}}*/

  public function ExistSectionByCtidAndPfidAndSourceId($ctid,$pfid,$sourceid){
    /*{{{*/
    return $this->getinfos(sprintf('ctid=%d and ctssource=%d and ctssourceid=%d', $ctid, $pfid, $sourceid));
  }/*}}}*/

};

?>
