<?php
require_once('dbdriver.php');
class DBpool
{
    private $_dbhs = array();

    public function getInstance()
    {/*{{{*/
        static $ins;
        if (false == $ins instanceof self)
        {
            $ins = new self();
        }
        return $ins;
    }/*}}}*/

    public function getDBConnect($dbConf)
    {/*{{{*/
        if($dbConf == false)
            return NULL;
        return $this->getDbh($dbConf);
    }/*}}}*/

    public function getDbh($dbConf)
    {/*{{{*/
        $key = $this->genDbhKey($dbConf);
        if(isset($this->_dbhs[$key]))
        {
            return $this->_dbhs[$key]['resource'];
        }
        $session = isset($dbConf['is_long_session']) ? $dbConf['is_long_session'] : false;
        
        $dbh = new DbDriver($dbConf['host'], $dbConf['dbname'], $dbConf['user'], $dbConf['password']);
        $this->addDbh($dbConf, $dbh);
        return $dbh;
    }/*}}}*/

    public function delDbConnect($dbConf)
    {/*{{{*/
        if($dbConf == false)
            return;
        $this->delDbh($dbConf);

    }/*}}}*/

    public function delDbh($dbConf)
    {/*{{{*/
        $key = $this->genDbhKey($dbConf);
    }/*}}}*/

    private function __construct()
    {/*{{{*/

    }/*}}}*/

    private function genDbhKey($dbConf)
    {/*{{{*/
        return md5(serialize($dbConf));
    }/*}}}*/

    private function addDbh($dbConf, $dbh)
    {/*{{{*/
        $key = $this->genDbhKey($dbConf);
        $this->_dbhs[$key]['resource'] = $dbh;
    }/*}}}*/

}

?>
