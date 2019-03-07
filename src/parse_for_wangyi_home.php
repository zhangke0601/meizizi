<?php
/**
 * 解析抓取的网易数据，并入库
 * User: A555L
 * Date: 2017/11/26
 * Time: 22:44
 */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/clsCartooninfos.php');

$cart = new Cartooninfos();

$path = $argv[1];
$datetime = substr($path, strrpos($path,'.')+1);
$datatime = date('Y-m-d H:i:s',substr($datetime,10));

$cnt = file_get_contents($path);
$lines = explode("\n", $cnt);
foreach($lines as $line)
{
    $line = trim($line);
    if (strlen($line) < 100) continue;
    $line = substr($line, strpos($line,'{'));
    $data = json_decode($line,true);
    if(isset($data['data']['recs']))
    {
        continue;
    }
    foreach($data['data']['books'] as $value)
    {
        if(!isset($value['brief']))
            continue;
        $cartinfo = array('uid'=>0,'ctsource'=>SOURCE_WANGYI, 'ctsourceid'=>$value['id'],
            'cttype'=>5);
        $cartinfo['ctname'] = $value['title'];
        $cartinfo['ctauthorname'] = $value['author'];
        //$cartinfo['ctprogress'] = ($data['data']['update_status']=='连载中')?PROGRESS_NORMAL:PROGRESS_OVER;
        $cartinfo['ctverticalimage'] = $value['cover'];
        $cartinfo['cthorizontalimage'] = $value['cover'];
        $cartinfo['ctdesc'] = $value['brief'];
        $ctinfo = $cart->CartoonExistForSourceAndSourceid($cartinfo['ctsource'], $cartinfo['ctsourceid']);
        if($ctinfo){
            $ctid = $ctinfo['ctid'];
            if($ctinfo['ctname'] != $value['title']){
              $cart->update($ctid, array('ctlatestname'=>$value['title']));
            }
        }else{
            //$ctinfo = $cart->CartoonExistForNameAndAuthor($cartinfo['ctname'], $cartinfo['ctauthorname']);
            //if($ctinfo)
            //    $ctid = $ctinfo['ctid'];
            //else
            $ctid = $cart->add($cartinfo);
            $csinfo = array('ctid'=>$ctid, 'cssource'=>SOURCE_WANGYI,
                'cssourceid'=>$value['id']);
            $cart->add($csinfo,'cartoonsourceinfos');
        }
        /*foreach($value['subjects'] as $subject)
        {
            $cttpinfo = $cart->GetcartoontypeinfoBycttpname($subject);
            if(!empty($cttpinfo))
                $cttpid = $cttpinfo['cttpid'];
            else
            {
                $info = array('cttpname'=>$subject);
                $cttpid = $cart->add($info,'cartoontypeinfos');
            }
            $catpinfo = array('cttpid'=>$cttpid,'ctid'=>$ctid);
            $cart->add($catpinfo,'cartoonandtypeinfos');
        }*/
        $exclusive = 0;
        if(!empty($value['labels']))
        {
          foreach($value['labels'] as $label)
          {
            if($label['text'] == '独家')
            {
              $exclusive = 1;
              break;
            }
          }
        }

        $cartdatainfo = array('ctid'=>$ctid, 'ctdsource'=>SOURCE_WANGYI,
            'ctdzancount'=>0, 'ctdbrowsercount'=>0,
            'ctdexclusive'=>$exclusive,
            'ctdprice'=>$value['vip'],
            'ctdupdateat'=>date('Y-m-d H:i:s',substr($value['update'],0,10)),
            'ctddatetime'=>date('Y-m-d H:i:s',substr($datetime,0,10)),
            'ctdkeyname'=>implode(',', $value['subjects']),
            'ctdtimestamp'=>$datetime,'ctdupdateatval'=>$value['update']);

        $cartdatainfo['ctdname'] = $cartinfo['ctname'];
        $cartdatainfo['ctdauthorname'] = $cartinfo['ctauthorname'];
        $cartdatainfo['ctdprogress'] = $cartinfo['ctprogress'];
        $cartdatainfo['ctdsourceid'] = $cartinfo['ctsourceid'];
        $cartdatainfo['ctdsectioncount'] = 0;//count($data['data']['comics']);
        $cart->add($cartdatainfo,'cartoondatainfos');
    }
}
?>
