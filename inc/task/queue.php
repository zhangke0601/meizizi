<?php
ini_set('max_execution_time', 1000000000);

$now = dirname(__FILE__).'/';
$baseDir = $now.'../../../inc/';
require_once($baseDir.'config_loader.php');
require_once($baseDir.'locking_util.php');
require_once($baseDir.'utils.php');
require_once($baseDir.'task/task.php');
require_once($baseDir.'task/storagemedia.php');
require_once($baseDir.'task/taskmgr.php');
require_once($now.'../src/callbackfs.php');

//use flock to protect
$lockKey = 'callbackFillPmd5ByUmd5';
$options = array('single' => true);
$lockIns = LockUtil::factory(LockUtil::LOCK_TYPE_FILE, $options);
if (false == $lockIns->getLock($lockKey))
{
    echo 'i am still running.';
    exit;
}

$config = ConfigLoader::getInstance();
$config->setConfigFile($now.'../config/upload_pkg/config.php');
$dbc = $config->get('taskDb');

$asyncTaskNotifiers = $config->get('taskNotifiers');
$logPath = $config->get('taskLogPath');

RequestDelegate::setLogPath($logPath);

$taskMgr = new TaskDelegate($dbc['readDb']);
$taskMgr->addNotifiers($asyncTaskNotifiers);

//fetch all tasks(or getById($taskId), getByType($type))
$res = $taskMgr->getAll(50);

$succTasks = $retryTasks = array();
foreach ($res as $task)
{
    try
    {
        error_log(print_r($task, 2), 3, $logPath);
        $result = $task->run();
        //if ($result || (false == $task->isNeedRetry()))
        if ($result)
        {
            $succTasks[] = $task;
        }
        else
        {
            $retryTasks[] = $task;
        }
    }
    catch (Exception $e)
    {
        error_log(print_r($e, 2), 3, $logPath);
        $retryTasks[] = $task;
    }
}
$taskMgr->del($succTasks);
$taskMgr->retry($retryTasks);

$lockIns->releaseLock($lockKey);
$log = $taskMgr = null;
