<?php
interface Storage
{
	public function executeRead($sql, $values=array());
	public function executeWrite($sql, $values=array());
    public function beginTrans();
    public function rollback();
    public function getLastInsertId();
}

class PDODriver implements Storage
{
	private $_dbh;

    public function __construct($host, $dbName, $userName, $password)
    {/*{{{*/
        $this->_dbh = new PDO("mysql:host=$host;dbname=$dbName", $userName, $password);
        $this->_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->_dbh->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }/*}}}*/
	
	public function executeRead($sql, $values=array())
	{/*{{{*/
		try
		{
            //$this->_dbh->query("SET NAMES 'gbk'");
            $sth = $this->_dbh->prepare($sql);
            $i = 0;
            foreach ($values as $value)
            {
                $sth->bindValue(++$i, $value);
            }
            $sth->execute();
            return $sth->fetchAll(PDO::FETCH_ASSOC);
		}
		catch (PDOException $e)
		{
            $this->processError($sql, $e);
		}
	}/*}}}*/
    
    public function executeWrite($sql, $values=array())
	{/*{{{*/
        //throw new BizException('sys_upgrading');
		try
		{
            //$this->_dbh->query("SET NAMES 'latin1'");
            $sth = $this->_dbh->prepare($sql);
            $i = 0;
            foreach ($values as $value)
            {
                $sth->bindValue(++$i, $value);
            }

            return $sth->execute();
		}
		catch (PDOException $e)
		{
            $this->processError($sql, $e);
		}
	}/*}}}*/

    public function executeByHuman($sql, $values=array())
	{/*{{{*/
		try
		{
            $this->_dbh->query("SET NAMES 'gbk'");
            $sth = $this->_dbh->exec($sql);
		}
		catch (PDOException $e)
		{
            $this->processError($sql, $e);
		}
	}/*}}}*/

    private function processError($sql, Exception $e)
    {/*{{{*/
        throw new Exception($e->getMessage());
    }/*}}}*/

    public function beginTrans()
    {/*{{{*/
        $this->_dbh->beginTransaction();
    }/*}}}*/

    public function commit()
    {/*{{{*/
        $this->_dbh->commit();
    }/*}}}*/

    public function rollback()
    {/*{{{*/
        $this->_dbh->rollback();
    }/*}}}*/
	
	public function getLastInsertId()
	{
		return $this->_dbh->lastInsertId();
	}

    public function hasConnection()
    {/*{{{*/
        try
        {
            $this->_dbh->query("select 1;");
            return true;
        }
        catch(PDOException $e)
        {
            return false;
        }
    }/*}}}*/

}
