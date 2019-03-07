<?php
/*
 * 功能：一个先进先出队列，通过继承一个优先级队列来实现
 * 作者：fengerbo@360.cn
 * 时间：2011-08-19
 */

require_once("priorityqueue.php");

class FifoQueue extends PriorityQueue
{
  public function __construct($server, $app, $timeout=1800, $commit=true)/*{{{*/
  {
    parent::__construct($server, $app, $timeout, $commit);
  }/*}}}*/

  public function put($value)/*{{{*/
  {
    return parent::put(0, $value);
  }/*}}}*/

}

/*$server = "redis://se7.white.lft.qihoo.net:6580";
$app = "whitelist";
$queue = new FifoQueue($server, $app);

for($index=0; $index<4; $index+=1)
  $queue->put($index+3);

for($index=0; $index<4; $index+=1)
  $queue->put($index+2);

var_dump($queue->get(10));*/
?>
