<?php
/*
 * 用途：前端接口中过滤函数
 * 作者：feb1234@163.com
 * 时间：2015-12-03
 * */
$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'config/config.php');
require_once($base_dir.'inc/func.php');

function filter_values($info, $keys)
{/*{{{*/
  $i = array();
  foreach($keys as $key)
  {
    if(isset($info[$key]))
      $i[$key] = $info[$key];
  }
  if(empty($i))
    $i = (object)array();
  return $i;
}/*}}}*/

function filter_uinfo($mcinfo)
{/*{{{*/
  $keys = array('uid','utype','uname','urealname','ulastlogintime','ulastloginip','ulogintimes');
  return filter_values($mcinfo,$keys);
}/*}}}*/

function get_prices_from_params(&$params){
  global $sources;
  $prices = array();
  foreach($sources as $s=>$v){
    $k = 'ctprice_'.$s;
    if(isset($params[$k])){
      if(!empty($params[$k])){
        $prices[$s] = $params[$k];
      }
      unset($params[$k]);
    }
  }
  return json_encode($prices);
}

function compare_coverinfos($orisizeinfos, $realsizeinfos)
{/*{{{*/
  $same = true;
  foreach($orisizeinfos as $info)
  {
    $pfid = $info['pfid'];
    if(!isset($realsizeinfos[$pfid]))
      $same = false;
  }
  return $same;
}/*}}}*/

function start_checkstate()
{/*{{{*/
  global $base_dir;
  $phppath = $base_dir.'src/crontab/checkstate.php';
  $cmd = sprintf('/bin/env php %s >/dev/null 2>&1 &', $phppath);
  exec($cmd);
}/*}}}*/

function cutimage_to_ctscontent($specs, $old=array())
{/*{{{*/
  $new = $old;
  $count = count($old);
  foreach($specs as $row){
    $new[] = array('imgurl'=>$row['imageurl'], 'filename'=>sprintf('%s.jpg', $count));
    ++$count;
  }
  return $new;
}/*}}}*/

?>
