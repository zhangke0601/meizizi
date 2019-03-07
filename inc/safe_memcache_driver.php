<?php
class SafeMemCacheDriver
{
    var $_memcache;
    var $_compress = false;

    function getInstance($MEMCACHE_SERVERS)
    {/*{{{*/
        static $ins;
        if (false == is_a($ins, 'SafeMemCacheDriver'))
        {
            $ins = new SafeMemCacheDriver($MEMCACHE_SERVERS);
        }
        return $ins;
    }/*}}}*/

    function SafeMemCacheDriver($servers)
    {/*{{{*/
        $this->_memcache = new Memcache();
        foreach($servers as $server)
        {
            $persistent = isset($server['persistent']) ? $server['persistent'] : true;
            $this->_memcache->addServer($server['host'], $server['port'], $persistent);
        }

    }/*}}}*/

    /*
     * string SafeMemCacheDriver::get ( string key )
     * array SafeMemCacheDriver::get ( array keys )
     */
    function get($key)
    {/*{{{*/
        return $this->_memcache->get($this->buildKey($key));
    }/*}}}*/

    function buildKey($str)
    {
        return $str;
        //return md5(serialize($str));
    }

    function add($key, $value, $expire=0)
    {/*{{{*/
        return $this->_memcache->add($this->buildKey($key), $value, $this->_compress, $expire);
    }/*}}}*/

    function set($key, $value, $expire=0)
    {/*{{{*/
        return $this->_memcache->set($this->buildKey($key), $value, $this->_compress, $expire);
    }/*}}}*/

    function replace($key, $value, $expire=0)
    {/*{{{*/
        return $this->_memcache->replace($this->buildKey($key), $value, $this->_compress, $expire);
    }/*}}}*/

    function delete($key)
    {/*{{{*/
        return $this->_memcache->delete($this->buildKey($key));
    }/*}}}*/

    function flush()
    {/*{{{*/
        return $this->_memcache->flush();
    }/*}}}*/

    function close()
    {/*{{{*/
        return $this->_memcache->close();
    }/*}}}*/
}
