<?php

/**
 * pipeline worker
 *
 * @example
 * $worker = new Pipeline('pipe.qihoo.net:8360', 'test_stage', 'test_putpkg',
 * 'test_getpkg');
 * $putpkg = array(); $getpkg = array();
 * $ret = $worker->commit_and_get($putpkg, $getpkg);
 * $worker->get_contents($getpkg['uri']);
 *
 * @author hanyugang (2010-5-8)
 */
class Pipeline
{
    public $errno = -2;
    public $error = '';

    /**
     * 构造一个pipeline worker实例
     *
     * @param server [in] pipeline server地址
     * @param stagename [in] stage名称
     * @param provide [in] 生成的package名称
     * @param consume [in] 获取的package名称
     * @param debug [in] 设置为TRUE则显示调试信息
     */
    public function __construct($server, $stagename, $provide, $consume, $debug = FALSE)
    {/*{{{*/
        if (0 == strncasecmp($server, 'http://', 7))
            $this->server = $server;
        else
            $this->server = 'http://'.$server;
        $this->cmd_join = $this->server.Pipeline::QP_JOIN_SERVICE.
            '?stagename='.$stagename.'&provide='.$provide.'&consume='.$consume;
        if (empty($provide))
            $this->finalStage = TRUE;
        if (empty($consume))
            $this->firstStage = TRUE;
        $this->debug = $debug;

        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_NOSIGNAL, TRUE);
        curl_setopt($this->curl, CURLOPT_FAILONERROR, TRUE);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Connection: keep-alive'));
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, TRUE);
        //curl_setopt($this->curl, CURLOPT_DNS_USE_GLOBAL_CACHE, FALSE);
        //curl_setopt($this->curl, CURLOPT_VERBOSE, $debug);
        //curl_setopt($this->curl, CURLOPT_MAXCONNECTS, 1);
    }/*}}}*/

    /**
     * 阻塞等待直到从pipeline获取package
     *
     * @param package [out] 获取的array(packageid,uri,size,items,checksum)
     * @param delay [in] 无package时重试间隔时间（秒）
     *
     * @return TRUE or FALSE. 失败时,errno/error指示错误信息
     */
    public function get_package(array &$package, $delay = 5)
    {/*{{{*/
        while(1)
        {
            $ret = $this->tryget_package($package);
            switch ($ret)
            {
            case 1:
                return TRUE;
            case 0:  //no package
                sleep($delay);
                continue;
            case -1:
            default:
                return FALSE;
            }
        }
    }/*}}}*/

    /**
     * 尝试从pipeline获取package
     *
     * @param package [out] array(packageid,uri,size,items,checksum) or empty
     *
     * @return 1:ok 0:nopackage -1:false
     */
    public function tryget_package(array &$package)
    {/*{{{*/
        $package = array();
        if ($this->firstStage)
        {
            $this->errno = -2;
            $this->error = 'can not get package. firstStage ' . $this->cmd_join;
            return -1;
        }
        while(1)
        {
            if (!$this->join())
                return -1;
            $ret = $this->do_getpkg($package);
            if (-1 == $ret && -1 == $this->errno)
            {
                //long time before action
                $this->workerid = FALSE;
                continue;
            }
            return $ret;
        }
    }/*}}}*/

    /**
     * 验证package数据是否正确
     *
     * @param package [in/out] array(packageid, size, checksum) to be checked
     *
     * @return TRUE or FALSE. 成功则设置package[items];
     *         失败时,errno/error指示错误信息
     */
    public function check_get(array &$package)
    {/*{{{*/
        while(1)
        {
            if (!$this->join())
                return FALSE;
            $ret = $this->do_chkget($package);
            if (!$ret && -1 == $this->errno)
            {
                //long time before action
                $this->workerid = FALSE;
                continue;
            }
            return $ret;
        }
    }/*}}}*/

    /**
     * 提交一个package
     *
     * @param puttPackage [in/out] 待提交的package, 提交成功后设置package[packageid]
     *
     * @return TRUE or FALSE. 失败时,errno/error指示错误信息
     */
    public function commit_package(array &$package)
    {/*{{{*/
        while(1)
        {
            if (!$this->join())
                return FALSE;
            $ret = $this->do_commit($package);
            if (!$ret && -1 == $this->errno)
            {
                //long time before action
                $this->workerid = FALSE;
                continue;
            }
            return $ret;
        }
    }/*}}}*/

    /**
     * 提交并获取下一个package.
     *
     * @param puttPackage [in/out] 待提交的package, 提交成功后设置package[packageid]
     * @param getPackage [out] 获取的array(packageid,uri,size,items,checksum)
     * @param delay [in] 无package时重试间隔时间（秒）
     *
     * @return TRUE or FALSE. 失败时,errno/error指示错误信息
     * @note 如果是firstStage， getPackage返回空
     */
    public function commit_and_get(array &$putPackage, array &$getPackage, $delay = 5)
    {/*{{{*/
        while(1)
        {
            $ret = $this->commit_and_tryget($putPackage, $getPackage);
            switch ($ret)
            {
            case 1:
                return TRUE;
            case 0: //no package
                sleep($delay);
                return $this->get_package($getPackage, $delay);
            case -1:
            default:
                return FALSE;
            }
        }
    }/*}}}*/

    /**
     * commit package and try get package
     *
     * @return returns 1:ok 0:nopackage -1:false
     */
    public function commit_and_tryget(array &$putPackage, array &$getPackage)
    {/*{{{*/
        $getPackage = array();
        if ($this->finalStage)
        {
            $this->errno = -2;
            $this->error = 'finalStage ' . $this->cmd_join;
            return -1;
        }
        while(1)
        {
            if (!$this->join())
                return -1;
            $ret = $this->do_cmtget($putPackage, $getPackage);
            if (-1 == $ret && -1 == $this->errno)
            {
                //long time before action
                $this->workerid = FALSE;
                continue;
            }
            if ($this->firstStage)
            {
                if (0 == $ret)
                {
                    $ret = 1;
                }
                elseif (1 == $ret)
                {
                    $this->errno = -2;
                    $this->error = 'get package on firstStage ' . $this->cmd_join;
                    $ret = -1;
                }
            }

            return $ret;
        }
    }/*}}}*/

    /**
     * leave from the pipeline
     */
    public function leave()
    {/*{{{*/
        if ($this->workerid)
        {
            $contents = $this->get_contents($this->cmd_leave);
            if ($this->debug) echo $this->cmd_leave, ' -> ', $contents, "<p>\n";
            $this->workerid = FALSE;
        }
    }/*}}}*/

    /**
     * 读取文件内容
     *
     * @param filename [in] 待读取的文件名或url
     * @param timeout [in] Maximum time in seconds when read data
     *
     * @return 返回文件内容或FALSE. 失败时,errno/error指示错误信息
     */
    public function get_contents($filename, $timeout = 60)
    {/*{{{*/
        // get from file server
        if (0 != strncasecmp($filename, 'http://', 7))
        {
            $data = @file_get_contents($filename);
            if (!$data)
            {
                $this->errno = -2;
                $this->error = 'failed to read file ' . $filename;
            }
            return $data;
        }

        // get from http server
        curl_setopt($this->curl, CURLOPT_URL, $filename);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
        $data = curl_exec($this->curl);
        if (!$data)
        {
            $this->errno = curl_errno($this->curl);
            $this->error = curl_error($this->curl);
        }
        return $data;
    }/*}}}*/

    public function __destruct()
    {/*{{{*/
        $this->leave();
        curl_close($this->curl);
    }/*}}}*/

    /**
     * join into qpipeline
     */
    private function join()
    {/*{{{*/
        if ($this->workerid)
            return TRUE;

        $contents = $this->get_contents($this->cmd_join);
        if ($this->debug) echo $this->cmd_join, ' -> ', $contents, "<p>\n";
        if (!$contents) //net error
            return FALSE;
        $data = explode('|', $contents);
        if (3 != sizeof($data)) //client error
        {
            $this->errno = -2;
            $this->error = 'Can not join ' . $this->cmd_join;
            return FALSE;
        }
        if ('OK' != $data[0]) //server error
        {
            $this->errno = intval($data[1]);
            $this->error = $data[2];
            return FALSE;
        }

        $this->workerid = intval($data[2]);
        $this->cmd_leave  = $this->server.Pipeline::QP_LEAVE_SERVICE.'?workerid='.$this->workerid;
        $this->cmd_getpkg = $this->server.Pipeline::QP_GETPKG_SERVICE.'?workerid='.$this->workerid;
        $this->cmd_chkget = $this->server.Pipeline::QP_CHKGET_SERVICE.'?workerid='.$this->workerid;
        $this->cmd_cmtget = $this->server.Pipeline::QP_CMTGET_SERVICE.'?workerid='.$this->workerid;
        $this->cmd_commit = $this->server.Pipeline::QP_COMMIT_SERVICE.'?workerid='.$this->workerid;
        return TRUE;
    }/*}}}*/

    /**
     * try get package
     *
     * @param package [out] getpackage or empty
     *
     * @return returns 1:ok 0:nopackage -1:false
     * @note firstStage allways return no package
     */
    private function do_getpkg(array &$package)
    {/*{{{*/
        $package = array();
        $contents = $this->get_contents($this->cmd_getpkg);
        if ($this->debug) echo $this->cmd_getpkg, ' -> ', $contents, "<p>\n";
        if (!$contents) // net error
            return -1;
        $data = explode('|', $contents);
        switch(sizeof($data))
        {
        case 3:
            if ('OK' == $data[0]) //no package
            {
                return 0;
            }
            else // server error
            {
                $this->errno = intval($data[1]);
                $this->error = $data[2];
                return -1;
            }
        case 4:
            if ('OK' == $data[0])
            {
                $package['packageid'] = intval($data[2]);
                $package['uri'] = $data[3];
                $package['size'] = 0;
                $package['items'] = 0;
                $package['checksum'] = '';
                return 1;
            }
        default:
            // client error
            $this->errno = -2;
            $this->error = 'Bad response for ' . $this->cmd_getpkg;
            return -1;
        }
    }/*}}}*/

    /**
     * check if get package right
     *
     * @return returns TRUE and set package[items] or FALSE on failure
     */
    private function do_chkget(array &$package)
    {/*{{{*/
        if (empty($package))
        {
            $this->errno = -2;
            $this->error = 'check null package';
            return FALSE;
        }
        $url = $this->cmd_chkget.'&packageid='.$package['packageid']
            .'&size='.$package['size'].'&checksum='.$package['checksum'];
        $contents = $this->get_contents($url);
        if ($this->debug) echo $url, ' -> ', $contents, "<p>\n";
        if (!$contents)
            return FALSE;
        $data = explode('|', $contents);
        if (3 != sizeof($data)) //client error
        {
            $this->errno = -2;
            $this->error = 'Bad response for ' . $url;
            return FALSE;
        }
        if ('OK' != $data[0]) //server error
        {
            $this->errno = intval($data[1]);
            $this->error = $data[2];
            return FALSE;
        }
        $package['items'] = intval($data[2]);
        return TRUE;
    }/*}}}*/

    /**
     * commit package
     *
     * @return returns TRUE and set package[packageid] or FALSE on failure
     */
    private function do_commit(array &$putPackage)
    {/*{{{*/
        if (empty($putPackage))
        {
            $this->errno = -2;
            $this->error = 'commit null package';
            return FALSE;
        }
        if ( !isset($putPackage['packageid']) )
            $putPackage['packageid'] = '';
        $url = $this->cmd_commit.'&uri='.$putPackage['uri'].'&packageid='.$putPackage['packageid']
            .'&size='.$putPackage['size'].'&checksum='.$putPackage['checksum'].'&items='.$putPackage['items'];
        $contents = $this->get_contents($url);
        if ($this->debug) echo $url, ' -> ', $contents, "<p>\n";
        if (!$contents)
            return -1;
        $data = explode('|', $contents);
        if (5 != sizeof($data)) //client error
        {
            $this->errno = -2;
            $this->error = 'Bad response for ' . $url;
            return FALSE;
        }
        if ('OK' != $data[0]) //server error
        {
            $this->errno = intval($data[1]);
            $this->error = $data[2];
            return FALSE;
        }
        $putPackage['packageid'] = intval($data[2]);
        return TRUE;
    }/*}}}*/

    /**
     * commit package and try get package
     *
     * @param getPackage [out] getpackage or empty
     *
     * @return returns 1:ok(set putPackage[packageid]) 0:nopackage -1:false
     * @note firstStage allways return no package
     */
    private function do_cmtget(array &$putPackage, array &$getPackage)
    {/*{{{*/
        $getPackage = array();
        if (empty($putPackage))
        {
            $this->errno = -2;
            $this->error = 'commit null package';
            return -1;
        }
        if ( !isset($putPackage['packageid']) )
            $putPackage['packageid'] = '';
        $url = $this->cmd_cmtget.'&uri='.$putPackage['uri'].'&packageid='.$putPackage['packageid']
            .'&size='.$putPackage['size'].'&checksum='.$putPackage['checksum'].'&items='.$putPackage['items'];
        $contents = $this->get_contents($url);
        if ($this->debug) echo $url, ' -> ', $contents, "<p>\n";
        if (!$contents)
            return -1;
        $data = explode('|', $contents);
        switch(sizeof($data))
        {
        case 3:
            // server error
            $this->errno = intval($data[1]);
            $this->error = $data[2];
            return -1;
        case 5:
            if ('OK' == $data[0])
            {
                $putPackage['packageid'] = intval($data[2]);
                $this->errno = intval($data[1]); //-1002 if no new package
                $getPackage['packageid'] = intval($data[3]);
                if (0 == $getPackage['packageid'])
                {
                    $getPackage = array();
                    return 0;
                }
                $getPackage['uri'] = $data[4];
                $getPackage['size'] = 0;
                $getPackage['items'] = 0;
                $getPackage['checksum'] = '';
                return 1;
            }
        default:
            // client error
            $this->errno = -2;
            $this->error = 'Bad response for ' . $url;
            return -1;
        }
    }/*}}}*/

    private $cmd_join, $cmd_leave, $cmd_getpkg, $cmd_chkget, $cmd_cmtget, $cmd_commit;
    private $server = FALSE;
    private $firstStage = FALSE;
    private $finalStage = FALSE;
    private $curl = FALSE;
    private $workerid = FALSE;
    private $debug = FALSE;

    const QP_JOIN_SERVICE   = '/QPipe/appser/qp_join_service.php';
    const QP_LEAVE_SERVICE  = '/QPipe/appser/qp_leave_service.php';
    const QP_GETPKG_SERVICE = '/QPipe/appser/qp_getpkg_service.php';
    const QP_CHKGET_SERVICE = '/QPipe/appser/qp_chkget_service.php';
    const QP_CMTGET_SERVICE = '/QPipe/appser/qp_cmtget_service.php';
    const QP_COMMIT_SERVICE = '/QPipe/appser/qp_commit_service.php';
}

?>
