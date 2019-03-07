<?php
abstract class QueryTpl
{
	private $_mc;
    private $_baseDir;
	private $_config;
    private $_useMultTables;
    private $_skipCache;
    
    abstract protected function getConfigFile();
    abstract protected function checkData();
    abstract protected function prepareData(&$data);
    abstract protected function workOnLeftData(&$freshData, &$reload, $data);
    abstract protected function genCacheKey($str);
    abstract protected function getColumn();
    abstract protected function getTableName();
    abstract protected function getType();

	public function __construct($useMultTables, $skipCache=false)
	{/*{{{*/
        $this->_useMultTables = $useMultTables;
        $this->_skipCache = $skipCache;
        $this->_baseDir = dirname(__FILE__).'/../';

		require('config_loader.php');
		require('safe_memcache_driver.php');

		$this->_config = ConfigLoader::getInstance();
		$this->_config->setConfigFile($this->getConfigFile());

		$memServers = $this->_config->get('memcache');
		$this->_mc = SafeMemCacheDriver::getInstance($memServers);
  	}/*}}}*/

    protected function iskipCache(&$data)
    {
        return true;
    }
    
    public function run()
	{/*{{{*/
        try
        {
            $data = $this->checkData();
            $prepareData = $this->prepareData($data);
            $left = $this->getNotInCache($prepareData);
            $prepareData = null;
            $freshData = array();
            //var_dump($left);
            $notInDb = $this->findNotInDbByMd5s(array_keys($left));
            //var_dump($notInDb);
            foreach ($notInDb as $needSaveKey)
            {
                $freshData[] = $left[$needSaveKey];
                unset($left[$needSaveKey]);
            }
            //freshData: 新增数据
            //left: db中有, cache中没有的数据
            $this->workOnLeftData($freshData, $left, $data);

        }
        catch(Exception $e)
        {
            $type = $this->getType();
            $msg = $e->getMessage()."\n".$e->getTraceAsString();
            $this->log("[".$type."] err occurs\n".$msg);
            self::showMsg($msg);
            //self::showMsg('err occurs!');
        }
    }/*}}}*/

    protected function getCacheHandler()
    {/*{{{*/
        return $this->_mc;
    }/*}}}*/

    protected function getConfigHandler()
    {/*{{{*/
        return $this->_config;
    }/*}}}*/

    protected function log($msg, $path='')
    {/*{{{*/ 
        if ('' == $path)
        {
            $path = $this->_config->get('md5ErrorLog');
            $msg = $msg."\n";
        }
        error_log($msg, 3, $path);
    }/*}}}*/
    
    protected function setCache($res)
    {/*{{{*/
        $expire = $this->_config->get('expire');
        foreach ($res as $md5)
        {
            $this->_mc->set($this->genCacheKey($md5), $md5, $expire);
        }
    }/*}}}*/
    
    protected function getDb()
    {/*{{{*/
        require_once($this->_baseDir.'inc/db/dbdelegate.php');
        require_once($this->_baseDir.'inc/db/pdodriver.php');

        static $dbd;
        if (false == $dbd instanceof DBDelegate)
        {
            $dbc = $this->_config->get('db');
            $dbd = DBDelegate::getInstance();
            $dbd->setup($dbc);
        } 
        return $dbd;
    }/*}}}*/

    private function getNotInCache(&$arr)
    {/*{{{*/
        $md5s = $skips = $dict = $notInCache = array();
        $date = date("Ymd", time());
        foreach($arr as $single)
        {
            if (false == $this->iskipCache($single))
            {
                $dict[$this->genCacheKey($single['md5'])] = $single['md5'];
                $md5s[$single['md5']] = $single;
            }
            else
            {
                $skips[$single['md5']] = $single;
            }
            $type = $this->getType();
        }
        if (false == empty($dict))
        {
            $allMd5Keys = array_keys($dict);
            $md5sInCache = $this->_mc->get($allMd5Keys);
            $md5sInCacheKeys = array_keys($md5sInCache);

            $lefts = array_diff($allMd5Keys, $md5sInCacheKeys);
            //var_dump($allMd5Keys, $md5sInCacheKeys, $lefts); exit;
            foreach ($lefts as $left)
            {
                $dictKey = $dict[$left];
                $notInCache[$dictKey] = &$md5s[$dictKey];
            }
        }
        //echo '<br>not in cache:'; var_dump($notInCache, $skips);
        //var_dump(array_merge($notInCache, $skips));
        //exit;
        return array_merge($notInCache, $skips);
    }/*}}}*/

    private function prepareForMult(&$allMd5s)
    {/*{{{*/
        $eachTableNos = array();
        foreach ($allMd5s as $md5)
        {
            $dbIndex = $this->getDbIndexByMd5($md5);
            $tableNo = $this->getTableNo($md5);
            if (false == isset($eachTableNos[$dbIndex]))
            {
                $eachTableNos[$dbIndex] = array();
            }
            if (false == isset($eachTableNos[$dbIndex][$tableNo]))
            {
                $eachTableNos[$dbIndex][$tableNo] = array();
            }
            $eachTableNos[$dbIndex][$tableNo][] = $md5;
        }
        return $eachTableNos;
    }/*}}}*/

    private function execute($sql)
    {/*{{{*/
        if (is_array($sql))
        {
            $res = array();
            foreach ($sql as $dbIndex=>$single)
            {
                $tmpRes[] = $this->getDb()->execute($single, array(), $dbIndex);
            }
            foreach ($tmpRes as $tmp)
            {
                foreach ($tmp as $set)
                {
                    $res[] = $set;
                }
            }
        }
        else
        {
            $res = $this->getDb()->execute($sql);
        }
        return $res;
    }/*}}}*/

    private function findNotInDbByMd5s(&$allMd5s)
    {/*{{{*/
        if (false == empty($allMd5s))
        {
            $column = $this->getColumn();
            $tableName = $this->getTableName();
            if ($this->_useMultTables)
            {
                $eachNos = $this->prepareForMult($allMd5s);
                $sql = array();
                foreach ($eachNos as $dbIndex=>$tmp)
                {
                    $tmpSql = '';
                    foreach ($tmp as $tableNo => $md5s)
                    {
                        $str = '"'.implode('","', $md5s).'"';
                        $tmpSql .= ' SELECT '.$column.' FROM '.$tableName.$tableNo.' WHERE md5 in ('.$str.') union';
                    }
                    $sql[$dbIndex] = substr($tmpSql, 0, strlen($tmpSql)-5);
                }
            }
            else
            {
                $str = '"'.implode('","', $allMd5s).'"';
                $sql = ' SELECT '.$column.' FROM '.$tableName.' WHERE md5 in ('.$str.')';
            }
            //var_dump($sql); exit;
            $res = $this->execute($sql);            

            $toCache = array();
            foreach ($res as $info)
            {
                $toCache[] = $info['md5'];
            }
            $this->setCache($toCache);
            return array_diff($allMd5s, $toCache);
        }
        return $allMd5s;
    } /*}}}*/
     
    protected function getTableNo($md5) 
    {/*{{{*/
        require_once($this->_baseDir.'inc/db/storagerule.php');
        $tableNo = $this->_config->get('tableNo'); 
        return StorageRule::getStringHash($md5, $tableNo);
    }/*}}}*/

    protected function getPkgUrlTableNo($md5) 
    {/*{{{*/
        require_once($this->_baseDir.'inc/db/storagerule.php');
        $tableNo = $this->_config->get('tableNo'); 
        return StorageRule::getStringHash($md5, $tableNo);
    }/*}}}*/

    protected function getDbConfByMd5($md5) 
    {/*{{{*/
        require_once($this->_baseDir.'inc/db/storagerule.php');
        $dbs = $this->_config->get('db'); 
        $dbno = count($dbs);
        //兼容老库
        if (1 == $dbno)
        {
            return $dbs;
        }
        else
        {
            $dbIndex = StorageRule::getStringHash($md5, $dbno);
            return $dbs[$dbIndex];
        }
    }/*}}}*/

    protected function getDbIndexByMd5($md5) 
    {/*{{{*/
        require_once($this->_baseDir.'inc/db/storagerule.php');
        $dbno = $this->_config->get('dbNo'); 
        return StorageRule::getStringHash($md5, $dbno);
    }/*}}}*/

    protected function getPkgByMd5($md5)
    {/*{{{*/
        $tableNo = $this->getTableNo($md5);
        $dbIndex = $this->getDbIndexByMd5($md5);
        $sql = 'SELECT md5, filename, download_addr FROM wl_package_'.$tableNo.' WHERE md5 = ?';
        $values = array($md5);
        $res = $this->getDb()->execute($sql, $values, $dbIndex);
        return $res;
    }/*}}}*/

    protected function getPkgUrlDbIndexByMd5($md5) 
    {/*{{{*/
        require_once($this->_baseDir.'inc/db/storagerule.php');
        $dbno = $this->_config->get('pkgUrlDbNo'); 
        return StorageRule::getStringHash($md5, $dbno);
    }/*}}}*/

    protected function getDbConfByIndex($index) 
    {/*{{{*/
        require_once($this->_baseDir.'inc/db/storagerule.php');
        $dbs = $this->_config->get('db'); 
        return $dbs[$index];
    }/*}}}*/
  
    static protected function showMsg($msg='')
    {/*{{{*/
        echo $msg;
        exit;
    }/*}}}*/

    protected function sendMail($title, $msg)
    {/*{{{*/
        $mails = $this->_config->get('mails');
        if ('' != $mails)
        {
            mail($mails, $title, $msg);
        }
    }/*}}}*/
}
