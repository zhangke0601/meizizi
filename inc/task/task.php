<?php
interface Callable
{
    public function run($args);
}
class Task
{
    private $_id;
    private $_obj;
    private $_args;
    private $_createtime;
    private $_status;
    private $_type;
    private $_retry;
    private $_nowTry;

    const INIT = 1;
    const DONE = 2;
    const FAILED = 3;

    const DEFAULT_RETRY_NUM = 10;

    private function __construct($id, $obj, $args, $createTime, $status, $type, $retry, $nowTry)
    {
        $this->_id = $id;
        $this->_obj = $obj;
        $this->_args = $args;
        $this->_createTime = $createTime;
        $this->_status = $status;
        $this->_type = $type;
        $this->_retry = $retry;
        $this->_nowTry = $nowTry;
    }

    static public function register(Callable $obj, $args, $type, $retry=self::DEFAULT_RETRY_NUM)
    {
        return new self(-1, $obj, $args, 
            date('Y-m-d H:i:s'), self::INIT, $type, $retry, 0);
    }

    static public function create($id, $obj, $args, $createtime, $status, $type, $retry, $nowTry)
    {
        return new self($id, $obj, $args, $createtime, $status, $type, $retry, $nowTry);
    }

    public function toString()
    {
        return base64_encode(serialize($this));
    }

    public function getId()
    {
        return $this->_id;
    }

    public function getObj()
    {
        return $this->_obj;
    }

    public function getObjStr()
    {
        if ('' == $this->_obj)
        {
            return '';
        }
        return base64_encode(serialize($this->_obj));
    }

    public function run()
    {
        $obj = unserialize(base64_decode($this->_obj));
        if (is_object($obj))
        {
            return ($obj->run($this->getArgs()));
        }
        //skip
        return true;
    }

    public function getArgs()
    {
        return unserialize(base64_decode($this->_args));
    }
    
    public function getArgsStr()
    {
        return base64_encode(serialize($this->_args));
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function getCreateTime()
    {
        return $this->_createTime;
    }

    public function getRetry()
    {
        return $this->_retry;
    }

    public function getNowTry()
    {
        return $this->_nowTry;
    }

    public function isNeedRetry()
    {
        return $this->_nowTry <= $this->_retry;
    }

    static public function recover($str)
    {
        return base64_decode(unserialize($str));
    }
}
