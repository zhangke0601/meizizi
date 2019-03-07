<?php
//------------------------------------------

class Load {
	
	var $m_filelist;

	//------------------------------------------
	function __construct($defarray, $stagename) 
	{
		$this->statistic['succ'] = 0;
		$this->statistic['total'] = 0;

		$this->env_info['tag']       = $defarray[$stagename]['tag'];
		$this->env_info['logcfg']    = $defarray[$stagename]['logcfg'];
		$this->m_filelist            = $defarray[$stagename]['filelist'];

		if (true == function_exists('qLogInfo')) {
//                        qLogConfig ( $this->env_info['logcfg']);
                }
	}

	//------------------------------------------
	function __destruct() 
	{

	}

	//------------------------------------------
	function Do_one_record($buf) 
	{
	}

	//------------------------------------------
	function GetStatisticInfo() 
	{

	}

	//------------------------------------------
	function Run() 
	{
		$flist = fopen($this->m_filelist, "r");		
		if ($flist == false) {
			printf("open file [%s] failed \n", $this->m_filelist);
			return false;
		}

		while ( $line = fgets($flist)) {
			$fname = trim($line);
			$_fp = fopen($fname, "r");
			if ($_fp == false) {
				printf("open file [%s] failed ...\n", $fname);
				continue;
			}
		
			while ($buf = fgets($_fp)) {
				if ($this->Do_one_record(trim($buf)) != false ) {
					$this->statistic['succ']++;
				} 
				
				$this->statistic['total']++;
			}

			fclose($_fp);

			rename($fname, $fname.".bak");
		}

		$this->GetStatisticInfo();

		fclose($flist);
	}
}
