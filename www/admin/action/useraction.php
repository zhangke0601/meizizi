<?php
/*
 * 用途：处理前端用户请求
 * 作者：feb1234@163.com
 * 时间：2017-09-09
 * */
$base_dir = dirname(__FILE__) . '/../../../';
require_once($base_dir . 'inc/init.php');
require_once($base_dir . 'inc/PHPExcel/PHPExcel.php');
require_once($base_dir . 'inc/util_for_meizizi.php');
require_once($base_dir . 'inc/util_for_shortmsg.php');
require_once($base_dir . 'config/config.php');
require_once($base_dir . 'model/clsUserinfos.php');

$ajaxret = array('retno' => RETNO_FAIL);
$logger = 'meizizi.admin.user';
qLogConfig($base_dir . 'config/qlog.cfg');

$params = $_POST;
qLogInfo($logger, json_encode($params));
$type = GetItemFromArray($params, 'type');
unset($params['type']);
$user = new Userinfos();
$uinfo = $user->IsLogin();

if ($type == 'sendvcode') {/*{{{*/
    $umobile = GetItemFromArray($params, 'umobile');
    $vcode = GetVerifyCode(4);
    SetVerifyCode($umobile, $vcode);
    $ret = ShortMsgForYinghua($umobile, $vcode);
    qLogInfo($logger, sprintf("%s %s %s", $umobile, $vcode, $ret));
    $ajaxret['retno'] = RETNO_SUCC;
    $ajaxret['result'] = true;
}/*}}}*/
elseif ($type == 'login') {/*{{{*/
    $ui = $user->CheckUserByMobile($params);
    if ($ui === false) {
        $ajaxret['msg'] = '账号或密码不对';
    } else {
        if ($ui['ustate'] == STATE_OFFLINE) {
            $ajaxret['msg'] = '该账号被锁定，请联系管理员';
        } else {
            $user->update($ui['uid'], array('ulastlogintime' => date('Y-m-d H:i:s'), 'ulastloginip' => $_SERVER['REMOTE_ADDR'], 'ulogintimes' => $ui['ulogintimes'] + 1));
            $user->recordlogin(array('ullogintime' => date('Y-m-d H:i:s'), 'ulloginip' => $_SERVER['REMOTE_ADDR'], 'utype' => $ui['utype'], 'uid' => $ui['uid']));
            $ajaxret['result'] = array('utype' => $ui['utype']);
            $ajaxret['retno'] = RETNO_SUCC;
        }
    }
}/*}}}*/
elseif ($type == 'super_login') {
    $uid = GetItemFromArray($params, 'uid');
    $_SESSION['uid'] = $uid;
    $ajaxret['retno'] = RETNO_SUCC;
} elseif ($type == 'userreg') {/*{{{*/
    $mobile = GetItemFromArray($params, 'umobile');
    $vcode = GetItemFromArray($params, 'vcode');
    $mvcode = GetItemFromArray($params, 'mvcode');
    $verify = VcodeVerify($mobile, $mvcode);
    if (strlen($vcode) && ($vcode == $_SESSION['vcode']) && $verify) {
        $utype = GetItemFromArray($params, 'utype');
        if (in_array($utype, array(USERTYPE_MANHUASHI, USERTYPE_STUDIO))) {
            $uinfo = $user->GetUserInfoByMobile($mobile);
            if (empty($uinfo)) {
                unset($params['type']);
                unset($params['vcode']);
                unset($params['mvcode']);
                qLogInfo($logger, json_encode($params));
                $uid = $user->add($params);
                if ($uid !== false) {
                    $ajaxret['retno'] = RETNO_SUCC;
                    $ajaxret['result'] = $uid;
                    $_SESSION['uid'] = $uid;
                    if ($utype == USERTYPE_STUDIO) {
                        $params = array('uid' => $uid, 'sugname' => '上传', 'sugstate' => 0);
                        $sugid = $user->add($params, 'studiousergroupinfos');
                        $user->AddStudioGroupAuth($sugid, array(1, 3));
                        $params = array('uid' => $uid, 'sugname' => '运营', 'sugstate' => 0);
                        $sugid = $user->add($params, 'studiousergroupinfos');
                        $user->AddStudioGroupAuth($sugid, array(3, 6));
                    }
                } else {
                    $ajaxret['msg'] = '注册失败';
                    unset($_SESSION['uid']);
                }
            } else {
                $ajaxret['msg'] = '该手机已注册';
            }
        } else {
            $ajaxret['msg'] = '请确认是漫画师还是工作室';
        }
    } else {
        $ajaxret['msg'] = '验证码不正确';
    }
}/*}}}*/
elseif ($type == 'resetpwd') {/*{{{*/
    $mobile = GetItemFromArray($params, 'umobile');
    $vcode = GetItemFromArray($params, 'uvcode');
    $passwd = GetItemFromArray($params, 'upasswd');
    $mvcode = GetItemFromArray($params, 'mvcode');
    $verify = VcodeVerify($mobile, $mvcode);
    if (strlen($vcode) && ($vcode == $_SESSION['vcode']) && $verify) {
        $uinfo = $user->GetUserInfoByMobile($mobile);
        if ($uinfo) {
            if ($passwd == $uinfo['upasswd']) {
                $ajaxret['msg'] = '不能和原密码重复';
            } else {
                $succ = $user->update($uinfo['uid'], array('upasswd' => $passwd));
                if ($succ) {
                    $user->CheckUserByMobile(array('umobile' => $mobile, 'upasswd' => $passwd));
                    $ajaxret['retno'] = RETNO_SUCC;
                    $ajaxret['result'] = $succ;
                } else {
                    $ajaxret['msg'] = '修改密码失败';
                    unset($_SESSION['uid']);
                }
            }
        } else {
            $ajaxret['msg'] = '该手机未注册';
        }
    } else {
        $ajaxret['msg'] = '验证码不正确';
    }
}/*}}}*/
elseif ($type == 'logout') {/*{{{*/
    $_SESSION['uid'] = 0;
    header('Location:/index.html');
    exit();
}/*}}}*/
else {
    if ($uinfo !== false) {
        $uid = $uinfo['uid'];
        if ($type == 'getuserinfo') {/*{{{*/
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = filter_uinfo($uinfo);
        }/*}}}*/
        elseif ($type == 'updateuserinfo') {/*{{{*/
            $utype = $uinfo['utype'];
            $data = array();
            if ($utype == USERTYPE_AUTH) {
                $data = array('urealname' => GetItemFromArray($params, 'urealname'), 'usex' => GetItemFromArray($params, 'usex'));
            } elseif ($utype == USERTYPE_MANHUASHI) {
                $data = array('urealname' => GetItemFromArray($params, 'urealname'),
                    'usex' => GetItemFromArray($params, 'usex'),
                    'udesc' => GetItemFromArray($params, 'udesc'),
                    'unotice' => GetItemFromArray($params, 'unotice'));
            } elseif ($utype == USERTYPE_STUDIO) {
                $data = array('urealname' => GetItemFromArray($params, 'urealname'));
            }
            if (empty($data)) {
                $ajaxret['msg'] = '无效操作';
            } else {
                $user->update($uid, $data);
                $ajaxret['retno'] = RETNO_SUCC;
            }
        }/*}}}*/
        elseif ($type == 'modifypasswd') {/*{{{*/
            $data = array('upasswd' => GetItemFromArray($params, 'upasswd'));
            $user->update($uid, $data);
            $ajaxret['retno'] = RETNO_SUCC;
        }/*}}}*/
        elseif ($type == 'addauthuser') {/*{{{*/
            unset($params['type']);
            $params['utype'] = USERTYPE_AUTH;
            $umobile = GetItemFromArray($params, 'umobile');
            $uemail = GetItemFromArray($params, 'uemail');
            $newuinfo = $user->GetUserInfoByMobile($umobile);
            if (empty($newuinfo)) {
                $newuinfo = $user->GetUserInfoByEmail($uemail);
                if ($newuinfo['uid'] != $uid) {
                    $ajaxret['msg'] = '该邮箱已注册';
                } else {
                    $uid = $user->add($params);
                    $ajaxret['retno'] = RETNO_SUCC;
                    $ajaxret['result'] = $uid;
                }
            } else {
                $ajaxret['msg'] = '该手机号已注册';
            }
        }/*}}}*/
        elseif ($type == 'updateauthuser') {/*{{{*/
            unset($params['type']);
            $uid = GetItemFromArray($params, 'uid');
            unset($params['uid']);
            $umobile = GetItemFromArray($params, 'umobile');
            $uemail = GetItemFromArray($params, 'uemail');
            $newuinfo = $user->GetUserInfoByMobile($umobile);
            if ($newuinfo['uid'] != $uid) {
                $ajaxret['msg'] = '该手机号已注册';
            } else {
                $newuinfo = $user->GetUserInfoByEmail($uemail);
                if ($newuinfo['uid'] != $uid) {
                    $ajaxret['msg'] = '该邮箱已注册';
                } else {
                    $succ = $user->update($uid, $params);
                    $ajaxret['retno'] = RETNO_SUCC;
                    $ajaxret['result'] = $succ;
                }
            }
        }/*}}}*/
        elseif ($type == 'getauthuserbyuid') {/*{{{*/
            $uid = GetItemFromArray($params, 'uid');
            $uinfo = $user->find($uid);
            //unset($uinfo['upasswd']);
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = $uinfo;
        }/*}}}*/
        elseif ($type == 'deleteauthinfo') {/*{{{*/
            $uid = GetItemFromArray($params, 'uid');
            $succ = $user->SetAuthState($uid, STATE_DEL);
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = $succ;
        }/*}}}*/
        elseif ($type == 'setuserstate') {/*{{{*/
            $uid = GetItemFromArray($params, 'uid');
            $uinfo = $user->find($uid);
            if ($uinfo['ustate'] == STATE_NOR)
                $user->setstate($uid, STATE_OFFLINE);
            else
                $user->setstate($uid, STATE_NOR);
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = true;
        }/*}}}*/
        elseif ($type == 'deletecomicinfo') {/*{{{*/
            $uid = GetItemFromArray($params, 'uid');
            $succ = $user->setstate($uid, STATE_DEL);
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = $succ;
        }/*}}}*/
        elseif ($type == 'deletestudioinfo') {/*{{{*/
            $uid = GetItemFromArray($params, 'uid');
            $succ = $user->setstate($uid, STATE_DEL);
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = $succ;
        }/*}}}*/
        elseif ($type == 'modifypwd') {/*{{{*/
            $unewpwd = GetItemFromArray($params, 'unewpwd');
            $unewpwd2 = GetItemFromArray($params, 'unewpwd2');
            $uoldpwd = GetItemFromArray($params, 'uoldpwd');
            $vcode = GetItemFromArray($params, 'vcode');
            if (strlen($vcode) && ($vcode == $_SESSION['vcode'])) {
                if ((strlen($unewpwd) >= 6) && ($unewpwd == $unewpwd2)) {
                    if ($uinfo['upasswd'] == $uoldpwd) {
                        $succ = $user->update($uinfo['uid'], array('upasswd' => $unewpwd));
                        if ($succ) {
                            $ajaxret['retno'] = RETNO_SUCC;
                            $ajaxret['result'] = true;
                        } else {
                            $ajaxret['msg'] = '修改失败';
                        }
                    } else
                        $ajaxret['msg'] = '原密码有误';


                } else
                    $ajaxret['msg'] = '新密码不一样';
            } else {
                $ajaxret['msg'] = '验证码有误';
            }
        }/*}}}*/
        elseif ($type == 'getloginstatbyuid') {/*{{{*/
            $uid = GetItemFromArray($params, 'uid');
            list($count, $ulinfos) = $user->GetLoginInfosByUid($uid);
            $time = '未登录';
            if (count($ulinfos) > 0)
                $time = $ulinfos[0]['ulcreatetime'];
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = array('lasttime' => $time, 'weektimes' => $user->GetLoginWeekTimesByUid($uid), 'monthtimes' => $user->GetLoginMonthTimesByUid($uid), 'yeartimes' => $user->GetLoginYearTimesByUid($uid));
        }/*}}}*/
        elseif ($type == 'getgroupinfobyugid') {/*{{{*/
            $ugid = GetItemFromArray($params, 'ugid');
            $groupinfo = $user->GetGroupInfoByUgid($ugid);
            if ($groupinfo) {
                $fcs = $groupinfo['ugfuncinfos'];
                if ($fcs)
                    $groupinfo['ugfuncinfos'] = array();
                else
                    $groupinfo['ugfuncinfos'] = json_decode($fcs, 'true');
            }
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = $groupinfo;
        }/*}}}*/
        elseif ($type == 'addgroupinfo') {/*{{{*/
            unset($params['type']);
            unset($params['ugid']);
            $ugid = $user->add($params, 'usergroupinfos');
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = $ugid;
        }/*}}}*/
        elseif ($type == 'updategroupinfo') {/*{{{*/
            $ugid = GetItemFromArray($params, 'ugid');
            unset($params['type']);
            unset($params['ugid']);
            $succ = $user->update($ugid, $params, 'ug', 'usergroupinfos');
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = $succ;
        }/*}}}*/
        elseif ($type == 'deletegroupinfo') {/*{{{*/
            $ugid = GetItemFromArray($params, 'ugid');
            $succ = $user->update($ugid, array('ugstate' => STATE_DEL), 'ug', 'usergroupinfos');
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = $succ;
        }/*}}}*/
        elseif ($type == 'addgroupauth') {/*{{{*/
            $ugid = GetItemFromArray($params, 'ugid');
            $funcs = GetItemFromArray($params, 'funcs');
            $succ = $user->AddGroupAuth($ugid, $funcs);
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = $succ;
        }/*}}}*/
        elseif ($type == 'dataauth') {/*{{{*/
            $user->update($uid, array('udataauth' => 5));
            $ajaxret['retno'] = 0;
        }/*}}}*/
        /* 修改用户信息 */
        elseif ($type == 'updateuinfo') {
            $urealname = GetItemFromArray($params, 'urealname');
            $usex = GetItemFromArray($params, 'usex');
            $data = array('urealname' => $urealname, 'usex' => $usex);
            $ret = $user->update($uid, $data);
            if ($ret)
                $ajaxret['retno'] = RETNO_SUCC;
            else
                $ajaxret['msg'] = '操作失败';
        } /* 修改手机号时 发送验证码 */
        elseif ($type == 'getcode') {
            $mobile = GetItemFromArray($params, 'mobile');
            $vcode = GetVerifyCode(4);
            SetVerifyCode($mobile, $vcode);
            $ret = ShortMsgForYinghua($mobile, $vcode);
            qLogInfo($logger, sprintf("%s %s %s", $mobile, $vcode, $ret));
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = true;
        } /* 验证第一次的手机验证码 */
        elseif ($type == 'verifymobileandcode') {
            $mobile = GetItemFromArray($params, 'mobile');
            $vcode = GetItemFromArray($params, 'vcode');
            $verify = VcodeVerify($mobile, $vcode);
            if ($verify) {
                $ajaxret['retno'] = RETNO_SUCC;
                $ajaxret['result'] = true;
            } else
                $ajaxret['msg'] = '验证码有误';
        } /* 修改手机号 */
        elseif ($type == 'updatemobile') {
            $mobile = GetItemFromArray($params, 'mobile');
            $vcode = GetItemFromArray($params, 'vcode');
            $verify = VcodeVerify($mobile, $vcode);
            if ($verify) {
                $muinfo = $user->GetUserInfoByMobile($mobile);
                if (empty($muinfo)) {
                    $data = array('umobile' => $mobile);
                    $user->update($uid, $data);
                    $ajaxret['retno'] = RETNO_SUCC;
                } else {
                    if ($muinfo['umobile'] == $uinfo['umobile']) {
                        $ajaxret['msg'] = '两次手机号一样';
                    } else
                        $ajaxret['msg'] = '该手机号已注册';
                }
            } else
                $ajaxret['msg'] = '验证码有误';
        } /* 修改密码 */
        elseif ($type == 'updatepwd') {
            $ypwd = GetItemFromArray($params, 'ypwd');
            $xpwd = GetItemFromArray($params, 'xpwd');
            if ($uinfo['upasswd'] == $ypwd) {
                $succ = $user->update($uinfo['uid'], array('upasswd' => $xpwd));
                if ($succ) {
                    $ajaxret['retno'] = RETNO_SUCC;
                    $ajaxret['result'] = true;
                } else {
                    $ajaxret['msg'] = '修改失败';
                }
            } else
                $ajaxret['msg'] = '原密码有误';

        } /* 工作室删除漫画师 */
        elseif ($type == 'updateuserinfoupuid') {
            $u_id = intval(GetItemFromArray($params, 'uid'));
            $userinfo = $user->find($u_id);
            if (!empty($userinfo)) {
                if ($userinfo['upuid'] == $uid) {
                    $data = array('upuid' => 0);
                    $ret = $user->update($u_id, $data);
                    if ($ret)
                        $ajaxret['retno'] = RETNO_SUCC;
                    else
                        $ajaxret['msg'] = '操作有误';
                } else
                    $ajaxret['msg'] = '无权操作';
            } else
                $ajaxret['msg'] = '参数有误';
        } elseif ($type == 'updateuserstate') {
            $u_id = intval(GetItemFromArray($params, 'uid'));
            $ustate = intval(GetItemFromArray($params, 'ustate'));
            $userinfo = $user->find($u_id);
            if (!empty($userinfo)) {
                if ($userinfo['upuid'] == $uid) {
                    $data = array('ustate' => $ustate);
                    $ret = $user->update($u_id, $data);
                    if ($ret)
                        $ajaxret['retno'] = RETNO_SUCC;
                    else
                        $ajaxret['msg'] = '操作有误';
                } else
                    $ajaxret['msg'] = '无权操作';
            } else
                $ajaxret['msg'] = '参数有误';
        } /* 工作室添加漫画师 */
        elseif ($type == 'addstudiocomicinfo') {
            $urealname = GetItemFromArray($params, 'urealname');
            $umobile = GetItemFromArray($params, 'umobile');
            $u_info = $user->GetUserInfoByMobile($umobile);
            if (!empty($u_info)) {
                if ($u_info['upuid'] == 0) {
                    if ($u_info['utype'] == USERTYPE_MANHUASHI) {
                        $data = array('upuid' => $uid, 'ustate' => STATE_NOR);
                        $ret = $user->update($u_info['uid'], $data);
                        $ajaxret['retno'] = RETNO_SUCC;
                    } else
                        $ajaxret['msg'] = '该用户不可添加为漫画师';
                } else
                    $ajaxret['msg'] = '此漫画师已有工作室';

            } else {
                $ajaxret['msg'] = '该用户未注册美滋滋';
            }
        } /* 工作室 添加 管理员*/
        elseif ($type == 'addstudiomanageuser') {
            unset($params['type']);
            $u_info = $user->GetUserInfoByMobile($params['umobile']);
            if (empty($u_info)) {
                $uemail = GetItemFromArray($params, 'uemail');
                $newuinfo = $user->GetUserInfoByEmail($uemail);
                if ($newuinfo['uid'] != $uid) {
                    $ajaxret['msg'] = '该邮箱已注册';
                } else {

                    $params['upuid'] = $uid;
                    $params['utype'] = USERTYPE_STUDIOMANAGE;
                    $uid = $user->add($params);
                    $ajaxret['retno'] = RETNO_SUCC;
                    $ajaxret['result'] = $uid;
                }
            } else
                $ajaxret['msg'] = '该手机号已注册';
        } /* 工作室添加管理组 */
        elseif ($type == 'addstudiousergroupinfo') {/*{{{*/
            unset($params['type']);
            unset($params['sugid']);
            $params['uid'] = $uid;
            $sugid = $user->add($params, 'studiousergroupinfos');
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = $sugid;
        }/*}}}*/
        /* 获取用户组信息 */
        elseif ($type == 'getstudiogroupinfobysugid') {
            $sugid = GetItemFromArray($params, 'sugid');
            $sgroupinfo = $user->GetStudioGroupInfoBySugid($sugid);
            if ($sgroupinfo) {
                $fcs = $sgroupinfo['sugfuncinfos'];
                if ($fcs)
                    $sgroupinfo['sugfuncinfos'] = array();
                else
                    $sgroupinfo['sugfuncinfos'] = json_decode($fcs, 'true');
            }
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = $sgroupinfo;
        } /* 修改 工作室用户组信息 */
        elseif ($type == 'updatestudiousergroupinfo') {
            $sugid = GetItemFromArray($params, 'sugid');
            unset($params['type']);
            unset($params['sugid']);
            $succ = $user->update($sugid, $params, 'sug', 'studiousergroupinfos');
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = $succ;
        } /* 授权 */
        elseif ($type == 'addstudionmanagegroupauth') {
            $sugid = GetItemFromArray($params, 'sugid');
            $funcs = GetItemFromArray($params, 'funcs');
            $succ = $user->AddStudioGroupAuth($sugid, $funcs);
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = $succ;
        } /* 删除 */
        elseif ($type == 'deletestudiousergroupinfo') {/*{{{*/
            $sugid = GetItemFromArray($params, 'sugid');
            $succ = $user->update($sugid, array('sugstate' => STATE_DEL), 'sug', 'studiousergroupinfos');
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = $succ;
        }/*}}}*/
        /* 工作室删除名下管理员 */
        elseif ($type == 'deletestudiomanageinfo') {
            $uid = GetItemFromArray($params, 'uid');
            $data = array('upuid' => 0, 'ustate' => STATE_DEL);
            $ret = $user->update($uid, $data);
            $ajaxret['retno'] = RETNO_SUCC;
            $ajaxret['result'] = $ret;
        } else {
            $ajaxret['retno'] = RETNO_INVALIDOPT;
            $ajaxret['msg'] = '无效操作';
        }
    } else {
        $ajaxret['retno'] = RETNO_NOTLOGIN;
        $ajaxret['msg'] = '登陆超时';
    }
}
echo json_encode($ajaxret);
//$ajaxret['uid'] = $uid;
qLogInfo($logger, json_encode($ajaxret));
?>
