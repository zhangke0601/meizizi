<?php
/*
 * 用途：处理前端用户请求
 * 作者：
 * 时间：2018-08-21
 *
 * */
$base_dir = dirname(__FILE__) . '/../../../';
require_once($base_dir . 'inc/init.php');
require_once($base_dir . 'inc/PHPExcel/PHPExcel.php');
require_once($base_dir . 'inc/util_for_shortmsg.php');
require_once($base_dir . 'inc/util_for_meizizi.php');
require_once($base_dir . 'config/config.php');
require_once($base_dir . 'model/clsUserinfos.php');
require_once($base_dir . 'model/clsCartooninfos.php');
require_once($base_dir . 'model/clsCartoonSectioninfos.php');
require_once($base_dir . 'model/clsPlatforminfos.php');
require_once($base_dir . 'model/clsThirduserinfos.php');

$ajaxret = array('retno' => RETNO_FAIL);
$logger = 'meizizi.admin.cartoon';
qLogConfig($base_dir . 'config/qlog.cfg');

$params = $_POST;
qLogInfo($logger, json_encode($params));
$type = GetItemFromArray($params, 'type');
$user = new Userinfos();
$cart = new Cartooninfos();
$sect = new CartoonSectioninfos();
$pf = new Platforminfos();
$tu = new Thirduserinfos();

$uinfo = $user->IsLogin();
if ($uinfo !== false) {
    $ajaxret['retno'] = 0;
    $pfid = $_POST['pfid']; //平台id
    $sqlpf = sprintf('SELECT pfname FROM platforminfos WHERE pfid = %d LIMIT 1', $pfid);
    $ajaxret['paltinfo'] = ($cart->ExecuteRead($sqlpf))[0]['pfname'];
    if ($uinfo['utype'] == USERTYPE_STUDIOMANAGE) {
        //工作室管理员
        $uid = $uinfo['upuid'];
    } else {
        $uid = $uinfo['uid'];
    }

    $sql = sprintf('SELECT * FROM cartooninfos WHERE uid = %d AND ctstate != 400', $uid);

//    $ajaxret['sql'] = $sql;
    $cartoons = $cart->ExecuteRead($sql);
    $data = [];
    $first = date('Y-m', time()) . '-01';
    $day = date('d', time());

    foreach ($cartoons as $cartoon) {


        $dsql = sprintf('SELECT ctsdday,sum(ctsddayincome) AS income FROM cartoonselfdatainfos WHERE ctid=%s AND pfid=%d AND  ctsdday >= "%s" GROUP BY ctsdday ORDER BY ctsdday ASC', $cartoon['ctid'], $pfid, $first);

        $incomes = $cart->ExecuteRead($dsql);

        $incomefinal = [];
        for ($i = 1; $i <= $day; $i++) {
            $incomefinal[] = [
                'ctsdday' => date('Y-m-', time()) . str_pad($i, 2, "0", STR_PAD_LEFT),
                'income' => 0
            ];
        }
        if (!empty($incomes)) {
            foreach ($incomefinal as &$item) {
                foreach ($incomes as $income) {
                    if ($item['ctsdday'] == $income['ctsdday']) {
                        $item['income'] = $income['income'];
                        break;
                    }
                }

            }
        }

        //$data[] = ['ctid' => $cartoon['ctid'], 'ctname' =>$cartoon['ctname'], 'income'=>$incomes, 'fincome'=>$incomefinal];
        $data[] = ['ctid' => $cartoon['ctid'], 'ctname' => $cartoon['ctname'], 'fincome' => $incomefinal];
    }

    foreach ($data as $k => $v) {
        $tt = 0;
        foreach ($v['fincome'] as $t) {
            if ($t['income'] > 0) {
                $tt = $tt + 1;
            }
        }
        if ($tt == 0) {
            unset($data[$k]);
        }
        //$data[$k]['key'] = $tt;
    }


    $ajaxret['data'] = $data;
} else {
    $ajaxret['retno'] = RETNO_NOTLOGIN;
    $ajaxret['msg'] = '登陆超时';
}

echo json_encode($ajaxret);

?>
