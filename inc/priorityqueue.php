<?php
/*
 * 功能：一个优先级队列，通过redis实现
 * 作者：fengerbo@360.cn
 * 时间：2011-08-18
 */
require_once("Predis.php");

class PriorityQueue
{
  public function __construct($server, $app, $timeout=1800, $commit=true)/*{{{*/
  {
    $this->seraddr = $server;
    $this->server = new Predis_Client($this->seraddr);
    $this->app = $app;
    $this->app_ping = $app.'_ping';
    $this->app_timeout = $app.'_timeout';
    $this->commit = $commit;
    $this->priority = array();
    $this->gettimes = 0;

    // set timeout
    $this->timeout = $timeout;
    $this->server->append($this->app_timeout, $this->timeout);

    // set priority
    $prior_q = $this->server->zrangebyscore($this->app, '-inf', '+inf', array('withscores'=>true));
    if($prior_q)
    {
      foreach($prior_q as $row)
      {
        $this->priority[$row[1]] = $row[0];
      }
    }
  }/*}}}*/

  public function put($prior, $value)/*{{{*/
  {
    $ret = false;
    try
    {
      if(!in_array($prior, $this->priority))
      {
        $prior_queue = sprintf("%s-prior-%s", $this->app, $prior);
        $this->priority[$prior] = $prior_queue;
        $this->server->zadd($this->app, $prior, $prior_queue);
      }
      $ret = $this->server->rpush($this->priority[$prior], $value);
    }
    catch(Exception $e)
    {}

    return $ret;
  }/*}}}*/

  public function get($count)/*{{{*/
  {
    $values = array();
    $this->gettimes = $this->gettimes + 1;
    try
    {
      $c = $count;
      foreach($this->priority as $prior=>$listname)
      {
        $vs = $this->get_from_list($listname, $c);
        if(!empty($vs))
        {
          foreach($vs as $v)
            $values[] = array($prior,$v);
          $c -= count($vs);
        }
        if($c == 0)
          break;
      }

      if(!empty($values))
      {
        foreach($values as $key=>$value)
        {
          if($this->commit)
          {
            $this->server->zadd($this->app_ping, time()+$this->timeout, serialize($value));
          }
        }
      }
    }
    catch(Exception $e)
    {}

    if($this->gettimes >= 100)
    {
      $prior_q = $this->server->zrangebyscore($this->app, '-inf', '+inf', array('withscores'=>true));
      if($prior_q)
      {
        foreach($prior_q as $row)
        {
          $this->priority[$row[1]] = $row[0];
        }
      }
      $this->gettimes = 0;
    }

    return $values;
  }/*}}}*/

  public function commit($values)/*{{{*/
  {
    $vs = array();
    if(!is_array($values))
      $vs[] = $values;
    else
      $vs = $values;

    foreach($vs as $v)
    {
      foreach($this->priority as $key=>$prior)
      {
        try
        {
          $arr = array();
          $arr[] = $key;
          $arr[] = $v;
          if($this->server->zrem($this->app_ping, serialize($arr)))
            break;
        }
        catch(Exception $e){}
      }
    }
  }/*}}}*/

  public function resubmit_timeout($count=20)/*{{{*/
  {
    $values = array();

    try
    {
      while(true)
      {
        $values = $this->server->zrangebyscore($this->app_ping, '-inf', time(), array('limit'=>array(0,$count)));
        if(!empty($values))
        {
          foreach($values as $v)
          {
            $this->server->zrem($this->app_ping, $v);
            $value = unserialize($v);
            $this->put($value[0], $value[1]);
          }
        }
        else
        {
          break;
        }
      }
    }
    catch(Exception $e){}

    return $values;
  }/*}}}*/

  public function get_queue_count()/*{{{*/
  {
    $count = 0;
    foreach($this->priority as $v)
    {
      try
      {
        $count += $this->server->llen($v);
      }
      catch(Exception $e){}
    }

    return $count;
  }/*}}}*/

  public function get_ping_queue_count()/*{{{*/
  {
    $count = 0;
    try
    {
      $count = $this->server->zcount($this->app_ping, '-inf', '+inf');
    }
    catch(Exception $e){}

    return $count;
  }/*}}}*/

  private function get_from_list($listname, $count)/*{{{*/
  {
    $values = array();
    try
    {
      $values = $this->server->lrange($listname, 0, $count-1);
      for($index=0; $index<count($values); $index+=1)
        $this->server->lpop($listname);
    }
    catch(Exception $e)
    {}

    return $values;
  }/*}}}*/

  private $seraddr;
  private $server;
  private $app;
  private $app_ping;
  private $app_timeout;
  private $commit;
  private $timeout;
  private $gettimes;
}

/*$server = "redis://se7.white.lft.qihoo.net:6580";
$app = "av_scan";
$queue = new PriorityQueue($server, $app, 5);
for($index=1; $index<3; $index+=1)
{
   $queue->put(10, $index);
   echo sprintf("%s\t%s\n", 10, $index);
}

$queue->put(10, 'a');
echo sprintf("%s\t%s\n", 10, 'a');
$queue->put(10, 'c');
echo sprintf("%s\t%s\n", 10, 'c');

for($index=100; $index<103; $index+=1)
{
  $queue->put(30, $index);
  echo sprintf("%s\t%s\n", 30, $index);
}

for($index=8; $index<12; $index+=1)
{
  $queue->put(20, $index);
  echo sprintf("%s\t%s\n", 20, $index);
}

$value = $queue->get(10);
var_dump($value);

echo sprintf("queuesize=%s\nping_queuesize=%s\n", $queue->get_queue_count(), $queue->get_ping_queue_count());

$queue->commit($value);
echo sprintf("queuesize=%s\nping_queuesize=%s\n", $queue->get_queue_count(), $queue->get_ping_queue_count());
*/
?>
