<?php
class TaskDelegate
{
    private $_taskMgr;

    public function __construct($config)
    {
        $storageMedia = new DBStorageMedia($config);
        $this->_taskMgr = new TaskMgr($storageMedia);
    }

    public function __call($method, $args)
    {
        return $this->_taskMgr->{$method}(array_pop($args));
    }
}
class TaskMgr
{
    private $_storage;
    private $_notifiers = array();

    public function __construct(IStorageMedia $storage)
    {
        $this->_storage = $storage;
    }

    public function __destruct()
    {
        $this->_storage = null;
    }

    public function addNotifiers($notifiers)
    {/*{{{*/
        $this->_notifiers = $notifiers;
    }/*}}}*/

    public function batchAdd($tasks)
    {/*{{{*/
        return $this->_storage->batchAdd($tasks);
    }/*}}}*/

    public function del($tasks)
    {/*{{{*/
        if (0 < count($tasks))
        {
            return $this->_storage->del($tasks);
        }
        return true;
    }/*}}}*/

    public function retry($tasks)
    {/*{{{*/
        if (0 < count($tasks))
        {
            foreach ($tasks as $task)
            {
                if ($task->getNowTry() > $task->getRetry())
                {
                    $this->sendWarning($task);
                }
            }
            return $this->_storage->retry($tasks);
        }
        return true;
    }/*}}}*/

    public function clearAll()
    {/*{{{*/
        return $this->_storage->delAll();
    }/*}}}*/

    public function getAll($num=-1)
    {/*{{{*/
        $res = $this->_storage->getAll($num);
        return $this->convertTaskObj($res);
    }/*}}}*/

    private function convertTaskObj($res)
    {/*{{{*/
        $objs = array();
        foreach ($res as $tmp)
        {
            $objs[] = Task::create($tmp['id'], $tmp['obj'], $tmp['args'], $tmp['createtime'], $tmp['status'], $tmp['type'], $tmp['retry'], $tmp['nowtry']);
        }
        return $objs;
    }/*}}}*/

    public function getByType($type)
    {/*{{{*/
        $res = $this->_storage->getByType($type);
        return $this->convertTaskObj($res);
    }/*}}}*/

    public function getById($taskId)
    {/*{{{*/
        $res = $this->_storage->getById($taskId);
        $tmp = $this->convertTaskObj(array($res));
        return $tmp[0];
    }/*}}}*/
    
    private function sendWarning(Task $task)
    {/*{{{*/
        foreach ($this->_notifiers as $notifier)
        {
            mail($notifier, 'async task alert!', 'async task try id:'.$task->getId().'['.$task->getRetry().'/'.$task->getNowTry().'](retry/nowtry) wrong times retry!');
        }
    }/*}}}*/
}
