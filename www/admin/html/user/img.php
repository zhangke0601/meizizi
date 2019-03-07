<?php
/*
 * 用途：显示验证码图片
 * 作者：feb1234@163.com
 * 时间：2017-09-26
 * */
$base_dir = dirname(__FILE__).'/../../../../';
require_once($base_dir.'inc/init.php');
require_once($base_dir.'inc/func.php');

$logger = 'meizizi.admin.vcode';
qLogConfig($base_dir.'config/qlog.cfg');

Header("Content-type: image/PNG");
$im = imagecreate(50,25);
$back = ImageColorAllocate($im, 245,245,245);
imagefill($im,0,0,$back); //背景
srand((double)microtime()*1000000);
$vcodes = '';
for($i=0;$i<4;$i++){
  $font = ImageColorAllocate($im, rand(100,255),rand(0,100),rand(100,255));
  $authnum=rand(1,9);
  $vcodes.=$authnum;
  imagestring($im, 5, 2+$i*10, 4, $authnum, $font);
}
qLogInfo($logger, sprintf("vcode=%s", $vcodes));
for($i=0;$i<100;$i++) //加入干扰象素
{ 
  $randcolor = ImageColorallocate($im,rand(0,255),rand(0,255),rand(0,255));
  imagesetpixel($im, rand()%70 , rand()%30 , $randcolor);
}
ImagePNG($im);
ImageDestroy($im);
$_SESSION['vcode'] = $vcodes;
?>
