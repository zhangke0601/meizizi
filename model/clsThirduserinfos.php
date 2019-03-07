<?php
/*
 * 用途：第三方相关操作
 * 作者：feb1234@163.com
 * 时间：2017-05-01
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/baseModel.php');

class Thirduserinfos extends BaseModel
{
  function __construct()
  {/*{{{*/
    parent::__construct();
    $this->idkey = "tuid";
    $this->tablename = "thirduserinfos";
    $this->fieldpre = 'tu';
  }/*}}}*/

  public function GetListByParam($p,$s)
  {/*{{{*/
    $s = trim($s);
    $p = intval($p);
    if($p < 1)
      $p = 1;
    $where = sprintf('tustate !=%s ', STATE_DEL);
    if(!empty($s))
      $where .= sprintf('and tuname like "%%%s%%"', mysql_real_escape_string($s,$this->db->link));
    return $this->getinfos($where,'','','',($p-1)*COUNTPERPAGE, COUNTPERPAGE);

  }/*}}}*/

  public function GetCartooninfosByTuid($tuid){
    /*{{{*/
    $tucinfos = $this->getinfos(sprintf('tuid=%d',$tuid), 'thirduserandcartooninfos');
    return $tucinfos;
  }/*}}}*/

  public function GetCartooninfoByTuidAndCtid($tuid,$ctid){
    /*{{{*/
    $tucinfos = $this->getoneinfo(sprintf('tuid=%d and ctid=%d',$tuid,$ctid), 'thirduserandcartooninfos');
    return $tucinfos;
  }/*}}}*/

  public function UpdateTucinfoByTucid($tucid,$data){
    /*{{{*/
    $ret = $this->update($tucid,$data,'tuc','thirduserandcartooninfos');
    return $ret;
  }/*}}}*/

  public function DeleteTucinfoByTucid($tucid){
    /*{{{*/
    $strsql = sprintf('delete from thirduserandcartooninfos where tucid=%d', $tucid);
    return $this->db->query($strsql);
  }/*}}}*/

  public function GetTucinfoByTucid($tucid){
    /*{{{*/
    $tucinfo = $this->find($tucid,'thirduserandcartooninfos','tuc');
    return $tucinfo;
  }/*}}}*/
  public function GetTucinfoByTuidAndCtid($tuid,$ctid){
    /*{{{*/
    return $this->getoneinfo(sprintf("tuid=%d and ctid=%d", $tuid, $ctid), 'thirduserandcartooninfos');
  }/*}}}*/

  public function GetTucAndUinfo($tuid,$uid){
    /*{{{*/
    return $this->getoneinfo(sprintf("tuid=%d and uid=%d", $tuid, $uid), 'thirduseranduserinfos');
  }/*}}}*/

  public function AddTuustate($tuid, $uid, $tuustate, $tuuauthenddate){
    /*{{{*/
    $strsql = sprintf('delete from thirduseranduserinfos where tuid=%d and uid=%d', $tuid, $uid);
    $this->db->query($strsql);
    $tuuid = $this->add(array('tuid'=>$tuid,'uid'=>$uid,'tuustate'=>$tuustate, 'tuuauthenddate'=>$tuuauthenddate), 'thirduseranduserinfos');
    return $tuuid;
  }/*}}}*/

  public function GetTuustate($tuid,$uid){
    /*{{{*/
    return $this->getoneinfo(sprintf('tuid=%d and uid=%d', $tuid, $uid), 'thirduseranduserinfos');
  }/*}}}*/

  public function GetTuuinfos($p){
    /*{{{*/
    $p = intval($p);
    if($p < 1)
      $p = 1;
    $where = sprintf('tuustate !=%s and tuid>0 ', STATE_DEL);
    return $this->getinfos($where,'thirduseranduserinfos','uid','',($p-1)*COUNTPERPAGE, COUNTPERPAGE);
  }/*}}}*/

  public function GetTuinfoByAccesstoken($accesstoken){
    /*{{{*/
    return $this->getoneinfo(array(array('tuaccesstoken','=', $accesstoken),array('tustate','=','0')), 'thirduserinfos');
  }/*}}}*/

};

?>
