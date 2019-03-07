<?php
/*
 * 用途：用户相关操作
 * 作者：feb1234@163.com
 * 时间：2015-10-11
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'config/config.php');
require_once($base_dir.'inc/db/newdbdriver.php');
require_once($base_dir.'inc/func.php');
require_once($base_dir.'inc/init.php');

class BaseNewModel
{
  protected $db;
  private $cfg;
  protected $idkey;
  protected $tablename;
  protected $fieldpre;
  protected $lastsql;

  function __construct()
  {/*{{{*/
      $new_cfg1 = [];
      $new_cfg1['db'] = [
          'host' => '172.31.195.225',
          'dbname' => 'new_meizizi',
          'username' => 'root',
          'password' => 'QWERT12345'
      ];
    $this->idkey = 'id';
    $this->cfg = $new_cfg1;
    $this->db = new DbDriver($this->cfg['db']['host'], $this->cfg['db']['dbname'], $this->cfg['db']['username'], $this->cfg['db']['password']);

  }/*}}}*/

  /*{{{ 基本操作*/
  function find($id,$tn="",$pre="")
  {/*{{{*/
    if(empty($tn))
      $tn = $this->tablename;
    $idkey = $this->idkey;
    if(!empty($pre))
      $idkey = $pre.'id';
    $strsql = sprintf('select * from %s where %s=%d', $tn, $idkey, $id);
    return $this->db->getOneRecord($strsql);
  }/*}}}*/

  function add($info,$tn="")
  {/*{{{*/
    $ks = '';
    $vs = '';
    foreach($info as $k=>$v)
    {
      $ks .= sprintf('%s,', $k);
      $vs .= sprintf('"%s",', mysql_real_escape_string($v,$this->db->link));
    }
    $ks = trim($ks,',');
    $vs = trim($vs,',');
    $id = false;
    if(strlen($tn) == 0)
      $tn = $this->tablename;
    $strsql = sprintf('insert into %s(%s) values(%s)', $tn, $ks, $vs);
    $ret = $this->db->query($strsql);
    if($ret !== false)
      $id = $this->db->insert_id();
    return $id;
  }/*}}}*/

  function update($id, $info, $pre='',$tn='')
  {/*{{{*/
    $kvs = '';
    foreach($info as $k=>$v)
    {
      $kvs .= sprintf('%s="%s",', $k, mysql_real_escape_string($v, $this->db->link));
    }
    $kvs = trim($kvs,',');
    if(empty($pre))
      $pre = $this->fieldpre;
    if(empty($tn))
      $tn = $this->tablename;
    $strsql = sprintf('update %s set %s where %sid=%d',$tn, $kvs, $pre, $id);
    $this->lastsql = $strsql;
    return $this->db->update($strsql);
  }/*}}}*/

  function setstate($id, $state)
  {/*{{{*/
    $strsql = sprintf('update %s set %sstate=%d where %sid=%d',
      $this->tablename, $this->fieldpre, $state, $this->fieldpre,
      $id);
    return $this->db->update($strsql);
  }/*}}}*/

  function getoneinfo($where, $tn='')
  {/*{{{*/
    if(empty($tn))
      $tn = $this->tablename;
    $strsql = sprintf('select * from %s ', $tn);
    if(!empty($where))
    {
      if(is_array($where)){
        $wh = '';
        foreach($where as $v)
        {
          if(strlen($wh) > 0)
            $wh .= sprintf(' and %s%s"%s" ',$v[0],$v[1], mysql_real_escape_string($v[2], $this->db->link));
          else
            $wh .= sprintf('%s%s"%s"',$v[0],$v[1], mysql_real_escape_string($v[2], $this->db->link));
        }
        $strsql .= sprintf(' where %s', $wh);
      }elseif(is_string($where)){
        $strsql .= sprintf(' where %s', $where);
      }
    }
    $this->lastsql = $strsql;
    return $this->db->getOneRecord($strsql);
  }/*}}}*/

  function getinfos($where='',$tn='',$orderby='', $all='all',$limitl=-1,$limitr=-1)
  {/*{{{*/
    if(empty($tn))
      $tn = $this->tablename;

    $strsql = sprintf('select * from %s ', $tn);
    $countstrsql = sprintf('select count(*) count from %s',$tn);
    if(!empty($where))
    {
      if(is_array($where)){
        $wh = '';
        foreach($where as $v)
        {
          if(strlen($wh) > 0)
            $wh .= sprintf(' and %s%s"%s" ',$v[0],$v[1], mysql_real_escape_string($v[2], $this->db->link));
          else
            $wh .= sprintf('%s%s"%s"',$v[0],$v[1], mysql_real_escape_string($v[2], $this->db->link));
        }
        $strsql .= sprintf(' where %s', $wh);
        $countstrsql .= sprintf(' where %s', $wh);
      }elseif(is_string($where)){
        $strsql .= sprintf(' where %s', $where);
        $countstrsql .= sprintf(' where %s', $where);
      }
    }
    if(!empty($orderby))
      $strsql .= sprintf(' order by %s', $orderby);
    else
      $strsql .= sprintf(' order by %sid desc', $this->fieldpre);
    if($limitl != -1)
      $strsql .= sprintf(' limit %d', $limitl);
    if($limitr != -1)
      $strsql .= sprintf(' ,%d', $limitr);
    $this->lastsql = $strsql;
    if($all == 'all')
      return $this->db->executeRead($strsql);
    return array($this->db->getRowCount($countstrsql),$this->db->executeRead($strsql));
  }/*}}}*/

  function getidinfos($where='',$tn='',$orderby='', $all='all',$limitl=-1,$limitr=-1)
  {/*{{{*/
    if(empty($tn))
      $tn = $this->tablename;

    $strsql = sprintf('select %sid from %s ', $this->fieldpre, $tn);
    $countstrsql = sprintf('select count(*) count from %s',$tn);
    if(!empty($where))
    {
      if(is_array($where)){
        $wh = '';
        foreach($where as $v)
        {
          if(strlen($wh) > 0)
            $wh .= sprintf(' and %s%s"%s" ',$v[0],$v[1], mysql_real_escape_string($v[2], $this->db->link));
          else
            $wh .= sprintf('%s%s"%s"',$v[0],$v[1], mysql_real_escape_string($v[2], $this->db->link));
        }
        $strsql .= sprintf(' where %s', $wh);
        $countstrsql .= sprintf(' where %s', $wh);
      }elseif(is_string($where)){
        $strsql .= sprintf(' where %s', $where);
        $countstrsql .= sprintf(' where %s', $where);
      }
    }
    if(!empty($orderby))
      $strsql .= sprintf(' order by %s', $orderby);
    else
      $strsql .= sprintf(' order by %sid desc', $this->fieldpre);
    if($limitl != -1)
      $strsql .= sprintf(' limit %d', $limitl);
    if($limitr != -1)
      $strsql .= sprintf(' ,%d', $limitr);
    $this->lastsql = $strsql;
    if($all == 'all')
      return $this->db->executeRead($strsql);
    return array($this->db->getRowCount($countstrsql),$this->db->executeRead($strsql));
  }/*}}}*/

  public function getsql(){
    return $this->lastsql;
  }

  public function ExecuteSql($strsql){
    return $this->db->query($strsql);
  }

  public function ExecuteRead($strsql){
    
    return $this->db->ExecuteRead($strsql);
  }

  /*}}}*/

  /*{{{ 多对多关系操作 */
  public function SetRelation($lkey,$lid,$rkey, $rid, $tn='',$skey='')
  {/*{{{*/
    $succ = false;
    if(empty($tn))
      $tn = $this->tablename;
    $rinfo = $this->ExistRelation($lkey,$lid,$rkey,$rid,$tn);
    if($rinfo)
      $this->SetRelationState($lkey,$lid,$rkey,$rid,$skey,STATE_NOR,$tn);
    else
      $this->AddRelation($lkey,$lid,$rkey,$rid,$tn);
    $succ = true;
    return $succ;
  }/*}}}*/

  public function ExistRelation($lkey,$lid,$rkey,$rid,$tn='')
  {/*{{{*/
    if(empty($tn))
      $tn = $this->tablename;
    $strsql = sprintf('select * from %s where %s=%d and %s=%d', $tn, $lkey, $lid, $rkey, $rid);
    return $this->db->getOneRecord($strsql);
  }/*}}}*/

  public function SetRelationState($lkey, $lid, $rkey, $rid, $skey, $state, $tn='')
  {/*{{{*/
    if(empty($tn))
      $tn = $this->tablename;
    $strsql = sprintf('update %s set %sstate=%d where %s=%d and %s=%d ', $tn, $skey,$state, $lkey, $lid, $rkey, $rid);
    return $this->db->update($strsql);
  }/*}}}*/

  public function SetRelationStateByLkey($lkey,$lid,$skey,$state,$tn='')
  {/*{{{*/
    if(empty($tn))
      $tn = $this->tablename;
    $strsql = sprintf('update %s set %sstate=%d where %s=%d', $tn, $skey, $state, $lkey, $lid);
    return $this->db->update($strsql);
  }/*}}}*/

  public function GetRelationsByKey($key,$id, $tn='', $pre='')
  {/*{{{*/
    if(empty($pre))
      $pre  = $this->fieldpre;
    if(empty($tn))
      $tn = $this->tablename;

    return $this->getinfos(sprintf('%sstate!=%s and %s=%d', $pre, STATE_DEL, $key, $id), $tn);
  }/*}}}*/

  private function AddRelation($lkey,$lid,$rkey,$rid,$tn='')
  {/*{{{*/
    $id = false;
    if(empty($tn))
      $tn = $this->tablename;
    $strsql = sprintf('insert into %s(%s,%s) values(%d,%d)', $tn, $lkey,$rkey,$lid,$rid);
    $ret = $this->db->query($strsql);
    if($ret !== false)
      $id = $this->db->insert_id();
    return $id;
  }/*}}}*/
  /*}}}*/

  /*{{{ 用户相关 */
  function CheckUserByMobile($ui)
  {/*{{{*/
    $uinfo = false;
    $mobile = GetItemFromArray($ui, $this->fieldpre.'mobile');
    $passwd = GetItemFromArray($ui, $this->fieldpre.'passwd');
    if(strlen($mobile)>0 && strlen($passwd)>0)
    {
      $strsql = sprintf('select * from %s where (%smobile="%s") and %spasswd="%s"',
        $this->tablename,$this->fieldpre,mysql_real_escape_string($mobile, $this->db->link),
        $this->fieldpre,mysql_real_escape_string($passwd, $this->db->link)
      );
      $sqlret = $this->db->getOneRecord($strsql);
      if($sqlret)
      {
        $uid = $sqlret[$this->fieldpre.'id'];
        $uinfo = $this->find($uid);
        $_SESSION[$this->fieldpre.'id'] = $uid;
      }
    }

    return $uinfo;
  }/*}}}*/

  function CheckUserByName($uinfo,$pre='',$tn='')
  {/*{{{*/
    $uid = false;
    if(empty($pre))
      $pre = $this->fieldpre;
    if(empty($tn))
      $tn = $this->tablename;
    $name = GetItemFromArray($uinfo, $pre.'name');
    $passwd = GetItemFromArray($uinfo, $pre.'passwd');
    if(strlen($name)>0 && strlen($passwd)>0)
    {
      $strsql = sprintf('select * from %s where (%sname="%s") and %spasswd="%s"',
        $tn,$pre,mysql_real_escape_string($name,$this->db->link),
        $pre,mysql_real_escape_string($passwd, $this->db->link)
      );
      $sqlret = $this->db->getOneRecord($strsql);
      if($sqlret)
      {
        $uid = $sqlret[$pre.'id'];
        $_SESSION[$pre.'id'] = $uid;
      }
    }

    return $uid;
  }/*}}}*/

  function AddUser($uinfo)
  {/*{{{*/
    $uid = false;
    $type = '';
    $val = '';
    if(isset($uinfo[$this->fieldpre.'type']))
    {
      $type = sprintf(',%stype',$this->fieldpre);
      $val = sprintf(',%d', $uinfo[$this->fieldpre.'type']);
    }

    $strsql = sprintf('insert into %s(%smobile,%spaswd %s) values("%s","%s" %s)',
      $this->tablename, $this->fieldpre, $this->fieldpre,$type,
      mysql_real_escape_string($uinfo[$this->fieldpre."mobile"], $this->db->link),
      mysql_real_escape_string($uinfo[$this->fieldpre."passwd"], $this->db->link),$val
    );
    $ret = $this->db->query($strsql);
    if($ret !== false)
      $uid = $this->db->insert_id();
    return $uid;
  }/*}}}*/

  function SetUserPasswd($uid, $passwd)
  {/*{{{*/
    $succ = false;
    if(strlen($passwd) >= 6)
    {
      $strsql = sprintf('update %s set %spasswd where %suid=%d',
        $this->tablename, $this->fieldpre, $this->fieldpre, $uid);
      $succ = $this->db->update($strsql);
    }
    return $succ;
  }/*}}}*/

  function GetUserInfoByMobile($m)
  {/*{{{*/
    $strsql = sprintf('select * from %s where %smobile="%s"',
      $this->tablename, $this->fieldpre,mysql_real_escape_string($m,$this->db->link)
    );
    return $this->db->getOneRecord($strsql);
  }/*}}}*/

  function GetUserInfoByEmail($email)
  {/*{{{*/
    $strsql = sprintf('select * from %s where %semail="%s"',
      $this->tablename, $this->fieldpre,mysql_real_escape_string($m,$this->db->link)
    );
    return $this->db->getOneRecord($strsql);
  }/*}}}*/

  public function IsLogin($pre='',$tn='')
  {/*{{{ */
    if(empty($pre))
      $pre = $this->fieldpre;
    if(empty($tn))
      $tn = $this->tablename;
    $k = $pre.'id';
    $uid = GetItemFromArray($_SESSION, $k, 0);
    $uid = intval($uid);
    $uinfo = $this->find($uid,$tn,$pre);
    if($uinfo[$pre.'state'] != STATE_NOR)
      $uinfo = array();
    if(!empty($uinfo))
    {
      return $uinfo;
    }
    else
    {
      return false;
    }
  }/*}}}*/
  /*}}}}*/

};


?>
