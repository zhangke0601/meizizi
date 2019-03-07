<?php
class StorageRule
{
    static public function getNumericHash($number, $base=10)
    {/*{{{*/
        return abs($number % $base);
    }/*}}}*/

    static public function getStringHash($string, $base=10)
    {/*{{{*/
        $a = (int)pow(2,31);
        $bit = ($a>0)? 64:32;
        if($bit == 32)
          return abs(crc32($string) % $base);
        else
        {
          $crc = (int)crc32($string);
          if($crc >= $a)
          {
            $crc = ~$crc;
            $crc += 1;
            $crc = $crc << 32;
            $crc = $crc >> 32;
          }
          return abs($crc % $base);
        }
    }/*}}}*/

    static public function getRange($number, $range)
    {/*{{{*/
        $preIndex = 0;
        foreach(array_keys($range) as $index)
        {
            if($number < $index)
                return $range[$preIndex];

            $preIndex = $index;
        }

        return $range[$preIndex];
    }/*}}}*/
}
/***
$md5 = "ad08fe53a5e484ea568d60544ef3f05c";
$rule = new StorageRule;

var_dump($rule->getStringHash($md5, 50));
***/
?>
