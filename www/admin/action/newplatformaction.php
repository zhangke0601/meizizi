<?php
/*
 * 用途：处理前端用户请求
 * 作者：feb1234@163.com
 * 时间：2017-09-09
 * */
$base_dir = dirname(__FILE__) . '/../../../';
require_once($base_dir . 'inc/init.php');
require_once($base_dir . 'inc/PHPExcel/PHPExcel.php');
require_once($base_dir . 'inc/util_for_shortmsg.php');
require_once($base_dir . 'inc/util_for_simulate.php');
require_once($base_dir . 'config/config.php');
require_once($base_dir . 'model/clsUserinfos.php');
require_once($base_dir . 'model/clsPlatforminfos.php');
require_once($base_dir . 'model/clsComicUserplatform.php');

$ajaxret = array('retno' => RETNO_FAIL);
$logger = 'meizizi.admin.platform';
qLogConfig($base_dir . 'config/qlog.cfg');

$params = $_POST;
qLogInfo($logger, json_encode($params));
$type = GetItemFromArray($params, 'type');
$user = new Userinfos();

$uinfo = $user->IsLogin();
$model = new ComicUserplatform();
if ($uinfo !== false) {
    $uid = $uinfo['uid'];
    if ($type == 'getaccountbyupfid') {
        $ajaxret['retno'] = RETNO_SUCC;
        $id = GetItemFromArray($params, 'upfid');
        $sql = sprintf('SELECT * FROM comic_user_platform WHERE id = %d LIMIT 1', $id);
        $pfinfos = $model->ExecuteRead($sql);
        $ajaxret['result'] = $pfinfos[0];
    } elseif ($type == 'setcookiesforupfid') {
        $id = GetItemFromArray($params, 'upfid');
        $cookies = htmlspecialchars(GetItemFromArray($params, 'cookies'));
        $cookies = trim($cookies);
        $sql = sprintf("UPDATE `comic_user_platform` SET `cookies`= '%s',`cookies_state` = %d WHERE `id`= %d", $cookies, STATE_COOKIES, $id);
//      var_dump($sql);die;
        $res = $model->ExecuteSql($sql);
//      var_dump($res);die;
        $ajaxret['retno'] = RETNO_SUCC;
    } else {
        $ajaxret['retno'] = RETNO_INVALIDOPT;
        $ajaxret['msg'] = '无效操作';
    }
} else {
    $ajaxret['retno'] = RETNO_NOTLOGIN;
    $ajaxret['msg'] = '登陆超时';
}

echo json_encode($ajaxret);
$ajaxret['uid'] = $uid;
qLogInfo($logger, json_encode($ajaxret));
?>
