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
require_once($base_dir . 'config/config.php');
require_once($base_dir . 'model/clsUserinfos.php');
require_once($base_dir . 'model/clsDictinfos.php');

$ajaxret = array('retno' => RETNO_FAIL);
$logger = 'meizizi.admin.dict';

$params = $_POST;
$type = GetItemFromArray($params, 'type');
$user = new Userinfos();
$dict = new Dictinfos();
$uinfo = $user->IsLogin();
if ($uinfo !== false) {
    $uid = $uinfo['uid'];
    if ($type == 'gettaginfobycttid') {/*{{{*/
        $cttid = GetItemFromArray($params, 'cttid');
        $taginfo = $dict->GetTagInfoByCttid($cttid);
        $ajaxret['retno'] = RETNO_SUCC;
        $ajaxret['result'] = $taginfo;
    }/*}}}*/
    elseif ($type == 'addtaginfo') {/*{{{*/
        unset($params['type']);
        unset($params['cttid']);
        $cttid = $dict->add($params, 'cartoontaginfos');
        $ajaxret['retno'] = RETNO_SUCC;
        $ajaxret['result'] = $cttid;
    }/*}}}*/
    elseif ($type == 'updatetaginfo') {/*{{{*/
        $cttid = GetItemFromArray($params, 'cttid');
        unset($params['type']);
        unset($params['cttid']);
        $succ = $dict->update($cttid, $params, 'ctt', 'cartoontaginfos');
        $ajaxret['retno'] = RETNO_SUCC;
        $ajaxret['result'] = $succ;
    }/*}}}*/
    elseif ($type == 'deletetaginfo') {/*{{{*/
        $cttid = GetItemFromArray($params, 'cttid');
        $succ = $dict->update($cttid, array('cttstate' => STATE_DEL), 'ctt', 'cartoontaginfos');
        $ajaxret['retno'] = RETNO_SUCC;
        $ajaxret['result'] = $succ;
    }/*}}}*/
    elseif ($type == 'gettypeinfobycttpid') {/*{{{*/
        $cttpid = GetItemFromArray($params, 'cttpid');
        $typeinfo = $dict->GetTypeInfoByCttpid($cttpid);
        $ajaxret['retno'] = RETNO_SUCC;
        $ajaxret['result'] = $typeinfo;
    }/*}}}*/
    elseif ($type == 'addtypeinfo') {/*{{{*/
        unset($params['type']);
        unset($params['cttpid']);
        $cttpid = $dict->add($params, 'cartoontypeinfos');
        $ajaxret['retno'] = RETNO_SUCC;
        $ajaxret['result'] = $cttpid;
    }/*}}}*/
    elseif ($type == 'updatetypeinfo') {/*{{{*/
        $cttpid = GetItemFromArray($params, 'cttpid');
        unset($params['type']);
        unset($params['cttpid']);
        $succ = $dict->update($cttpid, $params, 'cttp', 'cartoontypeinfos');
        $ajaxret['retno'] = RETNO_SUCC;
        $ajaxret['result'] = $succ;
    }/*}}}*/
    elseif ($type == 'deletetypeinfo') {/*{{{*/
        $cttpid = GetItemFromArray($params, 'cttpid');
        $succ = $dict->update($cttpid, array('cttpstate' => STATE_DEL), 'cttp', 'cartoontypeinfos');
        $ajaxret['retno'] = RETNO_SUCC;
        $ajaxret['result'] = $succ;
    }/*}}}*/
    elseif ($type == 'getsubjectinfobyctsuid') {/*{{{*/
        $ctsuid = GetItemFromArray($params, 'ctsuid');
        $subjectinfo = $dict->GetSubjectInfoByCtsuid($ctsuid);
        $ajaxret['retno'] = RETNO_SUCC;
        $ajaxret['result'] = $subjectinfo;
    }/*}}}*/
    elseif ($type == 'addsubjectinfo') {/*{{{*/
        unset($params['type']);
        unset($params['ctsuid']);
        $ctsuid = $dict->add($params, 'cartoonsubjectinfos');
        $ajaxret['retno'] = RETNO_SUCC;
        $ajaxret['result'] = $ctsuid;
    }/*}}}*/
    elseif ($type == 'updatesubjectinfo') {/*{{{*/
        $ctsuid = GetItemFromArray($params, 'ctsuid');
        unset($params['type']);
        unset($params['ctsuid']);
        $succ = $dict->update($ctsuid, $params, 'ctsu', 'cartoonsubjectinfos');
        $ajaxret['retno'] = RETNO_SUCC;
        $ajaxret['result'] = $succ;
    }/*}}}*/
    elseif ($type == 'deletesubjectinfo') {/*{{{*/
        $ctsuid = GetItemFromArray($params, 'ctsuid');
        $succ = $dict->update($ctsuid, array('ctsustate' => STATE_DEL), 'ctsu', 'cartoonsubjectinfos');
        $ajaxret['retno'] = RETNO_SUCC;
        $ajaxret['result'] = $succ;
    }/*}}}*/
    else {
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
