<?php
$dir = dirname(__FILE__);
require_once($dir.'/../storagemedia.php');
require_once($dir.'/../storagefactory.php');
require_once($dir.'/../task.php');
require_once($dir.'/../taskmgr.php');
require_once('tester.php');


//to register events to taskMgr:
//$obj is the Object that you want to run the tasks by $obj->method($arg)
//Task::register($obj, $method, $args, $type, $retryCnt=10)
//
$obj = new Test();
$args = time();

$task = Task::register($obj, $args, 1);

$config['host'] = 'localhost';
$config['user'] = 'root';
$config['pass'] = '';
$config['dbname'] = 'actkey_ysq';
$storageMedia = new DBStorageMedia($config);

//when taskMgr occurs warnings, it will try to send warnings to this emails.
$asyncTaskNotifiers = array('person1@ysq.com', 'person2@ysq.com');

$taskMgr = new TaskMgr($storageMedia, $asyncTaskNotifiers);
$taskMgr->batchAdd(array($task));
