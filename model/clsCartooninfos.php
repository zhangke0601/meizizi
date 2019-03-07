<?php
/*
 * 用途：用户相关操作
 * 作者：feb1234@163.com
 * 时间：2015-10-11
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/baseModel.php');

class Cartooninfos extends BaseModel
{
  function __construct()
  {/*{{{*/
    parent::__construct();
    $this->idkey = "ctid";
    $this->tablename = "cartooninfos";
    $this->fieldpre = 'ct';
  }/*}}}*/

  public function find($ctid,$tn='',$pre='')
  {/*{{{*/
    $ctinfo = parent::find($ctid,$tn,$pre);
    if(empty($tn))
    {
      if($ctinfo){
        $ctinfo['cttypeinfos'] = $this->GetRelationsByKey('ctid',$ctid,'cartoonandtypeinfos','catp');
        foreach($ctinfo['cttypeinfos'] as $idx=>$info)
        {
          $cttpid = $info['cttpid'];
          $cttpinfo = $this->find($cttpid,'cartoontypeinfos', 'cttp');
          $ctinfo['cttypeinfos'][$idx]['cttpname'] = $cttpinfo['cttpname'];
          $ctinfo['cttypeinfos'][$idx]['cttpdesc'] = $cttpinfo['cttpdesc'];
        }
        $ctinfo['ctsubinfos'] = $this->GetRelationsByKey('ctid',$ctid,'cartoonandsubjectinfos','cas');
        foreach($ctinfo['ctsubinfos'] as $idx=>$info)
        {
          $ctsuinfo = $this->find($info['ctsuid'], 'cartoonsubjectinfos','ctsu');
          $ctinfo['ctsubinfos'][$idx]['ctsuname'] = $ctsuinfo['ctsuname'];
        }
        if(empty($ctinfo['ctprices']))
          $ctinfo['ctprices'] = array();
        else
          $ctinfo['ctprices'] = json_decode($ctinfo['ctprices'], true);
      }
    }
    return $ctinfo;
  }/*}}}*/

  public function GetInfosByParams($s,$t,$p)
  {/*{{{*/
    $where = '1=1';
    if($s)
      $where.= sprintf(' and ctname like "%%%s%%"',mysql_real_escape_string($s, $this->db->link));
    if($t)
      $where .= sprintf(' and cttype=%d', $t);

    $p = intval($p);
    if($p < 1)
      $p = 1;
    $limitl = ($p-1)*COUNT_PER_PAGE;
    $limitr = COUNT_PER_PAGE;

    return $this->getinfos($where,'','','',$limitl, $limitr);
  }/*}}}*/

  public function GetInfosByKey($s,$uid=0)
  {/*{{{*/
    $where = 'ctstate!='.STATE_DEL;
    if($s)
      $where.= sprintf(' and ctname like "%%%s%%"',mysql_real_escape_string($s, $this->db->link));
    if($uid > 0)
      $where .= sprintf(' and uid=%d', $uid);

    return $this->getinfos($where);
  }/*}}}*/

  public function GetSelfInfosByParams($s,$p, $search)
  {/*{{{*/
    $where = 'ctstate!='.STATE_DEL;
    if($s)
      $where.= sprintf(' and ctstate=%d',$s);
    if($search)
      $where .= sprintf(' and ctname like "%%%s%%"', mysql_real_escape_string($search, $this->db->link));
    $where .= sprintf(' and cttype!=%d', TYPE_SOURCE_THIRD);

    $p = intval($p);
    if($p < 1)
      $p = 1;
    $limitl = ($p-1)*COUNT_PER_PAGE;
    $limitr = COUNT_PER_PAGE;

    return $this->getinfos($where,'','','',$limitl, $limitr);
  }/*}}}*/

  public function GetAllSelfInfos()
  {/*{{{*/
    $where = 'ctstate!='.STATE_DEL;
    $where .= sprintf(' and cttype!=%d', TYPE_SOURCE_THIRD);

    return $this->getinfos($where);
  }/*}}}*/

  public function CartoonExistForSourceAndSourceid($source,$sourceid)
  {/*{{{*/
    $csinfo = array();
    $infos = $this->getinfos(array(array('cssource','=',$source),array('cssourceid','=',$sourceid)), 'cartoonsourceinfos');
    foreach($infos as $info)
    {
      $ctid = $info['ctid'];
      $ctinfo = $this->find($ctid);
      if($ctinfo['cttype'] == TYPE_SOURCE_THIRD)
      {
        $csinfo = $info;
        $csinfo['ctname'] = $ctinfo['ctname'];
        break;
      }
    }
    return $csinfo;
  }/*}}}*/

  public function CartoonExistForNameAndAuthor($name,$author)
  {/*{{{*/
    $ctinfo = array();
    $infos = $this->getinfos(array(array('ctname','=',$name),array('ctauthorname','=',$author)));
    if(count($infos) > 0)
      $ctinfo = $infos[0];
    return $ctinfo;
  }/*}}}*/

  public function SectionExistForName($ctid, $ctsname)
  {/*{{{*/
    $ctsinfo = array();
    $info = $this->getoneinfo(array(array('ctsname','=',$ctsname), array('ctid','=',$ctid)), 'cartoonsectioninfos');
    //if($infos && (count($infos) > 0))
    //  $ctsinfo = $infos[0];
    return $info;
  }/*}}}*/

  public function CartoonSelfExistForSourceAndSourceid($source,$sourceid)
  {/*{{{*/
    $csinfo = array();
    $infos = $this->getinfos(array(array('cssource','=',$source),array('cssourceid','=',$sourceid)), 'cartoonsourceinfos');
    foreach($infos as $info)
    {
      $ctid = $info['ctid'];
      $ctinfo = $this->find($ctid);
      if($ctinfo['cttype'] == TYPE_SOURCE_USER)
      {
        $csinfo = $info;
        break;
      }
    }
    return $csinfo;
  }/*}}}*/

  public function CartoonSelfExistForCtidAndSource($ctid,$source)
  {/*{{{*/
    $info = $this->getoneinfo(array(array('cssource','=',$source),array('ctid','=',$ctid)), 'cartoonsourceinfos');
    return $info;
  }/*}}}*/

  public function CartoonSelfExistForName($name)
  {/*{{{*/
    $csinfo = array();
    $infos = $this->getinfos(array(array('ctname','=',mysql_real_escape_string($name,$this->db->link)),array('cttype','=',TYPE_SOURCE_USER)));
    if(count($infos) > 0)
      $csinfo = $infos[0];
    return $csinfo;
  }/*}}}*/

  public function CartoonSelfExistForNameAndUid($name,$uid)
  {/*{{{*/
    $csinfo = array();
    $infos = $this->getinfos(array(array('ctname','=',mysql_real_escape_string($name,$this->db->link)),array('cttype','=',TYPE_SOURCE_USER), array('uid','=', $uid)));
    if(count($infos) > 0)
      $csinfo = $infos[0];
    return $csinfo;
  }/*}}}*/

  public function SectionSelfExistForSourceAndSourceid($ctid, $source,$sourceid,$name)
  {/*{{{*/
    $ctsinfo = array();
    //$infos = $this->getinfos(array(array('ctssource','=',$source),array('ctssourceid','=',$sourceid), array('ctid','=',$ctid)), 'cartoonsectioninfos');
    $infos = $this->getinfos(array(array('pfid','=',$source), array('ctrrpfsectionid','=',$sourceid),array('ctid','=',$ctid)), 'cartoonreleaserecordinfos');
    if(!empty($infos))
    {
      $csinfo = $infos[0];
      $ctsinfo = $this->find($csinfo['ctsid'],'cartoonsectioninfos','cts');
    }
    else
    {
      $infos = $this->getinfos(array(array('ctsname','=',$name), array('ctid','=',$ctid)), 'cartoonsectioninfos');
      if(!empty($infos))
        $ctsinfo = $infos[0];
    }
    return $ctsinfo;
  }/*}}}*/

  public function SectionExistForCtidAndSourceAndSourceid($ctid, $source,$sourceid)
  {/*{{{*/
  }/*}}}*/

  public function GetSectionCopyInfosByCtsid($ctsid){
    /*{{{*/
    return $this->getinfos('ctsparentid='.$ctsid, 'cartoonsectioninfos');
  }/*}}}*/

  public function GetReleseInfoBySectionAndPlatform($ctsid, $pfid)
  {/*{{{*/
    $ctrrinfo = array();
    $infos = $this->getinfos(array(array('ctsid','=',$ctsid), array('pfid','=',$pfid)), 'cartoonreleaserecordinfos');
    if(!empty($infos))
      $ctrrinfo = $infos[0];
    return $ctrrinfo;
  }/*}}}*/

  public function GetLatestCartinfosByUid($uid)
  {/*{{{*/
    $strsql = sprintf('select * from cartooninfos where uid=%d order by ctid desc limit 1', $uid);
    $ctinfo = $this->db->getOneRecord($strsql);
    return $ctinfo;
  }/*}}}*/

  public function GetTypeInfos()
  {/*{{{*/
    return $this->getinfos(sprintf('cttpstate=%d',STATE_NOR), 'cartoontypeinfos','cttpsort , cttpid desc');
  }/*}}}*/

  public function GetTagInfos()
  {/*{{{*/
    return $this->getinfos(sprintf('cttstate=%d',STATE_NOR), 'cartoontaginfos','cttsort , cttid desc');
  }/*}}}*/
  public function GetSubjectInfos()
  {/*{{{*/
    return $this->getinfos(sprintf('ctsustate=%d',STATE_NOR), 'cartoonsubjectinfos','ctsusort , ctsuid desc');
  }/*}}}*/

  /*{{{ 发布相关 */
  public function GetReleaseRecordInfosByCtid($ctid)
  {/*{{{*/
    $ctrrinfos = $this->getinfos(sprintf('ctid=%d',$ctid), 'cartoonreleaserecordinfos', 'ctrrid desc');
    foreach($ctrrinfos as $idx=>$info)
    {
      $ctsid = $info['ctsid'];
      $ctsinfo = $this->find($ctsid,'cartoonsectioninfos','cts');
      if($ctsinfo['ctsstate'] == STATE_DEL)
        unset($ctrrinfos[$idx]);
    }
    $ctrrinfos = array_values($ctrrinfos);
    return $ctrrinfos;
  }/*}}}*/
  public function GetReleaseRecordInfosByCtsid($ctsid)
  {/*{{{*/
    $ctrrinfos = $this->getinfos(sprintf('ctsid=%d',$ctsid), 'cartoonreleaserecordinfos', 'ctrrid desc');
    return $ctrrinfos;
  }/*}}}*/
  public function GetReleaseRecordInfosByCtidAndPfid($ctid, $pfid)
  {/*{{{*/
    $ctrrinfos = $this->getinfos(sprintf('ctid=%d and pfid=%d', $ctid, $pfid), 'cartoonreleaserecordinfos');
    return $ctrrinfos;
  }/*}}}*/
  public function GetUnReleaseRecordInfos()
  {/*{{{*/
    $ctrrinfos = $this->getinfos(sprintf('ctrrstate=%d and ctrrtype=%d', STATE_NOR,STATE_NOR),'cartoonreleaserecordinfos','ctrrid');
    foreach($ctrrinfos as $idx=>$info)
    {
      $ctsid = $info['ctsid'];
      $ctsinfo = $this->find($ctsid,'cartoonsectioninfos','cts');
      if($ctsinfo['ctsstate'] == STATE_DEL)
        unset($ctrrinfos[$idx]);
    }
    $ctrrinfos = array_values($ctrrinfos);
    return $ctrrinfos;
  }/*}}}*/
  public function GetReleaseRecordInfosForIqiyi()
  {/*{{{*/
    $ctrrinfos = $this->getinfos(sprintf('ctrrstate!=%d and ctrrtype=%d and pfid=%d', STATE_OVER, STATE_NOR, SOURCE_AIQIYI),'cartoonreleaserecordinfos');
    return $ctrrinfos;
  }/*}}}*/
  public function GetReleaseRecordInfosForCookies()
  {/*{{{*/
    $ctrrinfos = $this->getinfos(sprintf('ctrrstate!=%d and ctrrtype=%d and pfid in (%d,%d)', STATE_OVER, STATE_NOR, SOURCE_AIQIYI, SOURCE_TENCENT),'cartoonreleaserecordinfos');
    foreach($ctrrinfos as $idx=>$info)
    {
      $ctsid = $info['ctsid'];
      $ctsinfo = $this->find($ctsid,'cartoonsectioninfos','cts');
      if($ctsinfo['ctsstate'] == STATE_DEL)
        unset($ctrrinfos[$idx]);
    }
    $ctrrinfos = array_values($ctrrinfos);
    return $ctrrinfos;
  }/*}}}*/
  public function GetLatestCookiesByUidAndPfid($uid, $pfid)
  {/*{{{*/
    $cookies = '';
    // 从两个地方获取cookies：1）同步记录；2）上传记录
    //$ctrrinfos = $this->getinfos(sprintf('ctrrstate!=%d and ctrrtype=%d and pfid in (%d)', STATE_OVER, STATE_NOR, $pfid),'cartoonreleaserecordinfos');
    $strsql = sprintf('select * from cartoonreleaserecordinfos where pfid=%d and exists(select * from cartooninfos where cartoonreleaserecordinfos.ctid=cartooninfos.ctid and cartooninfos.uid=%d) order by ctrrid desc limit 1', $pfid, $uid);
    $ctrrinfo = $this->db->getOneRecord($strsql);
    //$strsql = sprintf('select * from ');
    //$infos = $this->getinfos(sprintf('passtate=%d',STATE_NOR),'platformaccountsyncinfos');
    $strsql = sprintf('select * from platformaccountsyncinfos where uid=%d and pfid=%d order by pasid desc limit 1', $uid, $pfid);
    $pasinfo = $this->db->getOneRecord($strsql);
    $cookies = $ctrrinfo['ctrrcookies'];
    if(empty($cookies))
      $cookies = $pasinfo['pascookies'];
    else
    {
      if($pasinfo['pascreatetime'] > $ctrrinfo['ctrrcreatetime'])
        $cookies = $pasinfo['pascookies'];
    }
    return $cookies;
  }/*}}}*/

  public function GetCartoonReleaseInfo($ctid,$pfid)
  {/*{{{*/
    $csinfo = $this->getoneinfo(sprintf('ctid=%d and cssource=%d and csstate!=%d', $ctid, $pfid, STATE_DEL),'cartoonsourceinfos');
    return $csinfo;
  }/*}}}*/
  public function GetCartoonReleaseInfosByCtid($ctid)
  {/*{{{*/
    $csinfos = $this->getinfos(sprintf('ctid=%d', $ctid),'cartoonsourceinfos');
    return $csinfos;
  }/*}}}*/
  public function GetUnCartoonSourceInfos()
  {/*{{{*/
    $csinfos = $this->getinfos(sprintf("csstate=%d", STATE_UPLOADED), 'cartoonsourceinfos');
    return $csinfos;
  }/*}}}*/

  public function GetSourceInfosByCtid($ctid)
  {/*{{{*/
    $strsql = sprintf('select * from cartoonsourceinfos where ctid=%d', $ctid);
    return $this->db->executeRead($strsql);
  }/*}}}*/
  public function GetReleaseStatByCtid($ctid)
  {/*{{{*/
    $strsql = sprintf('select ctrrstate, count(ctrrstate) count from cartoonreleaserecordinfos where ctid=%d and exists(select * from cartoonsectioninfos where cartoonreleaserecordinfos.ctsid=cartoonsectioninfos.ctsid and cartoonsectioninfos.ctsstate!=%d) group by ctrrstate', $ctid, STATE_DEL);
    return $this->db->executeRead($strsql);
  }/*}}}*/
  public function GetReleaseInfosByCtidAndState($ctid, $state)
  {/*{{{*/
    $ctrrinfos = $this->getinfos(sprintf('ctid=%d and ctrrstate=%d', $ctid, $state), 'cartoonreleaserecordinfos','ctrrid desc');
    foreach($ctrrinfos as $idx=>$info)
    {
      $ctsid = $info['ctsid'];
      $ctsinfo = $this->find($ctsid,'cartoonsectioninfos','cts');
      if($ctsinfo['ctsstate'] == STATE_DEL)
        unset($ctrrinfos[$idx]);
    }
    $ctrrinfos = array_values($ctrrinfos);
    return $ctrrinfos;
  }/*}}}*/
  public function GetReleaseInfosByCtsid($ctsid)
  {/*{{{*/
    return $this->getinfos(sprintf('ctsid=%d', $ctsid), 'cartoonreleaserecordinfos','ctrrid desc');
  }/*}}}*/

  /*}}}*/

  /*{{{ 平台数据相关 */
  public function GetSelfDataInfosByCtid($ctid)
  {/*{{{*/
    $sdinfos = $this->getinfos(sprintf('ctid=%d',$ctid),'cartoonselfdatainfos','ctsdday desc');
    return $sdinfos;
  }/*}}}*/

  public function GetSelfDataInfosByUid($uid)
  {/*{{{*/
    $ctinfos = $this->getinfos(sprintf('uid=%d and ctstate!=%d', $uid, STATE_DEL));
    $ctids = GetItemsFromArray($ctinfos,'ctid');
    $sdinfos = $this->getinfos(sprintf('ctid in (%s)',implode(',',$ctids)),'cartoonselfdatainfos','ctsdday desc');
    return $sdinfos;
  }/*}}}*/
  /*}}}*/

  public function StudioGetCartooninfosByParams($uid,$p,$search='')
  {
    $p = intval($p);
    if($p<1)
      $p = 1;
    $where = sprintf('where ct.uid=u.uid and (u.uid=%d or u.upuid=%d) and ct.ctstate!=%d and ct.ctparentid=0',$uid,$uid,STATE_DEL);
    if(strlen($search))
      $where .= sprintf(' and ct.ctname like "%%%s%%"',mysql_real_escape_string($search,$this->db->link));
    $sql = sprintf('select ct.* from cartooninfos ct,userinfos u %s order by ctid desc limit %d,%d ',
        $where,($p-1)*COUNT_PER_PAGE,COUNT_PER_PAGE);

    $countssql = sprintf('select count(*) count from cartooninfos ct,userinfos u %s',$where);
    return array($this->db->getRowCount($countssql),$this->db->executeRead($sql));
  }

  /* 通过类型名称获取类型信息 */
  function GetcartoontypeinfoBycttpname($cttpname)
  {
    $cttpinfo = $this->getoneinfo(sprintf('cttpname="%s" and cttpstate!=%d', $cttpname, STATE_DEL),'cartoontypeinfos');
    return $cttpinfo;
  }

  /*{{{ 入库用户上传漫画的统计信息 */
  function AddSelfDataInfo($sdinfo)
  {/*{{{*/
    $ctid = $sdinfo['ctid'];
    $pfid = $sdinfo['pfid'];
    $ctsdday = $sdinfo['ctsdday'];
    $info = $this->existForDay($ctid,$pfid,$ctsdday);
    if(empty($info))
      $this->add($sdinfo, 'cartoonselfdatainfos');
    else
      $this->update($info['ctsdid'], $sdinfo, 'ctsd','cartoonselfdatainfos');
    return true;
  }/*}}}*/

  private function existForDay($ctid,$pfid,$ctsdday)
  {/*{{{*/
    $info = $this->getoneinfo(sprintf('ctid=%d and pfid=%d and ctsdday="%s"',$ctid,$pfid,$ctsdday), 'cartoonselfdatainfos');
    return $info;
  }/*}}}*/
  /*}}}*/
};

?>
