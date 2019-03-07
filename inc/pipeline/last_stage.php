<?php
require_once("qpipestage.php");

class LastStage extends Stage {
	var $bchecksig;
	var $statistic = array();
	var $toDelXmlName;
	var $toStageName;
	//var $ostream;

	function __construct($defarray, $stagename)
	{
		parent::__construct($defarray, $stagename);	
		$this->bchecksig = false;

		$this->statistic['total'] = 0;                        
		$this->statistic['succ']  = 0;
		$this->toStageName = $defarray[$stagename]['tostagename'];
		$this->detectPath = $defarray[$stagename]['xmlpath'];
		/*
		try {
			$this->ostream = new OutStream($defarray, $stagename);
		} catch (Exception $e) {
			$message = sprintf("creat out stream failed [%s]", $e->getMessage());
			throw new Exception($messge);
		}
		*/
	}

	function __destruct()
	{
		parent::__destruct();
		//unset($this->ostream);
	}

	function SetSig()
	{
		$this->bchecksig = true;
	}

	function GetStatisticInfo()
	{
	}

	function Run()
	{
		while (1) {
			if ($this->bchecksig == true)  {
//				qLogInfo($this->env_info['tag'], "receive quit signal and quit");	
				//$this->ostream->SetLastName();
				break;
			}
			//$this->ostream->SetCurrTime();

			/* get a new package */
			$__package = array();

			if (get_package($this->worker, $__package) <= 0) {
//				qLogInfo( $this->env_info['tag'], sprintf("!!! waring:get package failed or no waiting package for process wait gap[%ld]s then continue ...!!! \n", $this->env_info['spin_gap']));
				sleep($this->env_info['spin_gap']);
				continue;
			}
			else {
//				qLogInfo($this->env_info['tag'], sprintf("get package succ, uri[%s] | pid[%s]\n", $__package['uri'], $__package['packageid']));
			}			       

			/* download the package */
			$download_buf = null;
			$download_buf = $this->Download($__package['uri']);
			if ($download_buf == null) {
//				qAppError( $this->env_info['tag'],  sprintf("!!!waring:dowload failed|uri:[%s]!!!\n\n",$__package['uri'])) ;
				continue ;		
			} 

//			qLogInfo( $this->env_info['tag'],  sprintf("\t=====<download to memory succ>[%s]|====", $__package['uri'])) ;
			
			/* check the package */ 
			$size = strlen($download_buf);

			$__package['size'] = $size;
			$__package['checksum'] = $size;

			$icheck =  check_get($this->worker, $__package);
			if ($icheck < 0) {
//				qAppError( $this->env_info['tag'], sprintf("!!!waring:check_get failed [%d] checksum [%s]|size:[%d]===", $icheck, $__package['checksum'], $__package['size']));

				sleep($this->env_info['spin_gap']);
				continue;

			} else {
//				qLogInfo( $this->env_info['tag'], sprintf("=====<checksum succ>|checksum [%s]|size:%d|===", $__package['checksum'], $__package['size']));			
			}

			/* process the package */
			if ( $this->Do_process($download_buf) == 0) {
				$this->GetStatisticInfo($__package);
//				qLogInfo( $this->env_info['tag'], sprintf("package:[%s] process total:[%s] succ:[%s]", $__package['uri'], $this->statistic['total'], $this->statistic['succ']));

				$this->statistic['total'] = 0;
				$this->statistic['succ']  = 0;

				sleep($this->env_info['spin_gap']);
			}
		}		
	}

	//------------------------------------------
	function Do_process (&$_buf)
	{
		$xml_array = explode("\n", $_buf); 
		$this->toDelXmlName = $this->toStageName."_".getmypid()."_".date("YmdHis", time());
		echo $this->toDelXmlName."\n";
		foreach($xml_array as $xml_item) {
			if ( $xml_item == "")
			break;

			$ret = $this->Do_one_record($xml_item);
			if ($ret != false) {
				//$this->ostream->Outprint($out_buf);

				$this->statistic['succ']++;
			}

			$this->statistic['total']++;
		}
		error_log("done", 3, $this->detectPath.$this->toDelXmlName.".done");
		return 0;
	}

	function Creat_out_buf($_xml_doc) 
	{
	}
}


?>
