<?php
/*
 * 功能：一个时间队列，通过redis实现
 * 作者：fengerbo@360.cn
 * 时间：2011-08-22
 */
require_once("Predis.php");

class TimeQueue
{
  public function __construct($server, $app)/*{{{*/
  {
    $this->seraddr = $server;
    $this->server = new Predis_Client($this->seraddr);
    $this->app = $app;
  }/*}}}*/

  public function put($value, $time=0)/*{{{*/
  {
    $ret = false;
    if($time == 0)
      $time = time();
    try
    {
      $ret = $this->server->zadd($this->app, $time, $value);
      if($ret == 0)
        $ret = true;
    }
    catch(Exception $e)
    {}

    return $ret;
  }/*}}}*/

  public function get($count, $endtime=0)/*{{{*/
  {
    if($endtime == 0)
      $endtime = time();
    $values = array();
    try
    {
      $values = $this->server->zrangebyscore($this->app, '-inf', $endtime, array('withscores'=>true,'limit'=>array(0, $count)));
    }
    catch(Exception $e)
    {}

    return $values;
  }/*}}}*/

  function exist($key)/*{{{*/
  {
    $exist = false;
    try
    {
      $score = $this->server->zscore($this->app, $key);
      if($score)
        $exist = true;
    }
    catch(Exception $e){}

    return $exist;
  }/*}}}*/

  function remove($key)/*{{{*/
  {
    $succ = false;
    try
    {
      $ret = $this->server->zrem($this->app, $key);
      if($ret)
        $succ = true;
    }
    catch(Exception $e){}

    return $succ;
  }/*}}}*/

  public function commit($values)/*{{{*/
  {
    $ret = false;
    $vs = array();
    if(!is_array($values))
      $vs[] = $values;
    else
      $vs = $values;

    foreach($vs as $v)
    {
      $ret = $this->server->zrem($this->app, $v);
    }
    return $ret;
  }/*}}}*/

  public function get_queue_count()/*{{{*/
  {
    $count = 0;
    try
    {
      $count = $this->server->zcount($this->app, '-inf', '+inf');
    }
    catch(Exception $e){}

    return $count;
  }/*}}}*/

  private $seraddr;
  private $server;
  private $app;
}

/*$server = "redis://se7.white.lft.qihoo.net:6580";
$app = "time_test";
$queue = new TimeQueue($server, $app, 5);

for($index=0; $index<5; $index+=1)
{
  $queue->put($index);
  echo sprintf("put %s\n", $index);
}

echo sprintf("count=%s\n", $queue->get_queue_count());
$queue->commit(1);
echo sprintf("count=%s\n", $queue->get_queue_count());
$queue->commit(2);
echo sprintf("count=%s\n", $queue->get_queue_count());*/
?>
