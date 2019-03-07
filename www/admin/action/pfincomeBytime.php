<?php
/*
 * 用途：处理前端用户请求
 * 作者：
 * 时间：2018-08-21
 *
 * */
$base_dir = dirname(__FILE__).'/../../../';
require_once($base_dir.'inc/init.php');
require_once($base_dir.'inc/PHPExcel/PHPExcel.php');
require_once($base_dir.'inc/util_for_shortmsg.php');
require_once($base_dir.'inc/util_for_meizizi.php');
require_once($base_dir.'config/config.php');
require_once($base_dir.'model/clsUserinfos.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsPlatforminfos.php');
require_once($base_dir.'model/clsThirduserinfos.php');

$ajaxret = array('retno'=>RETNO_FAIL);
$logger = 'meizizi.admin.cartoon';
qLogConfig($base_dir.'config/qlog.cfg');

$params = $_POST;
qLogInfo($logger, json_encode($params));
$type = GetItemFromArray($params, 'type');
$user = new Userinfos();
$cart = new Cartooninfos();
$sect = new CartoonSectioninfos();
$pf = new Platforminfos();
$tu = new Thirduserinfos();

$uinfo = $user->IsLogin();
if($uinfo !== false)
{
    $ajaxret['retno'] = 0;
    $pfid =  $_POST['pfid']; //平台id
    $month = $_POST['time'];
    $sqlpf = sprintf('select pfname from platforminfos where pfid = %d limit 1',$pfid);
    $ajaxret['paltinfo'] = ($cart->ExecuteRead($sqlpf))[0]['pfname'];
    if($uinfo['utype'] == USERTYPE_STUDIOMANAGE){
        //工作室管理员
        $uid = $uinfo['upuid'];
    }else{
        $uid = $uinfo['uid'];
    }

    $sql = sprintf('select * from cartooninfos where uid = %d and ctstate != 400', $uid);

//    $ajaxret['sql'] = $sql;
    $cartoons = $cart->ExecuteRead($sql);
    $data = [];
    $first = $month.'-01';
    $last = date('Y-m-d',strtotime($first.'+1 month -1 day'));
    $day = date('d',time());

    foreach ($cartoons as $cartoon){

        if(empty($pfid)){
            $dsql = sprintf('select sum(ctsddayincome) as income from cartoonselfdatainfos where ctid=%s and  ctsdday >= "%s" AND ctsdday <= "%s"', $cartoon['ctid'], $first, $last);
        }else{
            $dsql = sprintf('select sum(ctsddayincome) as income from cartoonselfdatainfos where ctid=%s and pfid=%d and  ctsdday >= "%s" AND ctsdday <= "%s"', $cartoon['ctid'], $pfid, $first, $last);
        }
        $incomes = $cart->ExecuteRead($dsql);

        $fincome = sprintf('%.2f',$incomes[0]['income']);

        $data[] = ['ctid' => $cartoon['ctid'], 'ctname' =>$cartoon['ctname'],  'fincome'=>$fincome,'sql'=>$dsql];
    }


    $ajaxret['data'] = $data;
}
else
{
  $ajaxret['retno'] = RETNO_NOTLOGIN;
  $ajaxret['msg'] = '登陆超时';
}

echo json_encode($ajaxret);

?>
