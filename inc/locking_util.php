<?php
class LockUtil
{/*{{{*/
    const LOCK_TYPE_DB = 'SQLLock';
    const LOCK_TYPE_FILE = 'FileLock';

    private static $_availableLocks = array('FileLock', 'SQLLock');

    private function __construct(){}

    static public function factory($type, $options=array())
    {
        if (false == in_array($type, self::$_availableLocks))
        {
            throw new Exeception('no this ['.$type.'] locker!');
        }
        return new $type($options);
    }
}/*}}}*/

interface Lockable
{/*{{{*/
    const EXPIRE = 3;
    public function getLock($key, $timeout=self::EXPIRE);
    public function releaseLock($key);
}/*}}}*/


class FileLock implements Lockable
{/*{{{*/
    private $_fp;
    private $_map;
    private $_single;

    public function __construct($options)
    {/*{{{*/
        if (isset($options['path']) && is_dir($options['path']))
        {
            $this->_lockPath = $options['path'].'/';
        }
        else
        {
            $this->_lockPath = '/tmp/';
        }
        //是否独占只起一个实例
        $this->_single = isset($options['single'])?$options['single']:false;
    }/*}}}*/

    public function getLock($key, $timeout=self::EXPIRE)
    {/*{{{*/
        if (false == isset($this->_map[$key]))
        {
            $file = md5($key);
            $fp = fopen($this->_lockPath.$file.'.lock', "a+w");
            if ($this->_single)
            {
                $op = LOCK_EX + LOCK_NB;
            }
            else
            {
                $op = LOCK_EX;
            }
            if (flock($fp, $op))
            {
                $this->_map[$key] = $fp;
            }
            else
            {
                return false;
            }
        }
        return true;
    }/*}}}*/

    public function releaseLock($key)
    {/*{{{*/
        if (isset($this->_map[$key]))
        {
            $fp = $this->_map[$key];
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }/*}}}*/
}/*}}}*/

class SQLLock implements Lockable
{/*{{{*/
    public function __construct($options)
    {/*{{{*/
        if ( (false == isset($options['db'])) || (false == $options['db'] instanceof DBExecuter) )
        {
            throw new Exception('must specify a dbdriver in the options! $options[\'db\']!');
        }
        $this->_driver = $options['db'];
    }/*}}}*/

    public function getLock($key, $timeout=self::EXPIRE)
    {/*{{{*/
        $sql = "SELECT GET_LOCK('".$key."', '".$timeout."')";
        return $this->_driver->execute($sql);
    }/*}}}*/

    public function releaseLock($key)
    {/*{{{*/
        $sql = "SELECT RELEASE_LOCK('".$key."')";
        return $this->_driver->execute($sql);
    }/*}}}*/
}/*}}}*/
