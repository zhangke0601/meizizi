<?php
class DBDelegateException extends Exception
{}

interface DBExecuter
{
    public function execute($sql, $values=array(), $dbIndex=0, $dbConf='');
    public function executeStat($sql, $values=array(), $dbIndex=0, $dbConf='');
    public function executeByHuman($sql, $values=array(), $dbIndex=0, $dbConf='');
    public function beginTrans($dbIndex=0);
    public function commit();
    public function rollback();
    public function getLastInsertId();
}

class DBDelegate implements DBExecuter
{
    private $_ins;
    private $_dbhs = array();
    private $_readDbConf = array();
    private $_writeDbConf = array();
    private $_statDbConf = array();

    public function getInstance()
    {/*{{{*/
        static $ins;
        if (false == $ins instanceof self)
        {
            $ins = new self();
        }
        return $ins;
    }/*}}}*/

    public function setup($config)
    {/*{{{*/
        $readDb = isset($config['readDb'])?$config['readDb']:'';
        $writeDb = isset($config['writeDb'])?$config['writeDb']:'';
        $statDb = isset($config['statDb'])?$config['statDb']:'';

        if (empty($readDb))
        {
            throw new DBDelegateException('no db config');
        }
        if (empty($writeDb))
        {
            $writeDb = &$readDb;
        }
        if (empty($statDb))
        {
            $statDb = &$readDb;
        }
        $this->_readDbConf = $readDb;
        $this->_writeDbConf = $writeDb;
        $this->_statDbConf = $statDb;
    }/*}}}*/

    private function __construct()
    {/*{{{*/
    }/*}}}*/
    
    public static function getServiceName()
	{/*{{{*/
		return 'DBDelegate';
	}/*}}}*/

    public function __destruct()
    {/*{{{*/
        $this->_ins = '';
        $this->_dbhs = array();
    }/*}}}*/

    public function close()
    {/*{{{*/
        $this->_ins = '';
        $this->_dbhs = array();
    }/*}}}*/

    public function executeStat($sql, $values=array(), $dbIndex=0, $dbConf='')
    {/*{{{*/
        $sql =  trim($sql);
        $keyword = strtolower(trim(substr($sql, 0, 7)));

        $statDbConf = $this->getDbConf($dbConf, $dbIndex, 'stat');
        return $this->getDbh($statDbConf)->executeRead($sql, $values);
    }/*}}}*/

    public function execute($sql, $values=array(), $dbIndex=0, $dbConf='')
    {/*{{{*/
        $sql =  trim($sql);
        $keyword = strtolower(trim(substr($sql, 0, 7)));
        if (self::checkReadSql($keyword))
        {
            $readDbConf = $this->getDbConf($dbConf, $dbIndex, 'read');
            return $this->getDbh($readDbConf)->executeRead($sql, $values);
        }
        else if (self::checkWriteSql($keyword))
        {
            $writeDbConf = $this->getDbConf($dbConf, $dbIndex, 'write');
            return $this->getDbh($writeDbConf)->executeWrite($sql, $values);
        }
        else
        {
            throw new DBDelegateException('wrong sql');
        }
    }/*}}}*/

    public function executeByHuman($sql, $values=array(), $dbIndex=0, $dbConf='')
    {/*{{{*/
        $sql = trim($sql);
        if ('' != $dbConf)
        {
            $writeDbConf = $dbConf;
        }
        else
        {
            $writeDbConf = $this->_writeDbConf;
        }
        return $this->getDbh($writeDbConf)->executeByHuman($sql, $values);
    }/*}}}*/

    public function executeByHuman1($sql, $values=array(), $dbIndex=0, $dbConf='')
    {/*{{{*/
        $sql = trim($sql);
        if ('' != $dbConf)
        {
            $writeDbConf = $dbConf;
        }
        else
        {
            $writeDbConf = $this->_writeDbConf;
        }
        return $this->getDbh($writeDbConf)->executeRead($sql, $values);
    }/*}}}*/

    private function getDbh($dbConf)
	{/*{{{*/
        $key = $this->genDbhKey($dbConf);
        if (isset($this->_dbhs[$key]))
        {
            if ($this->_dbhs[$key]->hasConnection())
            {
                return $this->_dbhs[$key];
            }
        }

        $dbh = new PDODriver($dbConf['db_host'], $dbConf['db_name'], $dbConf['db_user'], $dbConf['db_pass']);
        $this->addDbh($dbConf, $dbh);

        return $dbh;
	}/*}}}*/

    private function genDbhKey($dbConf)
    {/*{{{*/
        return md5(serialize($dbConf));
    }/*}}}*/

    private function addDbh($dbConf, $dbh)
    {/*{{{*/
        $key = $this->genDbhKey($dbConf);
        $this->_dbhs[$key] = $dbh;
    }/*}}}*/

    public function beginTrans($dbIndex=0)
    {/*{{{*/
        $this->_ins = $this->getDbh($this->getDbConf('', $dbIndex, 'write'));
        $this->_ins->beginTrans();
    }/*}}}*/

    public function commit()
    {/*{{{*/
        $this->_ins->commit();
    }/*}}}*/

    public function rollback()
    {/*{{{*/
        $this->_ins->rollback();
    }/*}}}*/

    public function getLastInsertId()
    {/*{{{*/
        return $this->getDbh($this->getDbConf('', 0, 'write'))->getLastInsertId();
    }/*}}}*/

    public function executeONLYForTest($sql)
    {/*{{{*/
        return $this->getDbh($this->getDbConf('', 0, 'write'))->executeWrite($sql);
    }/*}}}*/

    private function checkReadSql($sqlMainWord)
	{/*{{{*/
		return ('select' == $sqlMainWord);
	}/*}}}*/
	
	private function checkWriteSql($sqlMainWord)
	{/*{{{*/
		return ( ('insert' == $sqlMainWord) || ('update' == $sqlMainWord) 
		        || ('delete' == $sqlMainWord) || ('replace' == $sqlMainWord) );
	}/*}}}*/

    private function getDbConf($dbConf, $index=0, $type='read')
    {/*{{{*/
        if ('' == $dbConf)
        {
            if ('read' == $type)
            {
                $dbConf = $this->_readDbConf[$index];
            }
            else if ('write' == $type)
            {
                $dbConf = $this->_writeDbConf[$index];
            }
        }
        return $dbConf;
    }/*}}}*/
}
