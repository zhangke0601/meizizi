<?php
/*
 * 重新实现调度策略的GearmanClient
 * 改进：依据队列容量和当前队列数量实现负载均衡机制
 *       防止队列满时client被挂住
 *       防止队列数据超出设置的上限，队列满则不发送数据
 *       防止server断开时的长时间等待
 */
class GearmanClient2
{
	private $m_monitor = null;
	private $m_gmclients = array();
	private $m_timeout = 50000;
	private $m_returnCode = GEARMAN_SUCCESS;
	private $m_error = '';

	function __construct()
	{
		$this->m_monitor = new GearmanStatus(5);
	}

	function addServer($host, $port = 4730)
	{
		$serv = $host.':'.$port;
		return $this->addServers($serv);
	}

	function addServers($hosts)
	{
		$servers = explode(',', $hosts);
		foreach($servers as $serv)
			$this->addserv(trim($serv));
		return true;
	}

	function doBackground($function_name, $workload, $unique=null)
	{
		$selected = $this->selectServer($function_name);
		if ($selected == false)
		{
			$this->m_returnCode = GEARMAN_NO_SERVERS;
			$this->m_error = 'selectServer: no servers';
			return '';
		}
		list($host, $stat) = $selected;
		if ($stat !== false)
		{
			if ($stat[4] > 0 && $stat[1] >= $stat[4])
			{
				$this->m_returnCode = GEARMAN_COULD_NOT_CONNECT;
				$this->m_error = $function_name . ' reached to ' . $stat[1];
				return '';
			}
		}
        
        $gmclient = $this->m_gmclients[$host];
        if($unique)
    		$job_handle = $gmclient->doBackground($function_name, $workload, $unique);
        else
            $job_handle = $gmclient->doBackground($function_name, $workload);

		$this->m_returnCode = $gmclient->returnCode();
		$this->m_error = $gmclient->error();
		if ($this->m_returnCode == GEARMAN_TIMEOUT)
			$this->addserv($host, true);
		return $job_handle;
	}

	function doHighBackground($function_name, $workload, $unique=null)
	{
		$selected = $this->selectServer($function_name);
		if ($selected == false)
		{
			$this->m_returnCode = GEARMAN_NO_SERVERS;
			$this->m_error = 'selectServer: no servers';
			return '';
		}
		list($host, $stat) = $selected;
		if ($stat !== false)
		{
			if ($stat[4] > 0 && $stat[1] >= $stat[4])
			{
				$this->m_returnCode = GEARMAN_COULD_NOT_CONNECT;
				$this->m_error = $function_name . ' reached to ' . $stat[1];
				return '';
			}
		}
		$gmclient = $this->m_gmclients[$host];
        
        if($unique) 
          $job_handle = $gmclient->doHighBackground($function_name, $workload, $unique);
        else
          $job_handle = $gmclient->doHighBackground($function_name, $workload);

		$this->m_returnCode = $gmclient->returnCode();
		$this->m_error = $gmclient->error();
		if ($this->m_returnCode == GEARMAN_TIMEOUT)
			$this->addserv($host, true);
		return $job_handle;
	}

	function doLowBackground($function_name, $workload, $unique=null)
	{
		$selected = $this->selectServer($function_name);
		if ($selected == false)
		{
			$this->m_returnCode = GEARMAN_NO_SERVERS;
			$this->m_error = 'selectServer: no servers';
			return '';
		}
		list($host, $stat) = $selected;
		if ($stat !== false)
		{
			if ($stat[4] > 0 && $stat[1] >= $stat[4])
			{
				$this->m_returnCode = GEARMAN_COULD_NOT_CONNECT;
				$this->m_error = $function_name . ' reached to ' . $stat[1];
				return '';
			}
		}
		$gmclient = $this->m_gmclients[$host];

        if($unique) 
            $job_handle = $gmclient->doLowBackground($function_name, $workload, $unique);
        else
            $job_handle = $gmclient->doLowBackground($function_name, $workload);

		$this->m_returnCode = $gmclient->returnCode();
		$this->m_error = $gmclient->error();
		if ($this->m_returnCode == GEARMAN_TIMEOUT)
			$this->addserv($host, true);
		return $job_handle;
	}

	function returnCode()
	{
		return $this->m_returnCode;
	}

	function error()
	{
		return $this->m_error;
	}

	function setTimeout($timeout)
	{
		$this->m_timeout = max(1000, $timeout);
		$this->m_timeout = min(50000, $this->m_timeout);
		foreach($this->m_gmclients as $gmclient)
			$gmclient->setTimeout($this->m_timeout);
		return true;
	}

	function timeout()
	{
		return $this->m_timeout;
	}

	private function addserv($serv, $renew=false)
	{
		list($hostname, $port) = explode(':', $serv);
		if ( !$port )
			$port = 4730;
		
		$tmp = $hostname.':'.$port;
		if ( $renew || !array_key_exists($tmp, $this->m_gmclients) )
		{
			$gmclient = new GearmanClient;
			$ret = $gmclient->addServers($tmp);
			if ( !$ret )
			{
				$this->m_returnCode = $gmclient->returnCode();
				$this->m_error = $gmclient->error();
				return false;
			}
			$gmclient->setTimeout($this->m_timeout);
			$this->m_gmclients[$tmp] = $gmclient;
		}
		return true;
	}

	private function selectServer($function_name)
	{
		if ( empty($function_name) )
			return false;

		$hosts = array_keys($this->m_gmclients);
		$besthost = false;
		$beststat = false;
		foreach ($hosts as $host)
		{
			$stat = $this->m_monitor->get_host_status($host);
			if ($stat === false) //server down
				continue;
			if ( !isset($stat[$function_name]) ) //首次使用此队列
			{
				$besthost = $host;
				$beststat = false;
				break;
			}

			$stat = $stat[$function_name];
			if ($beststat === false)
			{
				$besthost = $host;
				$beststat = $stat;
				continue;
			}
			//选取最空闲队列
			if ($stat[4] > 0 && $beststat[4] > 0)
			{
				$delta1 = $beststat[4] - $beststat[1];
				$delta2 = $stat[4] - $stat[1];
				if ($delta2 > $delta1)
				{
					$besthost = $host;
					$beststat = $stat;
				}
				continue;
			}
			if ($stat[4] == 0 && $beststat[4] == 0)
			{
				if ($stat[1] < $beststat[1])
				{
					$besthost = $host;
					$beststat = $stat;
				}
				continue;
			}
			//选取已经设置上限的队列,直到队列满
			if ($stat[4] > 0) // $beststat[4] == 0
			{
				if ($stat[1] < $stat[4])
				{
					$besthost = $host;
					$beststat = $stat;
				}
				continue;
			}
			// now $beststat[4] > 0 and $stat[4] == 0
			if ($beststat[1] >= $beststat[4])
			{
				$besthost = $host;
				$beststat = $stat;
			}
		}
		if ($besthost === false)
			return false;
		return array($besthost, $beststat);
	}
}

/*
 * 功能：获取队列状态
 * 性能：维护连接池以保持长连接、Server断开后自动重连、延迟重连 
 */
class GearmanStatus
{
	function __construct($delay)
	{
		$this->delay = max($delay, 1);
	}

	/*
	 * 获取队列状态
	 * @return status or false
	 */
	function get_host_status($host)
	{
		$status = array();
		$fp = $this->connect($host);
		if (!$fp)
			return false;

		$ret = fwrite($fp, "status\n");
		if (!$ret)
		{
			$this->remove($host);
			return false;
		}

		while(1)
		{
			$line = fgets($fp);
			if ($line === false || feof($fp))
			{
				$this->remove($host);
				return false;
			}
			//echo $host, '->(', strlen($line), ')', $line;
			if ($line == ".\n")
				break;
			$cols = explode("\t", trim($line));
			if (empty($cols[0]))
				continue;
			$status[$cols[0]] = $cols;
		}
		//print_r($status);
		return $status;
	}

	//return fd or false
	private function connect($host)
	{
		if ( isset($this->conns[$host]) )
		{	
			$fd = $this->conns[$host]['fd'];
			if ($fd) //connected
				return $fd;
			//not connected
			$conntime = $this->conns[$host]['time'];
			if (time() < $conntime)
				return false;
		}
		
		// need connect
		list($hostname, $port) = explode(':', $host);
		if (!$port)
			$port = 4730;
		$fp = fsockopen($hostname, $port,$err,$errstr,5);
		$this->conns[$host]['fd'] = $fp;
		if ($fp === false)
			$this->conns[$host]['time'] = time() + $this->delay;
		//echo $fp, ': connected to ', $host, PHP_EOL;
		return $fp;
	}

	private function remove($host)
	{
		//echo $this->conns[$host]['fd'], ': closed to ', $host, PHP_EOL;
		fclose($this->conns[$host]['fd']);
		$this->conns[$host]['fd'] = false;
		$this->conns[$host]['time'] = time() + $this->delay;
	}

	function __destruct() 
	{
		foreach($this->conns as $conn)
		{
			if ($conn['fd'])
				fclose($conn['fd']);
		}
	}

	private $conns = array();
	private $delay;
}

?>
