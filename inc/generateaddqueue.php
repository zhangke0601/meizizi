<?php
/*
 * 用途：产生自增ID
 * 作者：fengerbo@360.cn
 * 时间：2011-09-07
 */

require_once("Predis.php");
class AddQueue
{
  function __construct($server, $key, $step=1)
  {
    $this->key    = $key;
    $this->step   = $step;
    $this->seraddr = $server;
    $this->server = new Predis_Client($this->seraddr);
    $this->exist  = false;
  }

  function get()
  {
    $id = 0;
    try
    {
      if($this->exist)
      {
        if(!$this->server->exists($this->key))
          $this->server->set($this->key, 0);
        $this->exist = true;
      }

      if($this->step == 1)
        $id = $this->server->incr($this->key);
      else
        $id = $this->server->incrby($this->key);
    }
    catch(Exception $e){}

    return $id;
  }

  private $step;
  private $key;
  private $exist;
  private $server;
}

/*$queue = new AddQueue("redis://se7.white.lft.qihoo.net:6680",'scanid'); 
echo sprintf("%s\n", $queue->get());
sleep(1);
echo sprintf("%s\n", $queue->get());
sleep(1);
echo sprintf("%s\n", $queue->get());
sleep(1);
echo sprintf("%s\n", $queue->get());
sleep(1);*/
?>
