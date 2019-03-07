<?php
class DbDriver
{
  public $link = false;
  private $dsn;
  private $dbuser;
  private $dbpw;
  private $dbhost;
  private $dbname;

  function __construct($dbhost, $dbname, $dbuser, $dbpw)
  {
    $this->dbhost = $dbhost;
    $this->dbname = $dbname;
    $this->dsn = sprintf('mysql:dbname=%s;host=%s', $dbname, $dbhost);//$dbname;
    $this->dbuser = $dbuser;
    $this->dbpw = $dbpw;
    $this->connect(false);
  }

  function __destruct()
  {
    if ($this->link)
      $this->link = null;
  }

  private function dbConnect($pconnect = false)
  {
    if ($this->link)
      return true;

    return $this->connect($pconnect);
  }

  private function connect($pconnect)
  {
    //$this->link = new PDO($this->dsn, $this->dbuser, $this->dbpw);
    $this->link = mysqli_connect($this->dbhost,$this->dbuser,$this->dbpw,$this->dbname);//new PDO($this->dsn, $this->dbuser, $this->dbpw);

    if (!$this->link)
      return false;
    $this->query('set names utf8;');
    return true;
  }

  // return array() if error
  function executeRead($sql)
  {
    if (!$this->dbConnect())
      return false;

    $ret = $this->link->query($sql);
    //$query->setFetchMode(PDO::FETCH_ASSOC);

    //$data = $ret->fetch_array();
    $data = array();
    while ($row = $ret->fetch_assoc()) {
      $data[] = $row;
    }
    return $data;
  }

  function getOneRecord($sql)
  {
    if (!$this->dbConnect())
      return false;

    $query = $this->link->query($sql);

    $data = $query->fetch_assoc();
    return $data;
  }

  function getRowCount($sql)
  {
    $ret = $this->getOneRecord($sql);
    return intval($ret['count']);
  }

  // return rows affected
  function query($sql)
  {
    if (!$this->dbConnect())
      return false;
    $query = $this->link->query($sql);
    if(DBDEBUG == 1)
    {
      global $logger;
      qLogInfo($logger, $sql);
      qLogInfo($logger, json_encode($this->error()));
    }

    return $query;
  }

  function update($sql)
  {
    $update = false;
    if (!$this->dbConnect())
      return false;
    $query = $this->link->query($sql);
    if(DBDEBUG == 1)
    {
      global $logger;
      qLogInfo($logger, $sql);
      qLogInfo($logger, json_encode($this->error()));
    }
    if($query !== false)
      $update = true;

    return $update;
  }

  function error()
  {
    return $this->link->error_list;
  }

  function errno()
  {
    return $this->link->errno();
  }

  function insert_id()
  {
    return $this->link->insert_id;
  }

  function close()
  {
    if ($this->link)
    {
      $this->link = false;
    }
  }

}
?>
