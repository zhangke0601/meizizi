<?php
interface IStorageMedia
{
    public function batchAdd($tasks);

    public function getAll();

    public function getByType($type);

    public function del($tasks);

    public function retry($tasks);

    public function delAll();

    public function getById($id);
}

class DBStorageMedia implements IStorageMedia
{
    public function __construct($config)
    {/*{{{*/
        assert(isset($config['db_user']));
        assert(isset($config['db_pass']));
        assert(isset($config['db_name']));
        assert(isset($config['db_host']));

        $this->_dbh = new PDO("mysql:host=".$config['db_host'].";dbname=".$config['db_name'], 
            $config['db_user'], $config['db_pass']
        );
        $this->_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->_dbh->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }/*}}}*/

    public function batchAdd($tasks)
    {/*{{{*/
        $stmt = $this->_dbh->prepare("insert into asynctasks (obj, args, status, type, createtime, retry, nowtry) values (?, ?, ?, ?, ?, ?, ?)");
        foreach ($tasks as $task)
        {
            $stmt->bindParam(1, $task->getObjStr());
            $stmt->bindParam(2, $task->getArgsStr());
            $stmt->bindParam(3, $task->getStatus());
            $stmt->bindParam(4, $task->getType());
            $stmt->bindParam(5, $task->getCreateTime());
            $stmt->bindParam(6, $task->getRetry());
            $stmt->bindParam(7, $task->getNowTry());
            $stmt->execute();
        }
    }/*}}}*/

    public function getAll($num=-1, $order='asc')
    {/*{{{*/
        $limit = '';
        if (0 < $num)
        {
            $limit = ' limit 0, '.$num;
        }
        if ('asc' != $order)
        {
            $sql = 'select * from asynctasks '.$limit.' order by id '.$order;
        }
        else
        {
            $sql = 'select * from asynctasks '. $limit;
        }
        $stmt = $this->_dbh->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_NAMED);
    }/*}}}*/

    public function getByType($type, $order='asc')
    {/*{{{*/
        $stmt = $this->_dbh->prepare("select * from asynctasks where type=? order by createtime ".$order);
        $stmt->bindParam(1, $type);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_NAMED);
    }/*}}}*/

    public function getById($id)
    {/*{{{*/
        $stmt = $this->_dbh->prepare("select * from asynctasks where id=?");
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_NAMED);
    }/*}}}*/

    public function del($tasks)
    {/*{{{*/
        $taskIds = '';
        foreach ($tasks as $task)
        {
            $taskIds .= "'".$task->getId()."',";
        }
        $taskIds = substr($taskIds, 0, strlen($taskIds)-1);
        if ('' != $taskIds)
        {
            $this->_dbh->exec("delete from asynctasks where id in($taskIds)");
        }
    }/*}}}*/

    public function retry($tasks)
    {/*{{{*/
        $taskIds = '';
        foreach ($tasks as $task)
        {
            $this->_dbh->exec("update asynctasks set nowtry=nowtry+1 where id='".$task->getId()."'");
        }
    }/*}}}*/

    public function delAll()
    {/*{{{*/
        $this->_dbh->exec("delete from asynctasks");
    }/*}}}*/
}
