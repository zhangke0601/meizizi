<?php
class Test implements Callable
{
    public function run($time)
    {
        error_log(date('H:i:s')." $time\n", 3, '/home/ysq/svn/lp/integration/task/a.log');
        return true;
    }
}
