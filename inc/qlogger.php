#!/usr/bin/env php
<?php
/*
 * 用途：提供给shell调用的qlog接口
 * 作者：fengerbo@360.cn
 * 时间：2012-09-11
 */

$ret = 0;
$tmpfile = '';

if($argc == 4)
{
  $logger = trim($argv[1]);
  $level  = strtoupper(trim($argv[2]));
  $log    = trim($argv[3]);

  $logpath = get_qlog_cfg($logger);
  
  if(strlen($logpath) > 0)
  {
    qLogConfig($logpath);
    switch($level)
    {
      case 'INFO':
        qLogInfo($logger, $log);
        break;
      case 'DEBUG':
        qLogDebug($logger, $log);
        break;
      default:
        qLogInfo($logger, $log);
        break;
    }
  }
  else
  {
    $ret = 2;
  }
}
else
{
    echo "Usage: $argv[0] <MODULENAME|alarm.NAME> <INFO|DEBUG> CONTENT\n";
    $ret = 1;
}

if(file_exists($tmpfile))
    @unlink($tmpfile);

exit($ret);


function get_qlog_cfg($logger)
{
  $filepath = get_qlog_conf_path($logger);  
  if(!file_exists($filepath) || filesize($filepath) < 1 )
  {
    $cfg = "qlog.rootLogger = OFF
qlog.appender.cli=SocketAppender
qlog.appender.cli.host=file1.white.%IDC%.qihoo.net
qlog.appender.cli.port=8888
qlog.appender.cli.Threshold=DEBUG

qlog.logger.%MODULE%=DEBUG, cli 
qlog.additivity.%MODULE%=FALSE
        ";

    $IDC = shell_exec(" hostname | awk -F '.' '{print $(NF-2)}' ");
    $IDC = trim($IDC);
    $cfg = str_replace('%IDC%', $IDC, $cfg);
    $cfg = str_replace('%MODULE%', $logger, $cfg);
  
    $ret = file_put_contents($filepath, $cfg);
    if(0 == $ret)
      $filepath = '';
  }

  return $filepath;
}

function get_qlog_conf_path($module)
{
    global $tmpfile;
  $path = '';
  $path = get_exec_user_path();
  if((strlen($path)==0) || (!(is_writable($path)&&is_readable($path)))){
      $path = tempnam(sys_get_temp_dir(), "qlog_conf_");
      chmod($path, 0644);
      $tmpfile = $path;
  }else{
      $path = sprintf('%s/.qlog', $path);
      if(!file_exists($path))
        mkdir($path);
      $path = sprintf("%s/qlog.%s.cfg", $path, $module);
  }

  return $path;
}

function get_exec_user_path()
{
  $path = '';
  $uid = posix_geteuid();
  $userinfo = posix_getpwuid($uid);
  if(isset($userinfo['dir']))
    $path = $userinfo['dir'];
  return $path;
}

?>