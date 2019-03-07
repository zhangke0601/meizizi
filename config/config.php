<?php
ini_set('date.timezone','Asia/Shanghai');
$hostname = php_uname("n");
$cfg['db'] = array(
  'host' => '172.31.195.225',
  'dbname' => 'meizizi',
  'username' => 'root',
  'password' => 'QWERT12345'
);
/* new_meizizi */
$new_cfg['db'] = [
    'host' => '172.31.195.225',
    'dbname' => 'new_meizizi',
    'username' => 'root',
    'password' => 'QWERT12345'
];
/* 线下 */
$xxdb =array(
    'host' => 'localhost',
    'dbname' => 'meizizi',
    'username' => 'root',
    'password' => 'root'
);
/* 线下 DreamWake*/
$xxdb2 =array(
    'host' => 'localhost',
    'dbname' => 'meizizi',
    'username' => 'root',
    'password' => ''
);
if($hostname == 'ML-20170718WOLN')
    $cfg['db'] = $xxdb2;
if($hostname == 'DESKTOP-TN4RVJL')
    $cfg['db'] = $xxdb;
define('DBDEBUG', 1);

define('USERTYPE_AUTH',      0);
define('USERTYPE_MANHUASHI', 5);
define('USERTYPE_STUDIO',    10);
define('USERTYPE_STUDIOMANAGE',    15);

/*define('SOURCE_TENCENT', 5);
define('SOURCE_ZHANGYUE', 10);
define('SOURCE_MANMAN', 15);
define('SOURCE_KUAIKAN', 20);
define('SOURCE_U17', 25);
define('SOURCE_MANHUADAO', 30);
define('SOURCE_WANGYI', 35);*/
define('SOURCE_TENCENT', 5);
define('SOURCE_ZHANGYUE', 1);
define('SOURCE_MANMAN', 9);
define('SOURCE_KUAIKAN', 2);
define('SOURCE_U17', 8);
define('SOURCE_MANHUADAO', 3);
define('SOURCE_WANGYI', 7);
define('SOURCE_BUKA', 4);
define('SOURCE_AIQIYI', 6);
define('SOURCE_WEIBO', 11);
define('SOURCE_XUNI', 100);
$sources = array(SOURCE_TENCENT=>'腾讯', SOURCE_ZHANGYUE=>'掌阅', SOURCE_MANMAN=>'漫漫', SOURCE_KUAIKAN=>'快看', SOURCE_U17=>'有妖气',SOURCE_MANHUADAO=>'漫画岛', SOURCE_WANGYI=>'网易', SOURCE_BUKA=>'布卡',SOURCE_AIQIYI=>'爱奇艺',SOURCE_WEIBO=>'微博',SOURCE_XUNI=>'虚拟平台');

define('TYPE_SOURCE_USER', 1);
define('TYPE_SOURCE_THIRD', 5);
define('TYPE_SOURCE_MANAGER', 10);
$typesources = array(TYPE_SOURCE_USER=>'用户添加', TYPE_SOURCE_THIRD=>'第三方抓取', TYPE_SOURCE_MANAGER=>'管理员添加');

define('SEX_NOR', 0);
define('SEX_NAN', 5);
define('SEX_NV', 10);

define('RETNO_SUCC', 0);
define('RETNO_FAIL', 1);
define('RETNO_UPDATE', 2);
define('RETNO_EXIST', 55);
define('RETNO_INVALIDPARAM', 60);
define('RETNO_AUDIT', 65);
define('RETNO_AUDITFAIL', 70);
define('RETNO_BLACK',     75);
define('RETNO_INVALIDOPT', 80);
define('RETNO_NOTCOMPLETE', 90);
define('RETNO_MOBILE', 95);
define('RETNO_NOTLOGIN', 100);

define('COUNT_PER_PAGE', 20);
define('COUNT_PER_PAGE_TEN', 10);
define('COUNTPERPAGE', 10);

define('STATE_NOR',     0);
define('STATE_WHOLE',   2);
//define('STATE_COOKIES', 5);
define('STATE_UNCOOP',  5);
define('STATE_INVALID',  5);
define('STATE_NOTHEXIAO',5);
define('STATE_TMALLPAY', 5);
define('STATE_POSTING', 5);
define('STATE_REPLY',   10);
define('STATE_COOKIES', 10);
define('STATE_POST', 10);
define('STATE_LOCK',   15); /* 锁 */
define('STATE_UPLOADING', 20);
define('STATE_UPLOADED', 25);
define('STATE_AUTHING',   25);
define('STATE_AUTH',   25);
define('STATE_AUTHSUCC',      30);
define('STATE_AUTHALL',   30);
define('STATE_ONLINE',    40);
define('STATE_OFFLINE',   45);
define('STATE_BLACK',     100);
define('STATE_USED',      110);
define('STATE_OVER',    200);
define('STATE_UPLOADFAIL',    210);
define('STATE_AUTHFAIL',    220);
define('STATE_FAIL',    230);
define('STATE_DEL',     400);
define('STATE_USE',6);     /* 已使用 */
$states = array(STATE_NOR=>'正常', STATE_DEL=>'删除');
$ustates = array(STATE_NOR=>'正常',STATE_LOCK=>'锁定');
$ctstates = array(STATE_NOR=>'禁用', STATE_ONLINE=>'启用');
$dictstates = array(STATE_NOR=>'显示', STATE_OFFLINE=>'禁用');
$pfstates = array(STATE_NOR=>'启用', STATE_OFFLINE=>'禁用');
$ugstates = array(STATE_NOR=>'启用', STATE_OFFLINE=>'锁定');
$austates = array(STATE_NOR=>'启用', STATE_OFFLINE=>'锁定');
$releasestates = array(STATE_NOR=>'待上传', STATE_UPLOADING=>'正在上传', STATE_UPLOADED=>'上传成功', STATE_ONLINE=>'正在发布', STATE_AUTHSUCC=>'审核通过', STATE_OVER=>'发布成功', STATE_AUTHFAIL=>'被驳回', STATE_FAIL=>'发布失败', STATE_UPLOADFAIL=>'上传失败');
$ctreleasestates = array(STATE_NOR=>'待上传', STATE_UPLOADED=>'上传成功', STATE_ONLINE=>'已提交', STATE_AUTHSUCC=>'审核通过', STATE_OVER=>'发布成功', STATE_AUTHFAIL=>'被驳回', STATE_FAIL=>'发布失败',STATE_UPLOADFAIL=>'上传失败');

$cookiesstates = array(STATE_INVALID=>'COOKIES无效', STATE_COOKIES=>'已设置COOKIES');
$noticepoststates = array(STATE_NOR=>'待发布', STATE_POSTING=>'正在发布', STATE_POST=>'已发布');

$tuauthstates = array(STATE_NOR=>'未授权', STATE_AUTH=>'简单授权', STATE_AUTHALL=>'完全授权');

define('PROGRESS_NORMAL', 5);
define('PROGRESS_OVER',10);
$progresses = array(PROGRESS_NORMAL=>'连载中', PROGRESS_OVER=>'已完结');

define('RELEASE_NORMAL',  0);
define('RELEASE_TWODAY',  1);
define('RELEASE_TWOWEEK', 2);/* 1 2 4 8*/
$releases = array(RELEASE_TWODAY=>'提前2天', RELEASE_TWOWEEK=>'提前两星期');

define('RELEASE_TYPE_USER', 0);
define('RELEASE_TYPE_PLAT', 5);
$releasetypes = array(RELEASE_TYPE_USER=>'用户发布', RELEASE_TYPE_PLAT=>'原平台发布');

define('AUTHSTATE_1', 5);
define('AUTHSTATE_2', 10);
$authstates = array(AUTHSTATE_1=>'授权1', AUTHSTATE_2=>'授权2');

define('MAN_VECTOR_PAGE',  5);
define('MAN_VECTOR_TIAO',  10);
define('MAN_VECTOR_SHORT', 15);
define('MAN_VECTOR_CHA', 20);
$manvectors = array(MAN_VECTOR_PAGE=>'页漫', MAN_VECTOR_TIAO=>'条漫', MAN_VECTOR_CHA=>'插画');

define('USERGROUP_NAN', 5);
define('USERGROUP_NV',  10);
define('USERGROUP_ALL',  15);
$usergroupes = array(USERGROUP_NAN=>'少男', USERGROUP_NV=>'少女', USERGROUP_ALL=>'男女通吃');
/* 定义 平台账户类型 */
define('USERANDPLATFORM_TYPE_MEI',1);
define('USERANDPLATFORM_TYPE_OWN',2);
$upftypes = array(USERANDPLATFORM_TYPE_MEI=>'美滋滋联盟账户',USERANDPLATFORM_TYPE_OWN=>'个人账户');

$corrtags = array(26=>array(SOURCE_TENCENT=>'古风', SOURCE_AIQIYI=>'古风', SOURCE_WANGYI=>'古风'),
  27=>array(SOURCE_TENCENT=>'恋爱', SOURCE_AIQIYI=>'少女', SOURCE_WANGYI=>'治愈'),
  28=>array(SOURCE_TENCENT=>'古风', SOURCE_AIQIYI=>'穿越', SOURCE_WANGYI=>'后宫'),
  29=>array(SOURCE_TENCENT=>'恋爱', SOURCE_AIQIYI=>'恋爱', SOURCE_WANGYI=>'恋爱'),
  31=>array(SOURCE_TENCENT=>'玄幻', SOURCE_AIQIYI=>'剑与魔法', SOURCE_WANGYI=>'魔幻'),
  32=>array(SOURCE_TENCENT=>'爆笑', SOURCE_AIQIYI=>'娱乐圈', SOURCE_WANGYI=>'恋爱'),
  33=>array(SOURCE_TENCENT=>'校园', SOURCE_AIQIYI=>'恋爱', SOURCE_WANGYI=>'校园'),
  34=>array(SOURCE_TENCENT=>'恋爱', SOURCE_AIQIYI=>'腹黑', SOURCE_WANGYI=>'恋爱'),
  35=>array(SOURCE_TENCENT=>'耽美', SOURCE_AIQIYI=>'伪娘', SOURCE_WANGYI=>'耽美'),
  37=>array(SOURCE_TENCENT=>'恋爱', SOURCE_AIQIYI=>'唯美', SOURCE_WANGYI=>'恋爱'),
  39=>array(SOURCE_TENCENT=>'恋爱', SOURCE_AIQIYI=>'老司机', SOURCE_WANGYI=>'恋爱'),
  41=>array(SOURCE_TENCENT=>'异能', SOURCE_AIQIYI=>'异种人', SOURCE_WANGYI=>'科幻'),
  47=>array(SOURCE_TENCENT=>'日常', SOURCE_AIQIYI=>'吐槽', SOURCE_WANGYI=>'治愈'),
  48=>array(SOURCE_TENCENT=>'爆笑', SOURCE_AIQIYI=>'吐槽', SOURCE_WANGYI=>'搞笑'),
  49=>array(SOURCE_TENCENT=>'科幻', SOURCE_AIQIYI=>'异种人', SOURCE_WANGYI=>'科幻'),
  50=>array(SOURCE_TENCENT=>'爆笑', SOURCE_AIQIYI=>'开挂', SOURCE_WANGYI=>'搞笑'),
  53=>array(SOURCE_TENCENT=>'都市', SOURCE_AIQIYI=>'激情', SOURCE_WANGYI=>'热血'),
  54=>array(SOURCE_TENCENT=>'古风', SOURCE_AIQIYI=>'古风', SOURCE_WANGYI=>'穿越'),
  55=>array(SOURCE_TENCENT=>'校园', SOURCE_AIQIYI=>'少女', SOURCE_WANGYI=>'校园'),
  56=>array(SOURCE_TENCENT=>'耽美', SOURCE_AIQIYI=>'伪娘', SOURCE_WANGYI=>'耽美'),
  57=>array(SOURCE_TENCENT=>'玄幻', SOURCE_AIQIYI=>'剑与魔法', SOURCE_WANGYI=>'武侠'),
);
$txtags = array();
$txtags["玄幻"] = 101;
$txtags["校园"] = 102;
$txtags["恐怖"] = 103;
$txtags["恋爱"] = 104;
$txtags["爆笑"] = 105;
$txtags["冒险"] = 106;
$txtags["悬疑"] = 107;
$txtags["异能"] = 108;
$txtags["古风"] = 109;
$txtags["科幻"] = 110;
$txtags["都市"] = 111;
$txtags["耽美"] = 112;
$qytags = array();
$qytags["穿越"]=1000221;
$qytags["架空"]=1000222;
$qytags["恋爱"]=1000223;
$qytags["纯爱"]=1000224;
$qytags["同人"]=1000225;
$qytags["吐槽"]=1000226;
$qytags["少女"]=1000227;
$qytags["少年"]=1000228;
$qytags["腐向"]=1000229;
$qytags["宅向"]=1000230;
$qytags["重口味"]=1000231;
$qytags["脑洞"]=1000232;
$qytags["剑与魔法"]=1000233;
$qytags["基情"]=1000234;
$qytags["开挂"]=1000235;
$qytags["腹黑"]=1000236;
$qytags["异世界"]=1000237;
$qytags["性转换"]=1000238;
$qytags["古风"]=1000239;
$qytags["伪娘"]=1000240;
$qytags["娱乐圈"]=1000241;
$qytags["不正常"]=1000242;
$qytags["黑化"]=1000243;
$qytags["唯美"]=1000244;
$qytags["养成"]=1000245;
$qytags["HE"]=1000246;
$qytags["BE"]=1000247;
$qytags["女神"]=1000248;
$qytags["屌丝"]=1000249;
$qytags["老司机"]=1000250;
$qytags["黑科技"]=1000251;
$qytags["异种人"]=1000252;

$corrsubs = array(
  4=>array(SOURCE_TENCENT=>'校园', SOURCE_AIQIYI=>'少年', SOURCE_WANGYI=>'校园',SOURCE_MANHUADAO=>'少年'),
  5=>array(SOURCE_TENCENT=>'校园', SOURCE_AIQIYI=>'少女', SOURCE_WANGYI=>'校园',SOURCE_MANHUADAO=>'少女'),
  6=>array(SOURCE_TENCENT=>'玄幻', SOURCE_AIQIYI=>'玄幻', SOURCE_WANGYI=>'玄幻',SOURCE_MANHUADAO=>'玄幻'),
  7=>array(SOURCE_TENCENT=>'都市', SOURCE_AIQIYI=>'都市', SOURCE_WANGYI=>'都市',SOURCE_MANHUADAO=>'热血'),
  8=>array(SOURCE_TENCENT=>'校园', SOURCE_AIQIYI=>'校园', SOURCE_WANGYI=>'校园',SOURCE_MANHUADAO=>'校园'),
  9=>array(SOURCE_TENCENT=>'悬疑', SOURCE_AIQIYI=>'推理', SOURCE_WANGYI=>'悬疑',SOURCE_MANHUADAO=>'推理'),
  10=>array(SOURCE_TENCENT=>'悬疑', SOURCE_AIQIYI=>'悬疑', SOURCE_WANGYI=>'悬疑',SOURCE_MANHUADAO=>'悬疑'),
  11=>array(SOURCE_TENCENT=>'科幻', SOURCE_AIQIYI=>'科幻', SOURCE_WANGYI=>'科幻',SOURCE_MANHUADAO=>'科幻'),
  12=>array(SOURCE_TENCENT=>'耽美', SOURCE_AIQIYI=>'纯爱', SOURCE_WANGYI=>'耽美',SOURCE_MANHUADAO=>'耽美'),
  13=>array(SOURCE_TENCENT=>'爆笑', SOURCE_AIQIYI=>'搞笑', SOURCE_WANGYI=>'搞笑',SOURCE_MANHUADAO=>'搞笑'),
  14=>array(SOURCE_TENCENT=>'校园', SOURCE_AIQIYI=>'青春', SOURCE_WANGYI=>'恋爱',SOURCE_MANHUADAO=>'青春'),
  15=>array(SOURCE_TENCENT=>'恐怖', SOURCE_AIQIYI=>'恐怖', SOURCE_WANGYI=>'恐怖',SOURCE_MANHUADAO=>'恐怖'),
  16=>array(SOURCE_TENCENT=>'冒险', SOURCE_AIQIYI=>'竞技', SOURCE_WANGYI=>'冒险',SOURCE_MANHUADAO=>'少年'),
  17=>array(SOURCE_TENCENT=>'冒险', SOURCE_AIQIYI=>'竞技', SOURCE_WANGYI=>'冒险',SOURCE_MANHUADAO=>'少年'),
  18=>array(SOURCE_TENCENT=>'古风', SOURCE_AIQIYI=>'穿越', SOURCE_WANGYI=>'古风',SOURCE_MANHUADAO=>'穿越'),
  19=>array(SOURCE_TENCENT=>'玄幻', SOURCE_AIQIYI=>'奇幻', SOURCE_WANGYI=>'玄幻',SOURCE_MANHUADAO=>'奇幻'),
  20=>array(SOURCE_TENCENT=>'玄幻', SOURCE_AIQIYI=>'仙侠', SOURCE_WANGYI=>'玄幻',SOURCE_MANHUADAO=>'仙侠'),
  25=>array(SOURCE_TENCENT=>'都市', SOURCE_AIQIYI=>'都市', SOURCE_WANGYI=>'都市',SOURCE_MANHUADAO=>'商战'),
  27=>array(SOURCE_TENCENT=>'恋爱', SOURCE_AIQIYI=>'恋爱', SOURCE_WANGYI=>'校园',SOURCE_MANHUADAO=>'言情'),
  28=>array(SOURCE_TENCENT=>'冒险', SOURCE_AIQIYI=>'冒险', SOURCE_WANGYI=>'冒险',SOURCE_MANHUADAO=>'冒险'),
  29=>array(SOURCE_TENCENT=>'古风', SOURCE_AIQIYI=>'后宫', SOURCE_WANGYI=>'古风',SOURCE_MANHUADAO=>'穿越'),
  30=>array(SOURCE_TENCENT=>'玄幻', SOURCE_AIQIYI=>'神魔', SOURCE_WANGYI=>'魔幻',SOURCE_MANHUADAO=>'魔幻'),
  35=>array(SOURCE_TENCENT=>'爆笑', SOURCE_AIQIYI=>'日常', SOURCE_WANGYI=>'搞笑',SOURCE_MANHUADAO=>'日常'),
  36=>array(SOURCE_TENCENT=>'悬疑', SOURCE_AIQIYI=>'灵异', SOURCE_WANGYI=>'悬疑',SOURCE_MANHUADAO=>'恐怖'),
  37=>array(SOURCE_TENCENT=>'古风', SOURCE_AIQIYI=>'宫斗', SOURCE_WANGYI=>'古风',SOURCE_MANHUADAO=>'宫斗'),
);

$wysubs = array();
$wysubs["武侠"]=17;
$wysubs["古风"]=36;
$wysubs["治愈"]=24;
$wysubs["后宫"]=15;
$wysubs["恋爱"]=16;
$wysubs["萌系"]=13;
$wysubs["耽美"]=26;
$wysubs["穿越"]=14;
$wysubs["玄幻"]=11;
$wysubs["校园"]=3;
$wysubs["恐怖"]=2;
$wysubs["都市"]=1;
$wysubs["魔幻"]=10;
$wysubs["搞笑"]=7;
$wysubs["科幻"]=5;
$wysubs["战斗"]=32;
$wysubs["悬疑"]=4;
$wysubs["儿童"]=3001;
$wysubs["冒险"]=9;
$wysubs["热血"]=8;

$daosubs = array();
$daosubs["穿越"]=0;
$daosubs["热血"]=1;
$daosubs["运动"]=2;
$daosubs["历史"]=3;
$daosubs["都市"]=4;
$daosubs["冒险"]=5;
$daosubs["宫斗"]=6;
$daosubs["神话"]=7;
$daosubs["魔幻"]=8;
$daosubs["奇幻"]=9;
$daosubs["萌宠"]=10;
$daosubs["生活"]=11;
$daosubs["校园"]=12;
$daosubs["推理"]=13;
$daosubs["恐怖"]=14;
$daosubs["励志"]=15;
$daosubs["悬疑"]=16;
$daosubs["言情"]=17;
$daosubs["灵异"]=18;
$daosubs["商战"]=19;
$daosubs["少女"]=20;
$daosubs["武侠"]=21;
$daosubs["仙侠"]=22;
$daosubs["治愈"]=23;
$daosubs["恋爱"]=24;
$daosubs["少年"]=25;
$daosubs["美食"]=26;
$daosubs["青春"]=27;
$daosubs["玄幻"]=28;
$daosubs["日常"]=29;
$daosubs["搞笑"]=30;
$daosubs["动物"]=31;
$daosubs["科幻"]=32;
$daosubs["耽美"]=33;
$daosubs["涂鸦"]=34;
$daosubs["插画"]=35;
$daosubs["其他"]=36;

$qysubs = array();
$qysubs["搞笑"]=1000181;
$qysubs["热血"]=1000182;
$qysubs["冒险"]=1000183;
$qysubs["恋爱"]=1000184;
$qysubs["少女"]=1000185;
$qysubs["青春"]=1000186;
$qysubs["恐怖"]=1000187;
$qysubs["科幻"]=1000188;
$qysubs["奇幻"]=1000189;
$qysubs["神魔"]=1000190;
$qysubs["运动"]=1000191;
$qysubs["竞技"]=1000192;
$qysubs["玄幻"]=1000193;
$qysubs["校园"]=1000194;
$qysubs["悬疑"]=1000195;
$qysubs["推理"]=1000197;
$qysubs["萌系"]=1000198;
$qysubs["穿越"]=1000199;
$qysubs["后宫"]=1000200;
$qysubs["都市"]=1000201;
$qysubs["仙侠"]=1000202;
$qysubs["战斗"]=1000203;
$qysubs["战争"]=1000204;
$qysubs["历史"]=1000205;
$qysubs["纯爱"]=1000206;
$qysubs["同人"]=1000207;
$qysubs["社会"]=1000208;
$qysubs["励志"]=1000209;
$qysubs["百合"]=1000210;
$qysubs["治愈"]=1000211;
$qysubs["机甲"]=1000212;
$qysubs["美食"]=1000213;
$qysubs["怪谈"]=1000214;
$qysubs["日常"]=1000215;
$qysubs["灵异"]=1000216;
$qysubs["偶像"]=1000217;
$qysubs["虐心"]=1000218;
$qysubs["古装"]=1000219;
$qysubs["美少女"]=1000220;
$qysubs["完结"]=1000265;
$qysubs["独家"]=1000266;
$qysubs["宫斗"]=1000268;
$qysubs["连载"]=1000269;
$qysubs["真人漫画"]=1000272;
$qysubs["总裁"]=1000274;

/*{{{ 打码兔 */
$dama2account = 'tangmingming';
$dama2passwd = '000198';
/*}}}*/

/*{{{ 阿里云-云市场-四川微格科技有限公司-3023数据 */
$weige_appkey = '24863907';
$weige_appsecret = 'f31b1a63eaee51d7e0a1c7c449dacf6c';
$weige_appcode = '40a7f82c91bf4aa589214ee4cde80058';
/*}}}*/

/*{{{ qiniu */
$qiniuaccesskey = 'MX4swgbdxFc514mFtL5e4usrUpOmlq8e_AP1mczK';
$qiniusecretkey = 'v7e7uJ6IlVqjzjURFQbL5tousQ8IA-r1UgIaTQJL';
//$qiniuoutlink   = 'http://ox57m3ikc.bkt.clouddn.com';//此为七牛云测试域名 现已弃用
$qiniuoutlink   = 'http://qiniu.ftread.com';
/*}}}*/

/*{{{ 盈华讯方 */
define('YINGHUA_UPLOADFAIL_APPID', 'JQ00579');
define('YINGHUA_UPLOADFAIL_SECRET','DA6178BDBFA766ADEA2365F03F876C2D');

define('YINGHUA_VERIFYAPPID', 'JQ00110');
define('YINGHUA_VERIFYSECRET','B21B3963C90F599CAFD1606B1B1C31A9');
define('YINGHUA_COOKIEAPPID', 'JQ00257');
define('YINGHUA_COOKIESECRET','F539F5457ACD2C1ED282885022891E71');
define('YINGHUA_FULLAPPID', 'JQ00262');
define('YINGHUA_FULLSECRET','F539F5457ACD2C1ED282885022891E71');
define('YINGHUA_GONGGAOAPPID', 'JQ00283');
define('YINGHUA_GONGGAOSECRET','');
define('YINGHUA_MCHID', '170930512');
/*}}}*/


?>
