<?php
class DbDriver
{
    public $link = false;
    public $connectDelay = 5;
    public $qlog = true;
    private $dbhost;
    private $dbname;
    private $dbuser;
    private $dbpw;
    private $connectTime = 0;
    public  $QLOG_MODULE = 'mysql';
    private $session_wait_timeout = false;

  function __construct($dbhost, $dbname, $dbuser, $dbpw, $session=false)
  {
    $this->dbhost = $dbhost;
    $this->dbname = $dbname;
    $this->dbuser = $dbuser;
    $this->dbpw = $dbpw;
    $this->session_wait_timeout = $session;
  }

  function __destruct()
  {
    if ($this->link)
      mysql_close($this->link);
  }

  private function dbConnect($pconnect = false)
  {
    if ($this->link)
    {
      if (mysql_ping($this->link))
        return true;

      // reconnect
      $this->logerror('ping '.$this->dbhost.' failed.');
      if ($this->connect($pconnect))
        return true;

      $this->link = false;
      $this->connectTime = time() + $this->connectDelay;
      return false;
    }

    // reconnect failed. delay connectDelay second.
    if (time() < $this->connectTime)
      return false;

    return $this->connect($pconnect);
  }

  private function connect($pconnect)
  {
    if ($pconnect)
      $this->link = mysql_pconnect($this->dbhost, $this->dbuser, $this->dbpw);
    else
      $this->link = mysql_connect($this->dbhost, $this->dbuser, $this->dbpw, true);

    if (!$this->link)
    {
      $this->logerror('connect to '.$this->dbhost.' failed.');
      return false;
    }

    if ($this->dbname)
        mysql_select_db($this->dbname, $this->link);

    if($this->session_wait_timeout)
        mysql_query("set session wait_timeout = 365 * 24 * 3600", $this->link);
    return true;
  }

  // return array() if error
  function executeRead($sql, $buffer=true)
  {
    $result = $this->query($sql, $buffer);
    if (!$result)
      return array();

    $data = array();
    while ($row = $this->fetch_array($result))
    {
      $data[] = $row;
    }
    mysql_free_result($result);
    return $data;
  }

  // return false if error
  function select_db($dbname)
  {
    if (!$this->dbConnect())
      return false;
    return mysql_select_db($dbname, $this->link);
  }

  // return false if error
  function query($sql, $buffer=true)
  {
    if (!$this->dbConnect())
      return false;
    if ($buffer)
      $query = mysql_query($sql, $this->link);
    else
      $query = mysql_unbuffered_query($sql, $this->link);
    return $query;
  }

  // return false if error
  function fetch_array($query, $result_type = MYSQL_ASSOC)
  {
    return mysql_fetch_array($query, $result_type);
  }

  function affected_rows()
  {
    return mysql_affected_rows($this->link);
  }

  function error()
  {
    return mysql_error($this->link);
  }

  function errno()
  {
    return mysql_errno($this->link);
  }

  function result($query, $row)
  {
    $query = @mysql_result($query, $row);
    return $query;
  }

  function num_rows($query)
  {
    return mysql_num_rows($query);
  }

  function num_fields($query)
  {
    return mysql_num_fields($query);
  }

  function free_result($query)
  {
    return mysql_free_result($query);
  }

  function insert_id()
  {
    return mysql_insert_id($this->link);
  }

  function fetch_row($query)
  {
    return mysql_fetch_row($query);
  }

  function fetch_fields($query)
  {
    return mysql_fetch_field($query);
  }

  function escape_string($str)
  {
    if (!$this->dbConnect())
      return false;
    return mysql_real_escape_string($str, $this->link);
  }

  function version()
  {
    return mysql_get_server_info($this->link);
  }

  function close()
  {
    if ($this->link)
    {
      mysql_close($this->link);
      $this->link = false;
    }
  }

  function logerror($message)
  {
    if ($this->qlog)
      qAppError($this->QLOG_MODULE, $message.' -- ('. mysql_errno().')'.mysql_error());
    else
      echo $this->QLOG_MODULE.' '.$message.' -- ('. mysql_errno().')'.mysql_error()."\n";
  }

  function get_info()
  {
    return mysql_info($this->link);
  }
}

?>
