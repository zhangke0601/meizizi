<?php
class RequestDelegate
{/*{{{*/
    const TIME_OUT = 600;
    const MAX_RETRY_TIME = 2;

    static private $_logpath= null;

    static private function log($msg)
    {/*{{{*/
        if (null != self::$_logpath)
        {
            error_log($msg, 3, self::$_logpath);
        }
    }/*}}}*/

    static public function setLogPath($path)
    {/*{{{*/
        self::$_logpath = $path;
    }/*}}}*/

    static public function requestByProxy($proxy, $urlInfo, $params=array(), $timeout=3)
    {/*{{{*/
        $request = self::parseUrl($urlInfo) . self::parseParams($params);

        $ch = curl_init();

        $proxyUrl = self::parseUrl($proxy);
        $header = array('Cache-Control: no-cache');
        curl_setopt($ch, CURLOPT_PROXY, $proxyUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_URL, $request);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }/*}}}*/

    static public function request($hosts, $method='get', $args='', $cookie='', $timeout=self::TIME_OUT, $noRetry=false)
    {/*{{{*/
        assert(false==empty($hosts));

        $url = self::pickupHost($hosts);

        $ch = curl_init();
        if ( ('get' == $method) && ('' != $args) )
        {
            $url = self::preGetData($url, $args);
        }
        else
        {
            $data = self::convert($args);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_URL, $url);

        //proxy
        /*
        $header = array('Cache-Control: no-cache');
        curl_setopt($ch, CURLOPT_PROXY, 'http://cache1.web.bjt.qihoo.net:80');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        */
        //$header = array();
        //$header[] = 'Cache-Control: no-cache';
        //$header[] = 'Host:http://intf.f.360.cn:8360';
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, 'User-AgentMozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3');
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);


        $result = array();
        $result = curl_exec($ch);

        self::processErr($ch, $url, $data);

        //retry
        $tries = 0;
        while ( (false == $noRetry) && (false === $result) && (false == empty($hosts)) && ($tries <= self::MAX_RETRY_TIME) )
        {
            ++$tries;
            $result = self::request($hosts, $method, $args, $cookie, $timeout, true);

            $msg = 'retry('.$tries.'): url:'.$url;
            self::log($msg);
        }
        $info = curl_getinfo($ch);
        curl_close($ch);
        return array($result, $info['http_code']);
    }/*}}}*/

    static private function processErr($ch, $url, $data)
    {/*{{{*/
        $msg = curl_error($ch);
        if ('' != $msg)
        {
            $time = date('Y-m-d H:i:s');
            $msg = '['.$time.'] '.$msg." | ".$url.' | '.$data."\n";
            
            self::log($msg);
        }
    }/*}}}*/

    static private function preGetData($url, $args)
    {/*{{{*/
        $data = self::convert($args);
        if (false === strstr($url, '?'))
        {
            $url = $url.'?'.$data;
        }
        else
        {
            $url = $url.'&'.$data;
        }
        return $url;
    }/*}}}*/

    static private function microtimeFloat()
    {/*{{{*/
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }/*}}}*/

    static private function pickupHost(&$hosts)
    {/*{{{*/
        if (false == is_array($hosts))
        {
            $tries = array();
            array_push($tries, $hosts);
            array_push($tries, $hosts);
            $hosts = $tries;
        }
        $key = array_rand($hosts);
        if (null !== $key)
        {
            $url = $hosts[$key];
            unset($hosts[$key]);
            return $url;
        }
        //url is none!;
        assert(false);
    }/*}}}*/

    static private function convert($args)
    {/*{{{*/
        $data = '';
        if ('' != $args && is_array($args))
        {
            foreach ($args as $key=>$val)
            {
                if (is_array($val))
                {
                    foreach ($val as $k=>$v)
                    {
                        $data .= $key.'['.$k.']='.rawurlencode($v).'&';
                    }
                }
                else
                {
                    $data .="$key=".rawurlencode($val)."&";
                }
            }
            return $data;
        }
        return $args;
    }/*}}}*/

    static public function multiRequest($requests, $data, $cookie, $timeout=self::TIME_OUT)
    {/*{{{*/
        $mh = curl_multi_init();

        foreach($requests as $request)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('host:baike.360.cn'));
            curl_setopt($ch, CURLOPT_URL, $request);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            
            curl_setopt($ch, CURLOPT_USERAGENT, 'User-AgentMozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3');
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);


            curl_multi_add_handle($mh, $ch);
            $conn[] = $ch;
        }
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active and $mrc == CURLM_OK)
        {
            if (curl_multi_select($mh) != -1)
            {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        $cnt = count($requests);
        for($i =0; $i < $cnt; $i++)
        {
            if(!curl_errno($conn[$i]))
            {
                $res[$i] = curl_multi_getcontent($conn[$i]);
            }
            curl_multi_remove_handle($mh, $conn[$i]);
            curl_close($conn[$i]);
        }
        curl_multi_close($mh);

        return $res;
    }/*}}}*/

    static public function multiRequestByProxy($proxy, $requests, $timeout=self::TIME_OUT)
    {/*{{{*/
        $proxyUrl = self::parseUrl($proxy);
        $header = array('Cache-Control: no-cache');
        $mh = curl_multi_init();

        foreach($requests as $request)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_PROXY, $proxyUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_URL, $request);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_multi_add_handle($mh, $ch);
            $conn[] = $ch;
        }

        do {
            $mrc = curl_multi_exec($mh, $active);
        } while($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active and $mrc == CURLM_OK)
        {
            if (curl_multi_select($mh) != -1)
            {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        $cnt = count($requests);
        for($i =0; $i < $cnt; $i++)
        {
            if(!curl_errno($conn[$i]))
            {
                $res[$i] = curl_multi_getcontent($conn[$i]);
            }
            curl_multi_remove_handle($mh, $conn[$i]);
            curl_close($conn[$i]);
        }
        curl_multi_close($mh);

        return $res;
    }/*}}}*/

    static public function parseUrl($urlInfo)
    {/*{{{*/
        $scheme = isset($urlInfo['scheme']) ? $urlInfo['scheme'] : 'http';
        $port = isset($urlInfo['port']) ? $urlInfo['port'] : 80;
        $path = isset($urlInfo['path']) ? $urlInfo['path'] : '';

        $request = $scheme . '://'. $urlInfo['host'] .':'. $port . $path;
        return $request;
    }/*}}}*/

    static public function parseParams($params)
    {/*{{{*/
        $paramString = '';
        $pairs = array();
        foreach($params as $key => $value)
        {
            $pair = $key .'='. $value;
            array_push($pairs, $pair);
        }
        if($query = implode('&', $pairs))
        {
            $paramString .= '?' . $query;
        }

        return $paramString;
    }/*}}}*/
}/*}}}*/

class Utility
{/*{{{*/
    static public function isUtf8($str)
    {/*{{{*/
        $a = mb_convert_encoding($str, 'gbk', 'utf-8');
        $b = mb_convert_encoding($a, 'utf-8', 'gbk');
        return $str === $b;
    }/*}}}*/

	//for example input ==> abc.jpg  return ==> jpg
	static public function getFileSuffix($fileName, $default='')
	{/*{{{*/
		$result = $fileName;
		$splitChar = ".";
		if(strrpos($result, $splitChar))
		{
			while(strrpos($result, $splitChar))
			{
				$result = substr($result, strrpos($result, $splitChar)+1);
			}
			return $result;
		}
		else
		{
			return $default;
		}
	}/*}}}*/

    static public function convertEncoding($arr, $toEncoding, $fromEncoding='', $convertKey=false)
    {/*{{{*/
        if (empty($arr))
        {
            return $arr;
        }
        if ($toEncoding == $fromEncoding)
        {
            return $arr;
        }
        if (is_array($arr))
        {
            $keys = array_keys($arr);
            for ($i=0,$max=count($keys);$i<$max;$i++)
            {
                $key = $keys[$i];
                $res = $arr[$key];
                if ($convertKey)
                {
                    unset($arr[$key]);
                    $key = mb_convert_encoding($key, $toEncoding, $fromEncoding);
                }

                if (is_array($res))
                {
                    $res = self::convertEncoding($res, $toEncoding, $fromEncoding, $convertKey);
                }
                else
                {
                    $res = mb_convert_encoding($res, $toEncoding, $fromEncoding);
                }

                $arr[$key] = $res;
            }
        }
        else
        {
            $arr = mb_convert_encoding($arr, $toEncoding, $fromEncoding);
        }
        return $arr;
    }/*}}}*/

    static public function getHugeDataHelper($varName, $fileName)
    {/*{{{*/
        $handler = __FILE__;
        $ch = CacheHelper::getInstance($handler);
        $$varName = $ch->get($varName);
        if (false == $$varName)
        {
            assert(is_file($fileName));
            include($fileName);
            
            $ch->set($varName, $$varName);
        }
        return $$varName;
    }/*}}}*/

    static public function getMicroTime()
    {/*{{{*/
        list($usec, $sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }/*}}}*/
}/*}}}*/
