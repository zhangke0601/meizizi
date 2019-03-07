<?php
/*
 * 用途：非归类相关操作
 * 作者：feb1234@163.com
 * 时间：2017-05-01
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/baseModel.php');

class Noticeinfos extends BaseModel
{
  function __construct()
  {/*{{{*/
    parent::__construct();
    $this->idkey = "nid";
    $this->tablename = "noticeinfos";
    $this->fieldpre = 'n';
  }/*}}}*/

  public function GetListByParam($p,$s)
  {/*{{{*/
    $s = trim($s);
    $p = intval($p);
    if($p < 1)
      $p = 1;
    $where = sprintf('nstate !=%s ', STATE_DEL);
    if(!empty($s))
      $where .= sprintf('and ntitle like "%%%s%%"', mysql_real_escape_string($s,$this->db->link));
    return $this->getinfos($where,'','','',($p-1)*COUNTPERPAGE, COUNTPERPAGE);

  }/*}}}*/

  public function GetPostingInfos()
  {/*{{{*/
    return $this->getinfos(sprintf('nstate=%d', STATE_POSTING));
  }/*}}}*/

  public function AddPostNotice($uaninfo)
  {/*{{{*/
    return $this->add($uaninfo, 'userandnoticeinfos');
  }/*}}}*/

  public function GetLastestInfo($uid){
    /*{{{*/
    $strsql = sprintf('select * from userandnoticeinfos where uid=%d order by uanid desc limit 1', $uid);
    $uaninfo = $this->db->getOneRecord($strsql);
    $ninfo = array();
    if($uaninfo){
      $ninfo = $this->find($uaninfo['nid']);
      $ninfo['uanstate'] = $uaninfo['uanstate'];
      $ninfo['uanid'] = $uaninfo['uanid'];
    }
    return $ninfo;
  }/*}}}*/

  public function GetInfoByUanid($uanid){
    /*{{{*/
    $uaninfo = $this->find($uanid,'userandnoticeinfos','uan');
    $ninfo = array();
    if($uaninfo){
      $ninfo = $this->find($uaninfo['nid']);
      $ninfo['uanstate'] = $uaninfo['uanstate'];
      $ninfo['uanid'] = $uaninfo['uanid'];
    }
    return $ninfo;
  }/*}}}*/

  public function GetNoticeInfos($uid){
    /*{{{*/
    $strsql = sprintf('select * from userandnoticeinfos where uid=%d order by uanid desc', $uid);
    $uaninfos = $this->db->executeRead($strsql);
    $ninfos = array();
    foreach($uaninfos as $uaninfo){
      $ninfo = $this->find($uaninfo['nid']);
      $ninfo['uanstate'] = $uaninfo['uanstate'];
      $ninfo['uanid'] = $uaninfo['uanid'];
      $ninfos[] = $ninfo;
    }
    return $ninfos;
  }/*}}}*/

  public function UpdateReadState($uanid,$state){
    /*{{{*/
    return $this->update($uanid, array('uanstate'=>$state), 'uan', 'userandnoticeinfos');
  }/*}}}*/
};

?>
