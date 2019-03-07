<?php
/*
 * 用途：解析抓取的快看rank数据，并入库
 * 作者：feb1234@163.com
 * 时间：2018-04-30
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/clsCartooninfos.php');
require_once($base_dir.'model/clsCartoonSectioninfos.php');
require_once($base_dir.'model/clsDictinfos.php');
ini_set('memory_limit','1500M');

$cart = new Cartooninfos();
$sect = new CartoonSectioninfos();
$dict = new Dictinfos();

$path = $argv[1];
$datetime = substr($path, strrpos($path,'.')+1);

$cnt = file_get_contents($path);
$lines = explode("\n", $cnt);
$count = count($lines);
for($i=0; $i<$count; ++$i)
{
  $line = trim($lines[$i]);
  if(strlen($line) > 100){
    $lpos = strpos($line, 'type');
    $rpos = strpos($line, ' ', $lpos);
    $type = substr($line, $lpos+5,$rpos-$lpos-5);
    if(strlen($type) > 2)
      continue;

    $data = json_decode(substr($line,strpos($line,'{')),true);

    foreach($data['data']['topics'] as $idx=>$row){
      $row = array('crsource'=>SOURCE_KUAIKAN,'crsourceid'=>$row['id'],
        'crname'=>$row['title'],'crauthorname'=>'',
        'crrank'=>$idx+1,'crtype'=>$type,
        'crdatetime'=>date('Y-m-d H:i:s', substr($datetime,0,10)),'crdatetimeval'=>$datetime);
      $cart->add($row,'cartoonrankinfos');
    }
  }
}

?>
