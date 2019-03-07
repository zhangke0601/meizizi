<?php

class DbRule
{
    private $config = false;

    public function getInstance($configFile)
    {/*{{{*/
        static $ins;
        if (false == $ins instanceof self)
        {
            $ins = new self($configFile);
        }
        return $ins;
    }/*}}}*/

    private function __construct($configFile)
    {
        if(!file_exists($configFile))
        {
            echo "the config file is not exist: $configFile \n";
            exit;
        }
        require($configFile);
        $this->config = $config;

    }

    public function MapDbRule()
    {
        return $this->config['db']['map'];

    }

    public function DataDBRule($id)
    {
        return $this->config['db']['data'];
    }

    public function DataTableRule($id, $tablePrefix, $max=NULL)
    {
        if($max === NULL)
            $index = intval($id / $this->config['router']['max_data_in_table']);
        else
            $index = intval($id / $max);
        return $tablePrefix."_".$index;
    }
    
    public function MapTableRule($id='', $tablePrefix='')
    {
        if($id == '' && $tablePrefix == '')
            return 0;
        else
            return $tablePrefix;
    }


}




?>
