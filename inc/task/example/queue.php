<?php
$dir = dirname(__FILE__);
require_once($dir.'/../storagemedia.php');
require_once($dir.'/../storagefactory.php');
require_once($dir.'/../task.php');
require_once($dir.'/../taskmgr.php');
require_once('tester.php');

require_once('/home/ysq/svn/lp/integration/framework/locking_util.php');

//to run the taskMgr:
//
$config['host'] = 'localhost';
$config['user'] = 'root';
$config['pass'] = '';
$config['dbname'] = 'actkey_ysq';
$storageMedia = new DBStorageMedia($config);

//when taskMgr occurs warnings, it will try to send warnings to this emails.
$asyncTaskNotifiers = array('person1@ysq.com', 'person2@ysq.com');

$taskMgr = new TaskMgr($storageMedia, $asyncTaskNotifiers);

//use flock to protect
$lockKey = 'cleanPic';
$lockIns = LockUtil::factory(LockUtil::LOCK_TYPE_FILE);
$lockIns->getLock($lockKey);

//fetch all tasks(or getById($taskId), getByType($type))
$res = $taskMgr->getAll();

$succTasks = $retryTasks = array();
foreach ($res as $task)
{
    $result = $task->run();
    if ($result)
    {
        $succTasks[] = $task;
    }
    else
    {
        $retryTasks[] = $task;
    }
}
$taskMgr->del($succTasks);
$taskMgr->retry($retryTasks);

$lockIns->releaseLock($lockKey);
