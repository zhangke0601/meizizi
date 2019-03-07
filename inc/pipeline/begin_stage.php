<?php
require_once("qpipestage.php");

//------------------------------------------

class BeginStage extends Stage {
	var $bchecksig;
	var $statistic = array();
	var $ostream;
    var $m_port;
	function __construct($defarray, $stagename)
    {
        parent::__construct($defarray, $stagename);	
        $this->bchecksig = false;
        $this->statistic['total'] = 0;                        
        $this->statistic['succ']  = 0;
        $this->m_port = $defarray[$stagename]['port'];
        try
        {
            $this->ostream = new OutStream($defarray, $stagename);
        }
        catch (Exception $e) 
        {
            $message = sprintf("creat out stream failed [%s]", $e->getMessage());
            throw new Exception($messge);
        }
    }

	//------------------------------------------
	function __destruct()
	{
		parent::__destruct();
		unset($this->ostream);
	}
	//------------------------------------------
	function SetSig()
	{
		$this->bchecksig = true;
	}

	//------------------------------------------
	function GetStatisticInfo()
	{
    }

    function commit($pkg)
    {/*{{{*/
        $hostname = strtolower(str_replace('.360so.net','.qihoo.net',php_uname('n')));
	    $pkg['path'] = str_replace("/usr/home", "/home", $pkg['path']);
        $__uri = sprintf("http://%s:%s%s", $hostname, $this->m_port, $pkg['path']);
        $__pkg['uri']  = $__uri;
        $__pkg['size'] = filesize($pkg['path']);
        $__pkg['items']   = $pkg['line'];
        $__pkg['checksum']= filesize($pkg['path']);
        $iRet = commit_and_get($this->worker, $__pkg, $__pkg);
        if ($iRet < 0 && $iRet != -1002)
        {
            $mes = sprintf("[%s] packageid: [%s], uri[%s], commit failed", date('Y/m/d H:i:s'), $__pkg['packageid'], $__pkg['uri']);
            qAppError($this->env_info['tag'], $mes);
        }
        else
        {
            qLogInfo($this->env_info['tag'], sprintf(" commit succ pkg_id:%s\tpkg_item:%s\tpkg_size:%s", $__pkg['packageid'], $__pkg['items'], $__pkg['size']));
        }

    }/*}}}*/

	//------------------------------------------
	function Run()
    {/*{{{*/
        $this->ostream->SetCurrTime();
        $this->ostream->GetCurrFileLine();
        //$this->ostream->GetCurrFileSize();
        while(1)
        {
            echo "111111111111";
            exit;
            if($this->bchecksig == true)
            {
				qLogInfo($this->env_info['tag'], "receive quit signal and quit");	
				//$this->ostream->SetLastName();
				break;
            }
            $this->Do_process();
		}		
    }/*}}}*/

	//------------------------------------------
	function Do_process ()
    {

	}

	//------------------------------------------
	function Creat_out_buf($_xml_doc) 
	{
	}
}

//------------------------------------------

?>
