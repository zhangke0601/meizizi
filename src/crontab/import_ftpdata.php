<?php
/*
 * 用途：将ftp数据入库
 * 作者：feb1234@163.com
 * 时间：2017-12-16
 * */
$base_dir = dirname(__FILE__).'/../../';
require_once($base_dir.'config/config.php');
require_once($base_dir.'inc/util_for_simulate.php');
require_once($base_dir.'model/clsFunc.php');
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');

$func = new Func();

$cmds = array('all'=>$base_dir.'src/parse_for_tencent.php',
  'some'=>$base_dir.'src/parse_for_tencent_some.php',
  //'all_kk'=>$base_dir.'src/parse_for_tencent.php',
  //'home'=>'',
  //'home_kk'=>'',
  //'iqiyi_all'=>'',
  'kuaikan_all'=>$base_dir.'src/parse_for_kuaikan.php',
  //'kuaikan_home'=>'',
  'manhuadao_all'=>$base_dir.'src/parse_for_manhuadao_all.php',
  //'manhuadao_home'=>'',
  'manman_all'=>$base_dir.'src/parse_for_manman.php',
  //'manman_home'=>'',
  //'qq_rank'=>'',
  //'some'=>'',
  'u17_all'=>$base_dir.'src/parse_for_u17.php',
  //'u17_home'=>'',
  //'wangyi_all'=>'',
  'wangyi_home'=>$base_dir.'src/parse_for_wangyi_home.php',
  'wangyi_all'=>$base_dir.'src/parse_for_wangyi_all.php',
  'zhangyue_all'=>$base_dir.'src/parse_for_zhangyue.php',
  'qq_rank'=>$base_dir.'src/parse_rank_for_tencent.php',
  'kuaikan_home'=>$base_dir.'src/parse_rank_for_kuaikan.php',
);

$ftpdir = '/home/ftp/meizizi/fff/';
$filepath = scandir($ftpdir);

$begin = $func->GetValueFromKey('begintimestamp');

usort($filepath,'cmp');

foreach($filepath as $path)
{
  $pos = strrpos($path,'.');
  $t = substr($path,$pos+1);
  if($t >= $begin)
  {
    $pos2 = strpos($path, '.');
    $name = substr($path, 0, $pos2);
    if(isset($cmds[$name]))
    {
      $cmd = sprintf('php %s %s >/dev/null 2>&1', $cmds[$name], $ftpdir.$path);
      //echo sprintf("%s\n", $cmd);
      exec($cmd);

      $func->SetKeyAndValue('begintimestamp', $t);
    }
  }
}

function cmp($f1, $f2)
{
  $pos1 = strrpos($f1,'.');
  $pos2 = strrpos($f2,'.');
  $t1 = substr($f1, $pos1+1);
  $t2 = substr($f2, $pos2+1);

  if($t1 > $t2)
    return 1;
  elseif($t1 < $t2)
    return -1;
  return 0;

}



?>
