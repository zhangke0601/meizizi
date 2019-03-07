function getDb()
{/*{{{*/
    static $dbd;
    if (false == $dbd instanceof DBDelegate)
    {
        $dbc = ConfigLoader::getConfig('septdb');
        $dbd = DBDelegate::getInstance();
        $dbd->setup($dbc);
    }
    return $dbd;
}/*}}}*/

$values = array();
$sql = 'select * from tablename where id=? and type=?';
$values[] = 123;
$values[] = 3;

$db = getDb();
$res = $dbd->execute($sql, $values);
