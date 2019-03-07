<?php
/*
 * 用途：非归类相关操作
 * 作者：feb1234@163.com
 * 时间：2017-05-01
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/baseModel.php');

class Func extends BaseModel
{
  function __construct()
  {/*{{{*/
    global $cfg;
    $this->cfg = $cfg;
    $this->db = new DbDriver($this->cfg['db']['host'], $this->cfg['db']['dbname'], $this->cfg['db']['username'], $this->cfg['db']['password']); 
  }/*}}}*/

  /*{{{ DistrictInfo*/
  public function GetDetailAddrByCountyID($ciid)
  {/*{{{*/
    $addr = '';
    if($ciid > 0)
    {
      $strsql = sprintf('select * from cityinfos where ciid=%d', $ciid);
      $ciinfo = $this->db->getOneRecord($strsql);
      $addr = $ciinfo['ciname'];

      $ciid = $ciinfo['up2layer'];
      $strsql = sprintf('select * from cityinfos where ciid=%d', $ciid);
      $ciinfo = $this->db->getOneRecord($strsql);
      $addr = $ciinfo['ciname'] . $addr;

      $ciid = $ciinfo['up1layer'];
      $strsql = sprintf('select * from cityinfos where ciid=%d', $ciid);
      $ciinfo = $this->db->getOneRecord($strsql);
      $addr = $ciinfo['ciname'] . $addr;
    }

    return $addr;
  }/*}}}*/

  public function GetDistrictInfos()
  {/*{{{*/
    $strsql = sprintf('select id,name from districtinfos where level=1');
    $l1infos = $this->db->executeRead($strsql);
    $l1infos = SetKeyFromArray($l1infos, 'id');
    foreach($l1infos as $key=>$row)
    {
      $l1id = $row['id'];
      $strsql = sprintf('select id,name from districtinfos where upid=%d and level=2', $l1id);
      $l2infos = $this->db->executeRead($strsql);
      $l2infos = SetKeyFromArray($l2infos, 'id');
      foreach($l2infos as $k=>$r)
      {
        $l2id = $r['id'];
        $strsql = sprintf('select id,name from districtinfos where upid=%d and level=3', $l2id);
        $l3infos = $this->db->executeRead($strsql);
        $l3infos = SetKeyFromArray($l3infos, 'id');
        $l2infos[$k]['down'] = $l3infos;
      }
      $l1infos[$key]['down'] = $l2infos;
    }
    return $l1infos;
  }/*}}}*/

  public function GetDistrictInfosForAndroid()
  {/*{{{*/
    $strsql = sprintf('select id,name from districtinfos where level=1');
    $l1infos = array_merge(array(array('id'=>0,'name'=>'请选择')), $this->db->executeRead($strsql));
    foreach($l1infos as $key=>$row)
    {
      $l1id = $row['id'];
      $strsql = sprintf('select id,name from districtinfos where upid=%d and level=2', $l1id);
      $l2infos = array_merge(array(array('id'=>0,'name'=>'请选择')), $this->db->executeRead($strsql));
      foreach($l2infos as $k=>$r)
      {
        $l2id = $r['id'];
        $strsql = sprintf('select id,name from districtinfos where upid=%d and level=3', $l2id);
        $l3infos = array_merge(array(array('id'=>0,'name'=>'请选择')), $this->db->executeRead($strsql));
        $l2infos[$k]['down'] = $l3infos;
      }
      $l1infos[$key]['down'] = $l2infos;
    }
    return $l1infos;
  }/*}}}*/

  public function CountyIsExist($countyid)
  {/*{{{*/
    $ret = false;
    $strsql = sprintf('select * from cityinfos where ciid=%d', $countyid);
    $getret = $this->db->executeRead($strsql);
    if(!empty($getret))
      $ret = true;
    return $ret;
  }/*}}}*/

  public function GetDistrictNameById($id)
  {/*{{{*/
    $name = '';
    $info = $this->GetDistrictInfoById($id);
    if(!empty($info))
      $name = $info['name'];
    return $name;
  }/*}}}*/

  public function GetProviceNameById($id)
  {/*{{{*/
    $name = '';
    $info = $this->GetDistrictInfoById($id);
    if(!empty($info))
    {
      if($info['level'] == 1)
        $name = $info['name'];
      else if($info['level'] == 2)
      {
        $info = $this->GetDistrictInfoById($info['upid']);
        if(!empty($info))
          $name = $info['name'];
      }
    }

    return $name;
  }/*}}}*/

  public function GetCityInfosByProviceId($proviceid)
  {/*{{{*/
    $strsql = sprintf('select * from districtinfos where upid=%d and level=2', $proviceid);
    return $this->db->executeRead($strsql);
  }/*}}}*/

  public function GetProviceIdByCityId($cityid)
  {/*{{{*/
    $strsql = sprintf('select * from districtinfos where id=%d', $cityid);
    $cityinfo = $this->db->getOneRecord($strsql);
    return $cityinfo['upid'];
  }/*}}}*/

  public function GetCityIdByDistrictId($diid)
  {/*{{{*/
    $strsql = sprintf('select * from districtinfos where id=%d', $diid);
    $cityinfo = $this->db->getOneRecord($strsql);
    return $cityinfo['upid'];
  }/*}}}*/

  public function GetCityInfoByCiid($ciid)
  {/*{{{*/
    $strsql = sprintf('select * from districtinfos where id=%d', $ciid);
    return $this->db->getOneRecord($strsql);
  }/*}}}*/

  public function GetDistrictInfosByKey($key)
  {/*{{{*/
    $strsql = sprintf('select * from districtinfos where name like "%%%s%%"', mysql_real_escape_string($key,$this->db->link));
    return $this->db->executeRead($strsql);
  }/*}}}*/

  public function GetFullDistrictNameByDiid($diid)
  {/*{{{*/
    $diname = $this->GetDistrictNameById($diid);
    $ciid = $this->GetCityIdByDistrictId($diid);
    $ciinfo = $this->GetCityInfoByCiid($ciid);
    $prname = $this->GetProviceNameById($ciid);
    return sprintf('%s %s %s', $prname, $ciinfo['name'], $diname);
  }/*}}}*/

  public function GetFullCityNameByDiid($diid)
  {/*{{{*/
    $ciid = $this->GetCityIdByDistrictId($diid);
    $ciinfo = $this->GetCityInfoByCiid($ciid);
    $prname = $this->GetProviceNameById($ciid);
    return sprintf('%s %s', $prname, $ciinfo['name']);
  }/*}}}*/

  public function GetDistrictIdFromDistrictName($provice, $city, $district)
  {/*{{{*/
    $diid = 0;
    $proviceid = $this->GetIdFromName($provice, 1);
    if($proviceid > 0)
    {
      $cityid = $this->GetIdFromName($city, 2, $proviceid);
      if($cityid > 0)
        $diid = $this->GetIdFromName($district, 3, $cityid);
    }

    return $diid;
  }/*}}}*/

  public function GetCityIdFromCityName($provice, $city)
  {/*{{{*/
    $cityid = 0;
    $proviceid = $this->GetIdFromName($provice, 1);
    if($proviceid > 0)
      $cityid = $this->GetIdFromName($city, 2, $proviceid);

    return $cityid;
  }/*}}}*/
  public function GetDistrictInfosByCid($id)
  {/*{{{*/
    $strsql = sprintf('select * from districtinfos where upid=%d', $id);
    return $this->db->executeRead($strsql);
  }/*}}}*/

  public function GetCityInfosByPid($id)
  {/*{{{*/
    $strsql = sprintf('select * from districtinfos where upid=%d', $id);
    return $this->db->executeRead($strsql);
  }/*}}}*/

  public function GetSameLayerInfosById($id)
  {/*{{{*/
    $strsql = sprintf('select * from districtinfos where exists( select * from districtinfos d where d.upid=districtinfos.upid and d.id=%d);', $id);
    return $this->db->executeRead($strsql);
  }/*}}}*/

  private function GetIdFromName($name, $level, $upid=0)
  {/*{{{*/
    $id = 0;
    $strsql = sprintf('select * from districtinfos where name="%s" and level=%d and upid=%d ', $name, $level, $upid);
    $ret = $this->db->getOneRecord($strsql);
    if(!empty($ret))
    {
      $id = $ret['id'];
    }
    else
    {
      $name .= '市';
      $strsql = sprintf('select * from districtinfos where name="%s" and level=%d and upid=%d ', $name, $level, $upid);
      $ret = $this->db->getOneRecord($strsql);
      if(!empty($ret))
        $id = $ret['id'];
    }

    return $id;
  }/*}}}*/

  public function GetDistrictInfoById($id)
  {/*{{{*/
    $id = intval($id);
    $strsql = sprintf('select * from districtinfos where id=%d', $id);
    return $this->db->getOneRecord($strsql);
  }/*}}}*/

  /*}}}*/

  public function GetBankInfos()
  {/*{{{*/
    $strsql = sprintf('select * from bankinfos');
    return $this->db->executeRead($strsql);
  }/*}}}*/

  /*{{{ user account 管理员端 */

  /*{{{ 管理员端 */
  public function AddUseraccountFromShopForPartner($cpuid, $uid, $commi, $ebi)
  {/*{{{*/
    return $this->AddUseraccountFromTjForPartner(USERACCOUNT_TJSHOP, $cpuid, $uid, $commi, $ebi);
  }/*}}}*/

  public function AddUseraccountFromWorkerForPartner($cpuid, $uid, $commi, $ebi)
  {/*{{{*/
    return $this->AddUseraccountFromTjForPartner(USERACCOUNT_TJWORKER, $cpuid, $uid, $commi, $ebi);
  }/*}}}*/


  private function AddUseraccountFromTjForPartner($type, $cpuid, $uid, $commi, $ebi)
  {/*{{{*/
    $exist = $this->ExistUseraccountForPartnerTj($type, $cpuid, $uid);
    if($exist === false)
    {
      $balanceinfo = $this->GetPartnerAccountInfo($cpuid);
      if(empty($balanceinfo))
      {
        $strsql = sprintf('insert into useraccountinfos(uatype,uautype,uauid,uabalance,uaincome,uaebibalance,uaebiincome,uaoid) values(%d,%d,%d,%.2f, %.2f, %.2f, %.2f, %d)', $type, USERTYPE_PARTNER, $cpuid, $commi, $commi, $ebi, $ebi, $uid);
      }
      else
      {
        $strsql = sprintf('insert into useraccountinfos(uatype,uautype,uauid,uabalance,uaincome,uaebibalance,uaebiincome, uaoid) values(%d,%d,%d,%.2f, %.2f, %.2f, %.2f, %d)', $type, USERTYPE_PARTNER, $cpuid, $commi+$balanceinfo['uabalance'], $commi, $ebi+$balanceinfo['uaebibalance'], $ebi, $uid);
      }
      $ret = $this->db->query($strsql);
      if($ret !== false)
        $exist = true;
    }

    return $exist;
  }/*}}}*/

  private function GetPartnerAccountInfo($cpuid)
  {/*{{{*/
    $strsql = sprintf('select * from useraccountinfos where uautype=%d and uauid=%d order by uaid desc limit 1', USERTYPE_PARTNER, $cpuid);
    return $this->db->GetOneRecord($strsql);
  }/*}}}*/

  private function ExistUseraccountForPartnerTj($type, $cpuid, $uid)
  {/*{{{*/
    $exist = false;
    $strsql = sprintf('select * from useraccountinfos where uatype=%d and uauid=%d and uautype=%d and uaoid=%d', $type, $cpuid, USERTYPE_PARTNER, $uid);
    $ret = $this->db->executeRead($strsql);
    if(!empty($ret))
      $exist = true;
    return $exist;
  }/*}}}*/
  /*}}}*/

  /*{{{ 城市合伙人 */
  public function GetTjShopInfoByCpuid($cpuid)
  {/*{{{*/
    return $this->GetTjInfoByCpuid($cpuid, USERACCOUNT_TJSHOP);
  }/*}}}*/

  public function GetTjWorkerInfoByCpuid($cpuid)
  {/*{{{*/
    return $this->GetTjInfoByCpuid($cpuid, USERACCOUNT_TJWORKER);
  }/*}}}*/

  public function GetOrderAccountInfoByCpuid($cpuid)
  {/*{{{*/
    return $this->GetTjInfoByCpuid($cpuid, USERACCOUNT_ORDER);
  }/*}}}*/

  private function GetTjInfoByCpuid($cpuid, $type)
  {/*{{{*/
    $strsql = sprintf('select sum(uaincome) jine, sum(uaebiincome) ebi, count(1) count from useraccountinfos where uauid=%d and uatype=%d and uautype=%d', $cpuid, $type, USERTYPE_PARTNER);
    $info = $this->db->getOneRecord($strsql);
    if(empty($info['jine']))
    {
      $info['jine'] = 0;
      $info['ebi'] = 0;
    }
    return array('jine'=>sprintf("%.2f",GetItemFromArray($info,'jine')),
      'ebi'=>sprintf("%.2f",GetItemFromArray($info,'ebi')), 'count'=>intval(GetItemFromArray($info,'count')));
  }/*}}}*/

  public function GetProfitInfoFromDate($cpuid, $begdate, $enddate, $type=-1)
  {/*{{{*/
    $strsql = sprintf('select sum(uaincome) income, sum(uaebiincome) ebi, count(1) count from useraccountinfos where uautype=%d and uauid=%d and uacreatetime>="%s" and uacreatetime<"%s" and uaincome>0 %s', USERTYPE_PARTNER, $cpuid, $begdate, $enddate, ($type==-1)?'':sprintf('and uatype=%d',$type));
    $ret = $this->db->getOneRecord($strsql);
    if($ret['income'] == null)
    {
      $ret['income'] = 0;
      $ret['ebi'] = 0;
      $ret['count'] = 0;
    }
    else
    {
      $ret['income'] = sprintf("%.2f", $ret['income']);
      $ret['ebi'] = sprintf("%.2f", $ret['ebi']);
    }
    return $ret;
  }/*}}}*/

  /*}}}*/

  public function GetUserAccountInfoByUaid($uaid)
  {/*{{{*/
    $strsql = sprintf('select * from useraccountinfos where uaid=%d', $uaid);
    return $this->db->getOneRecord($strsql);
  }/*}}}*/

  public function SetUserAccountState($uaid, $state)
  {/*{{{*/
    $update = false;
    $strsql = sprintf('update useraccountinfos set uastate=%d where uaid=%d', $state, $uaid);
    $ret = $this->db->query($strsql);
    if($ret !== false)
      $update = true;
    return $update;
  }/*}}}*/

  public function SetUserAccountForFirstOrder($uaid, $jiesuaninfo)
  {/*{{{*/
    $update = false;
    $strsql = sprintf('update useraccountinfos set uastate=%d where uaid=%d', ORDERSTATE_AFTPAID, $uaid);
    $ret = $this->db->query($strsql);
    if($ret !== false)
      $update = true;
    return $update;
  }/*}}}*/

  public function GetUserAccountInfosByWuid($wuid)
  {/*{{{*/
    $strsql = sprintf('select * from useraccountinfos where uautype=%d and uauid=%d and uastate<%d order by uaid desc', USERTYPE_WORKER, $wuid, STATE_DEL);
    return $this->db->executeRead($strsql);
  }/*}}}*/

  public function GetOrderUserAccountCountForWuid($wuid)
  {/*{{{*/
    $strsql = sprintf('select count(*) count from useraccountinfos where uautype=%d and uauid=%d and uatype=%d', USERTYPE_WORKER, $wuid, USERACCOUNT_ORDER);
    return $this->db->getRowCount($strsql);
  }/*}}}*/

  public function GetUserAccountCashInfosForWorker()
  {/*{{{*/
    $strsql = sprintf('select * from useraccountinfos where uastate=%d and uatype=%d and uautype=%d', STATE_NOR, USERACCOUNT_PAYOUT, USERTYPE_WORKER);
    return $this->db->executeRead($strsql);
  }/*}}}*/

  /*}}}*/

  /*{{{ USERRECORD */
  public function AddAdminOptRecordInfo($rinfo)
  {/*{{{*/
    return $this->AddOptRecordInfo(USERTYPE_AUTH, $rinfo);
  }/*}}}*/

  public function AddShopOptRecordInfo($rinfo)
  {/*{{{*/
    return $this->AddOptRecordInfo(USERTYPE_SHOP, $rinfo);
  }/*}}}*/

  public function AddOwnerOptRecordInfo($rinfo)
  {/*{{{*/
    return $this->AddOptRecordInfo(USERTYPE_OWNER, $rinfo);
  }/*}}}*/

  public function AddPartnerOptRecordInfo($rinfo)
  {/*{{{*/
    return $this->AddOptRecordInfo(USERTYPE_PARTNER, $rinfo);
  }/*}}}*/

  public function AddWorkerOptRecordInfo($rinfo)
  {/*{{{*/
    return $this->AddOptRecordInfo(USERTYPE_WORKER, $rinfo);
  }/*}}}*/

  public function AddPriceOptRecordInfo($rinfo)
  {/*{{{*/
    return $this->AddOptRecordInfo(ROBJTYPE_PRICE, $rinfo);
  }/*}}}*/

  public function AddOrderOptRecordInfo($rinfo)
  {/*{{{*/
    return $this->AddOptRecordInfo(ROBJTYPE_ORDER, $rinfo);
  }/*}}}*/

  public function AddComplainOptRecordInfo($rinfo)
  {/*{{{*/
    return $this->AddOptRecordInfo(ROBJTYPE_COMPLAIN, $rinfo);
  }/*}}}*/

  public function AddJiesuanOptRecordInfo($rinfo)
  {/*{{{*/
    return $this->AddOptRecordInfo(ROBJTYPE_JIESUAN, $rinfo);
  }/*}}}*/

  public function AddOptRecordInfo($utype, $rinfo)
  {/*{{{*/
    $rid = false;
    $strsql = sprintf('insert into useroperaterecordinfos(uid,rutype,rotype,roid,ractiontype,rcontent) values(%d,%d,"%s",%d,"%s","%s")',
      $rinfo['uid'], $utype, $rinfo['rotype'], $rinfo['roid'],
      mysql_real_escape_string($rinfo['ractiontype'],$this->db->link),
      mysql_real_escape_string($rinfo['rcontent'],$this->db->link));
    $ret = $this->db->query($strsql);
    if($ret !== false)
      $rid = $this->db->insert_id();
    return $rid;
  }/*}}}*/

  public function GetOrderOptRecordInfos($oid)
  {/*{{{*/
    $strsql = sprintf('select * from useroperaterecordinfos where rotype=%d and roid=%d order by rid desc', ROBJTYPE_ORDER, $oid);
    return $this->db->executeRead($strsql);
  }/*}}}*/



  public function GetProviceinfoById($id)
  {/*{{{*/
    $proviceinfo = '';
    $info = $this->GetDistrictInfoById($id);
    if(!empty($info))
    {
      if($info['level'] == 1)
        $proviceinfo = $info ;
      else if($info['level'] == 2)
      {
        $info = $this->GetDistrictInfoById($info['upid']);
        if(!empty($info))
          $proviceinfo = $info ;
      }
    }

    return $proviceinfo ;
  }/*}}}*/
  /*}}}*/

  public function GetValueFromKey($key)
  {/*{{{*/
    $val = '';
    $strsql = sprintf('select * from constiteminfos where ciname="%s"', mysql_real_escape_string($key,$this->db->link));
    $ciinfo = $this->db->getOneRecord($strsql);
    if($ciinfo)
      $val = $ciinfo['civalue'];
    return $val;
  }/*}}}*/

  public function SetKeyAndValue($key,$val)
  {/*{{{*/
    $strsql = sprintf('select * from constiteminfos where ciname="%s"', mysql_real_escape_string($key,$this->db->link));
    $ciinfo = $this->db->getOneRecord($strsql);
    if($ciinfo)
    {
      $strsql = sprintf('update constiteminfos set civalue="%s" where ciname="%s"', mysql_real_escape_string($val, $this->db->link), mysql_real_escape_string($key,$this->db->link));
      $this->db->update($strsql);
    }
    else
    {
      $strsql = sprintf('insert into constiteminfos(ciname,civalue) values("%s","%s")', mysql_real_escape_string($key,$this->db->link), mysql_real_escape_string($val,$this->db->link));
      $this->db->query($strsql);
    }

    return true;
  }/*}}}*/

  public function SetQudaodesc($val){
    return $this->SetKeyAndValue('qudaodesc', $val);
  }
  public function GetQudaodesc(){
    return $this->GetValueFromKey('qudaodesc');
  }
};

?>
