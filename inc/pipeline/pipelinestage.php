<?php

require_once('pipeline.php');

class PipelineStage
{
    /**
     * 构造
     *
     * @param server [in] pipeline server地址
     * @param stagename [in] stage名称
     * @param provide [in] 生成的package名称
     * @param consume [in] 获取的package名称
     * @param debug [in] 设置为TRUE则显示调试信息
     */
    public function __construct($server, $stagename = '', $provide = '', $consume = '', $debug = FALSE)
    {
        $stagename = trim($stagename);
        $provide = trim($provide);
        $consume = trim($consume);
        if (empty($provide))
            $this->finalStage = TRUE;
        if (empty($consume))
            $this->firstStage = TRUE;
        if (!$this->finalStage)
        {
            $this->outpath = $this->outpath . $stagename . '/';
            $this->pkgname = $provide;
        }
        $hostname = strtolower(php_uname('n'));
        $hostname = explode(".", $hostname);
        $this->hostname = $hostname[0];
        $this->mypid = getmypid();
        $this->worker = new Pipeline($server, $stagename, $provide, $consume, $debug);
        $this->debug = $debug;
    }

    /**
     * put一个item
     *
     * @param buf [in] item信息
     * @param packageid [out] packageid or 0
     *
     * @return TRUE or FALSE
     */
    public function put($buf, &$packageid)
    {
        if ($this->finalStage)
            return FALSE;
        if (!$this->firstStage && empty($this->inputPkg))
            return FALSE;
        $ret = $this->savebuf($buf);
        if (!$ret)
            return FALSE;

        $outputPkg = array();
        if ($this->rollfile())
        {
            if (!$this->firstStage)
                $outputPkg['packageid'] = $this->inputPkg['packageid'];
            $outputPkg['uri'] = $this->httpserver . $this->filename;
            $outputPkg['items'] = $this->fileitems;
            $outputPkg['size'] = $this->filesize;
            $outputPkg['checksum'] = $this->filesize;
            $ret = $this->worker->commit_package($outputPkg);
            ++$this->countcommit;
            if (!$ret)
                return FALSE;
            if (!$this->firstStage)
            {   //package处理完成, 删除共享数据文件
                if (0 != strncasecmp($this->inputPkg['uri'], 'http://', 7)) //package save in file server
                {
                    unlink($this->inputPkg['uri']);
                    if ($this->debug) echo $this->inputPkg['uri'], " unlink.<p>\n";
                }
                $this->inputPkg = array();
            }
        }

        if ( !isset($outputPkg['packageid']) )
            $packageid = 0;
        else
            $packageid = $outputPkg['packageid'];
        return TRUE;
    }

    /**
     * 阻塞等待直到获取一个package
     *
     * @param buf [out] package内容
     * @param items [out] package包含的items
     * @param packageid [out] packageid
     *
     * @return TRUE or FALSE
     */
    public function get(&$buf, &$items, &$packageid)
    {
        $ret = $this->worker->get_package($this->inputPkg, $this->delay);
        if (!$ret)
            return FALSE;
        return $this->getdata($buf, $items, $packageid);
    }

    /**
     * 尝试获取一个package
     *
     * @param buf [out] package内容
     * @param items [out] package包含的items
     * @param packageid [out] packageid
     *
     * @return 1:ok 0:nopackage -1:false
     */
    public function tryget(&$buf, &$items, &$packageid)
    {
        $ret = $this->worker->tryget_package($this->inputPkg);
        if ($ret <= 0)
            return $ret;
        if ($this->getdata($buf, $items, $packageid))
            return 1;
        else
            return -1;
    }

    /**
     * 设置put保存文件的绝对路径, 和本地httpserver的url(空串表示使用共享存储)
     */
    public function setOutpath($path, $httpserver = '')
    {
        $this->outpath = str_replace('\\', '/', trim($path));
        if (substr($this->outpath, -1) != '/')
                $this->outpath .= '/';
        $this->httpserver = trim($httpserver);
    }

    /**
     * 设置切文件(put保存文件)的条件
     */
    public function setRollfile($maxitems, $maxtime = 0, $maxsize = 0)
    {
        if (0 == $maxitems)
            $maxitems = 1;
        if(0 == $maxtime)
            $maxtime = 1800;
        if(0 == $maxsize)
            $maxsize = 100000000;
        $this->maxitems = min(abs($maxitems), 10000);
        $this->maxtime = min(abs($maxtime), 1800);
        $this->maxsize = min(abs(maxsize), 100000000);
    }

    /**
     * 设置get()没有可用package时重试间隔时间（秒）
     */
    public function setGetpackageDelay($delay)
    {
        if (0 == $delay)
            $delay = 5;
        $this->delay = min(abs($delay), 300);
    }

    /**
     * leave
     */
    public function leave()
    {
        $this->worker->leave();
    }

    private function getdata(&$buf, &$items, &$packageid)
    {
        $uri = $this->inputPkg['uri'];
        $buf = $this->worker->get_contents($uri);
        //if (!$buf)
        //{
            //忽略此package，等待下次调度
            //$this->inputPkg = array();
            //return FALSE;
        //}
        if ($this->debug) echo $uri, ' -> get_contents(', strlen($buf), ")<p>\n";
        $this->inputPkg['size'] = strlen($buf);
        $this->inputPkg['checksum'] = strlen($buf);
        $ret = $this->worker->check_get($this->inputPkg);
        if (!$ret)
        {
            //忽略此package，等待下次调度
            $this->inputPkg = array();
            return FALSE;
        }
        $items = $this->inputPkg['items'];
        $packageid = $this->inputPkg['packageid'];
        if ($this->finalStage)
        {   //package处理完成, 删除共享数据文件
            if (0 != strncasecmp($uri, 'http://', 7)) //package save in file server
            {
                unlink($uri);
                if ($this->debug) echo $uri, " unlink.<p>\n";
            }
            $this->inputPkg = array();
            return TRUE;
        }
        else
        {   //package信息向下传递, put切文件条件为items==inputPkg['items']
            $this->maxitems = $this->inputPkg['items'];
            $this->maxtime = 0;
            $this->maxsize = 0;
            return TRUE;
        }
    }

    /**
     * save buf to this->filename, and count fileitems/filesize
     * @return bool
     */
    private function savebuf($buf)
    {
        if (!$this->fp)
        {
            if (!is_dir($this->outpath))
                if (!mkdir($this->outpath, 0766, true))
                    return FALSE;
            $this->filename = sprintf("%s%s_%s%s_%s_%s.xml", $this->outpath, $this->pkgname,
                                      $this->hostname, $this->mypid,
                                      $this->countcommit, date("mdHis", time()));
            $this->fp = @fopen($this->filename, 'a');
            if (!$this->fp)
                return FALSE;
            $this->fileitems = 0;
            $this->filesize = 0;
            $this->rolltime = time() + $this->maxtime;
        }
        if (!fwrite($this->fp, $buf))
        {
            fclose($this->fp);
            $this->fp = FALSE;
            return FALSE;
        }
        ++$this->fileitems;
        $this->filesize += strlen($buf);
        return TRUE;
    }

    /**
     * check if need roll file
     * @return bool
     */
    private function rollfile()
    {
        if ($this->fileitems >= $this->maxitems)
        {
            fclose($this->fp);
            $this->fp = FALSE;
            return TRUE;
        }
        if ($this->maxtime != 0 && time() >= $this->rolltime)
        {
            fclose($this->fp);
            $this->fp = FALSE;
            return TRUE;
        }
        if ($this->maxsize !=0 && $this->filesize >= $this->maxsize)
        {
            fclose($this->fp);
            $this->fp = FALSE;
            return TRUE;
        }
        return FALSE;
    }

    private $worker, $pkgname;
    private $fp = FALSE;
    private $outpath = '/home/s/apps/pipeline/';
    private $httpserver = '';
    private $delay = 5;
    private $inputPkg = array();
    private $firstStage = FALSE;
    private $finalStage = FALSE;
    private $hostname = '';
    private $mypid = 0;
    private $countcommit = 0;
    private $debug = FALSE;

    private $maxitems = 1;
    private $maxtime = 1800;
    private $maxsize = 100000000;
    private $filename, $fileitems, $filesize, $rolltime;
}

/*
$worker = new PipelineStage('pipe.white.qihoo.net:8360', 'stage_urldispatcher', 'package_url', '', TRUE);
$worker = new PipelineStage('pipe.white.qihoo.net:8360', 'test_urldispatcher', 'testpkg_url', '', TRUE);
//$worker->setOutpath('\\home\\s\test_urldispatcher', 'http://localhost:8360');
for ($i = 0; $i < 2; ++$i)
{
    $packageid = 0;
    $ret = $worker->put("test....\n", $packageid);
    echo 'put ', $packageid, ' ', $ret, "\n";
}
exit;


$worker = new PipelineStage('pipe.white.qihoo.net:8360', 'test_downloader', 'testpkg_software', 'testpkg_url', TRUE);
$worker = new PipelineStage('pipe.white.qihoo.net:8360', 'test_unpacker_ue', 'testpkg_files_ue', 'testpkg_software', TRUE);
$worker = new PipelineStage('pipe.white.qihoo.net:8360', 'test_unpacker_II', 'testpkg_files_II', 'testpkg_files_ue', TRUE);
$worker = new PipelineStage('pipe.white.qihoo.net:8360', 'test_storage', '', 'testpkg_files_II', TRUE);

//$worker = new PipelineStage('pipe.white.qihoo.net:8360', 'stage_downloader', 'package_software', 'package_url', TRUE);
//$worker = new PipelineStage('pipe.white.qihoo.net:8360', 'stage_unpacker_ue', 'package_files_ue', 'package_software', TRUE);
//$worker = new PipelineStage('pipe.white.qihoo.net:8360', 'stage_unpacker_II', 'package_files_II', 'package_files_ue', TRUE);
//$worker = new PipelineStage('pipe.white.qihoo.net:8360', 'stage_storage', '', 'package_files_II', TRUE);

$items = 0;
$packageid = 0;
while(1)
{
    $buf = '';
//  $ret = $worker->tryget($buf, $items, $packageid);
//  if ($ret <= 0)
//      break;
    $ret = $worker->get($buf, $items, $packageid);
    if (!$ret)
        continue;

    for($i = 0; $i < $items; ++$i)
    {
        $worker->put($buf, $packageid);
    }
}

*/
?>
