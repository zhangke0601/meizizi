<?php
if(php_sapi_name() != 'cli')
{
  session_start();
}
if( !function_exists('qLogConfig') )
{
  function qLogConfig(){};
}
if( !function_exists('qLogInfo'))
{
  function qLogInfo(){};
}