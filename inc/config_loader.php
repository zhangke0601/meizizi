<?php
/*
 * $c = ConfigLoader::getInstance();
 * $c->setConfigFile('/your/project/dir/config/config.php');
 * $spec = $c->get('spec');
 * */
class ConfigLoader
{/*{{{*/
    static private $_configs = array();

    private function __construct()
    {
    }

    public function setConfigFile($file)
    {
        assert(is_file($file));

        $this->_configFile = $file;

        return $this;
    }

    static public function getInstance()
    {
        static $ins;
        if (false == $ins instanceof self)
        {
            $ins = new self();
        }
        return $ins;
    }

    public function get($property, $defaultValue=null)
    {
        if (array_key_exists($property, self::$_configs))
        {
            return self::$_configs[$property];
        }
        else
        {
            include($this->_configFile);
            foreach ($configs as $k=>$v)
            {
                self::$_configs[$k] = $v;
            }
            $config = isset($configs[$property])?$configs[$property]:'';
            if ( ('' == $config) && (null != $defaultValue) )
            {
                $config = $defaultValue;
            }
            self::$_configs[$property] = $config;
            return $config;
        }
    }
}/*}}}*/
