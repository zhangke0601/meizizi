<?php

require_once ('qpipeline.php');

define ('_SUCC' , '0');
define ('_ERROR', '-1');

//----------------------------------------------
class OutStream{
	var $m_env =  array();
	var $split_condition = array();

	var $m_fp;
	var $m_filename;
	var $m_lastname;
	var $_complete_name;
	//----------------------------------------------
	function __construct($defarray, $stagename)
    {/*{{{*/
        $this->split_condition['maxline'] = $defarray[$stagename]['out_split_maxline'];
        $this->split_condition['timeout'] = $defarray[$stagename]['out_split_timeout'];
        $this->split_condition['maxsize'] = $defarray[$stagename]['out_split_maxsize'];

        $this->m_env['outpath']   = $defarray[$stagename]['outpath'];
        $this->m_env['curr_size'] = 0;
        $this->m_env['curr_line'] = 0;
        $this->m_env['curr_time'] = time();
        $this->m_env['last_time'] = $this->m_env['curr_time'];
        $this->m_env['curr_slice']= 0;
        $this->m_env['tag'] = $stagename;
	    $this->_complete_name = "";
        $this->m_filename = "";
        if ($this->GetOutHandle() == false) {
            throw new Exception("get output file handle error"); 
        }

        $this->SetLastName();
    }/*}}}*/

	//----------------------------------------------
	function __destruct()
    {/*{{{*/
        //$this->ChangeName();	
        //qLogInfo($this->env_info['tag'],"ostream destruct\n");
    }/*}}}*/

	//----------------------------------------------
	function GetOutHandle()
    {/*{{{*/
        if (file_exists($this->m_env['outpath']) == false) {
            if (mkdir($this->m_env['outpath'], 0766) == false) {
                return false;	
            }
        }

        $prefix = "Inc_".$this->m_env['tag'];
        $hostname = strtolower(str_replace('.360so.net','.qihoo.net',php_uname('n')));
        $hostname = explode(".", $hostname);
        $hostname = $hostname[0];


        $this->m_filename = sprintf("%s/%s_%s_%s_%s_%s.tmp",
                                    $this->m_env['outpath'],
                                    $prefix,
                                    $hostname,
                                    getmypid(),
                                    $this->m_env['curr_slice'],
                                    date("YmdHis", time())
                                   );

		$this->m_fp = fopen($this->m_filename, "a+");	
		if ($this->m_fp == false) {
			qAppError($this->m_env['tag'], sprintf("open file [%s] failed", $this->m_filename));
			return false; 
		}

        $this->m_env['curr_line'] = 0;
        $this->m_env['curr_size'] = 0;
        $this->m_env['last_time'] = $this->m_env['curr_time'];
        $this->m_env['curr_slice']++;

        $this->ChangeName();

        return true;
    }/*}}}*/
	//----------------------------------------------
	function ChangeName()
    {/*{{{*/
        if ($this->m_lastname != "") {
            $this->_complete_name = str_replace("tmp", "xml", $this->m_lastname);
            //var_dump($this->m_lastname);
            //var_dump($this->_complete_name);exit;
            if (rename($this->m_lastname, $this->_complete_name) == false) {
                qAppError($this->m_env['tag'], sprintf("rename [%s] to [%s] failed", $$this->m_lastname, $this->_complete_name));
            }	
        }
    }/*}}}*/

	function CheckSplitCondition()
    {/*{{{*/
        if ($this->m_env['curr_line'] >= $this->split_condition['maxline']) {
            return true;
        }

        if ($this->m_env['curr_size'] >= $this->split_condition['maxsize']) {
            return true;
        }

        if ( ($this->m_env['curr_time'] - $this->m_env['last_time']) > $this->split_condition['timeout']) {
            return true;
        }

        return false;
    }/*}}}*/

	function Outprint($_buf)
    {/*{{{*/
        if ($this->CheckSplitCondition() == true) {
            $this->SetLastName();
            $this->GetOutHandle();
        }

        fprintf($this->m_fp, "%s", $_buf);	

        $this->m_env['curr_line']++;
        $this->m_env['curr_size'] += strlen($_buf) + 1;
    }/*}}}*/

    function Outprint_Cmt($_buf)
    {/*{{{*/
        //var_dump($this->m_fp);
        //var_dump($this->m_env);
        //var_dump($this->split_condition);
        //var_dump($this->m_filename);
        //var_dump($this->m_lastname);
        //var_dump($this->_complete_name);
        fprintf($this->m_fp, "%s", $_buf);	
        $this->m_env['curr_line']++;
        $this->m_env['curr_size'] += strlen($_buf);

        if ($this->CheckSplitCondition() == true) {
            $pkg_info['line'] = $this->m_env['curr_line'];
            $pkg_info['size'] = $this->m_env['curr_size'];
            $this->SetLastName();
            $this->GetOutHandle();
            $pkg_info['path'] = $this->_complete_name;
        } else {
            $pkg_info = null;
        }

        //fprintf($this->m_fp, "%s", $_buf);	

        //$this->m_env['curr_line']++;
        //$this->m_env['curr_size'] += strlen($_buf) + 1;
        //var_dump($pkg_info);exit;
        return $pkg_info; 
    }/*}}}*/

	//----------------------------------------------
	function GetLastPkgInfo()
    {/*{{{*/
        $pkg_info['line'] = $this->m_env['curr_line'];
        $pkg_info['size'] = $this->m_env['curr_size'];
        $this->SetLastName();
        $this->ChangeName();

        $pkg_info['path'] = $this->m_lastname;

        return $pkg_info;
    }/*}}}*/

	//----------------------------------------------
	function SetCurrTime()
	{/*{{{*/
		$this->m_env['curr_time'] = time();	
	}/*}}}*/

	//----------------------------------------------
	function SetLastName()
    {/*{{{*/
        if ($this->m_filename != "") {
            $this->m_lastname = $this->m_filename;	
        } else {
            $this->m_lastname = "";
        }
    }/*}}}*/

	//----------------------------------------------
	function GetCurrFileName()
	{/*{{{*/
		return $this->m_filename;	
	}/*}}}*/

	//----------------------------------------------
	function GetCurrFileSize()
	{/*{{{*/
		return $this->m_env['curr_size'];	
	}/*}}}*/

	//----------------------------------------------
	function GetCurrFileLine()
	{/*{{{*/
		return $this->m_env['curr_line'];	
	}/*}}}*/
}

//----------------------------------------------

class Stage {
	var $stage_info = array();
	var $env_info = array();

	var $worker;

	//----------------------------------------------
    function __construct ($defarray, $stagename)
    {/*{{{*/

        $this->stage_info['stagename'] = $defarray[$stagename]['stagename'];
        $this->stage_info['provide']   = $defarray[$stagename]['provide'];
        $this->stage_info['consume']   = $defarray[$stagename]['consume'];
        $this->stage_info['server']    = $defarray[$stagename]['server'];

        //$this->env_info['inputpath'] = $defarray[$stagename]['inputpath'];
        $this->env_info['outpath']   = $defarray[$stagename]['outpath'];
        $this->env_info['spin_gap']  = $defarray[$stagename]['spin_gap'];
        $this->env_info['port']      = $defarray[$stagename]['port'];
        $this->env_info['logcfg']    = $defarray[$stagename]['logcfg'];
        $this->env_info['tag']       = $defarray[$stagename]['tag'];

        /* init qlog */
        if (true == function_exists('qLogInfo')) {
            qLogConfig ( $this->env_info['logcfg']);
        }
        /* join the pipeline */
        if ($this->Join_BB() == _ERROR) {
            throw new Exception("connect BB failed");	
        }
    }/*}}}*/

	//----------------------------------------------
	function Join_BB()
    {/*{{{*/
        $this->worker = join_pipeline($this->stage_info['stagename'], $this->stage_info['provide'], $this->stage_info['consume'], $this->stage_info['server']);
        if ($this->worker == -1) {
            qAppError($this->env_info['tag'], "\nconnect BB failed");
            return _ERROR;
        }
        else {
            qLogInfo($this->env_info['tag'], "**********************************");
            qLogInfo($this->env_info['tag'], sprintf("*** %s connect BB OK:  ***", $this->stage_info['stagename']));
            qLogInfo($this->env_info['tag'], sprintf("*********   workid; [%s]  *****", $this->worker['workerid']));
            qLogInfo($this->env_info['tag'], "**********************************\n");
        }
        return _SUCC;	
    }/*}}}*/

	//----------------------------------------------
	function Leave_BB()
    {/*{{{*/
        $ileave = leave_pipeline($this->worker);
        if ($ileave == 0) {
            qLogInfo($this->env_info['tag'], "***************************************");
            qLogInfo($this->env_info['tag'], "leave BB");
            qLogInfo($this->env_info['tag'],"***************************************\n");
        }			
    }/*}}}*/

	//----------------------------------------------
	function Download($_uri)
    {/*{{{*/
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $_uri);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, 120);

        $resp = curl_exec($curl_handle);

        $result['http_code'] = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

        curl_close($curl_handle);

        if ($result['http_code'] != 200) 
            return null;

        return $resp; 
    }/*}}}*/

	//----------------------------------------------
	function Run()
	{}
	//----------------------------------------------
	function Do_process()
	{}

	//----------------------------------------------

	function __destruct()
	{/*{{{*/
		$this->Leave_BB();		
	}/*}}}*/
}

//----------------------------------------------

function sig_handle($signo)
{/*{{{*/
    //global $g_bchecksig;
    global $_stage;

    if($signo == SIGINT) {
        printf("catch SIGINT and exit!\n");
        //$g_bchecksig = true;
        $_stage->SetSig();
    }

    if($signo == SIGTERM) {
        printf("catch SIGTERM and exit!\n");
        //$g_bchecksig = true;
        $_stage->SetSig();
    }
}/*}}}*/

function InitSignal() 
{/*{{{*/
    pcntl_signal(SIGTERM, "sig_handle");
    pcntl_signal(SIGINT, "sig_handle");
}/*}}}*/

?>
