<?php
/*
 * lujialei
 * Ïû³ýphp,get_magic_quotes_gpcµÄÓ°Ïì
 *
 */ 
function stripslashes_deep($value)
{
    $value = is_array($value) ? array_map('stripslashes_deep', $value) :  stripslashes($value);
    return $value;
}

function ignore_magic($value,$force = false)
{
    if (get_magic_quotes_gpc() || get_magic_quotes_runtime()) 
    {
        return stripslashes_deep($value);
    }else
    {   
        if($force !== false )
            return stripslashes_deep($value);
        else
            return $value;
    }
}
function ignore_magic_all($force = false)
{
    global $_POST;
    global $_GET;
    global $_REQUEST;

    if(isset($_POST))        
        $_POST = ignore_magic($_POST,$force);        

    if(isset($_GET))
        $_GET = ignore_magic($_GET,$force);
    
    if(isset($_REQUEST))
        $_REQUEST = ignore_magic($_REQUEST,$force);

}


#test 
/*
$_POST = array('1'=>'\\\'','a'=>array('a1'=>'abc\"','a2'=>'/path \"%driver%\" /a /w'));
var_dump($_POST);
var_dump($_GET);
echo "\n\n";
*/

?>
