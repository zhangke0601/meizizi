<?php
require_once("db/dbdriver.php");

class MoniterFile
{

    private $db = NULL;
    private $interval_time = 60;
    private $check_db_time = 0;
    private $data = array();
    private $_gm = NULL;
    
    private $mails = "G-ps-file-dev@360.cn,G-ps-file-jk@360.cn,lujialei@360.cn";
    #private $mails = "lujialei@360.cn";

    public function __construct($db, $gserver)
    {
        $this->db = new DbDriver($db['host'],$db['dbname'],$db['user'],$db['password']);
        $this->_gm= new GearmanClient();
        $this->_gm->addServers($gserver);
    }

    private function get_data_from_db()
    {
        if(time() < $this->check_db_time)
            return;
        // $sql = "select * from tbl_dsv_file_moniter";
        $sql ="select * from competing_products_condition";
        //echo $sql."\n";
        //$data = $this->db->executeRead($sql);
        $result = $this->db->query($sql);
        if (!$result)
            return ;

        $this->check_db_time = time() + $this->interval_time;
        $this->data = array();

        while ($row = $this->db->fetch_array($result))
        {
            array_push($this->data, $row);
        }
        //var_dump($this->data);
        mysql_free_result($result);
    }

    private function moniter_after($check_need_info) 
    {   
        $filename=$check_need_info["file_name"];  
        $filepath=$check_need_info["file_path"];    
        
        #$filename= basename($filepath); 
        #var_dump($check_need_info);
        
        $ret = $this->check_row($check_need_info["sign_corp"], $filename, $check_need_info["file_size"]);
        if($ret === false) return false;    

        $mes = "ruleid:$ret".
            "\r\n<br>|md5:".$check_need_info["md5"].
            "\r\n<br>|sign_corp:".$check_need_info["sign_corp"].
            "\r\n<br>|filename:".$filename.
            "\r\n<br>|size:".$check_need_info["file_size"].
            "\r\n<br>|signed_date:".$check_need_info["signed_date"].
            "\r\n<br>|issuer:".$check_need_info["issuer"].
            "\r\n<br>|path:".$filepath.
            "";   
        $mailHeader = "Content-Type:text/html;charset= GBK \n";  
        $mailHeader .= "X-Mailer: PHP\n"; // mailer
        
        $mescontent = iconv('UTF-8','GBK', $mes);
        #$mescontent = $mes;
        $subject = "竞品监控_延迟置白文件--sign=".iconv('UTF-8','GBK',$check_need_info["sign_corp"]);        
    
        mail($this->mails, $subject, $mescontent,$mailHeader);        
        #echo $subject, $mescontent;        
    
        return true;  
    }

    public function moniter_file($xml_info,$type,$md5=NULL)
    {
        if('pre' != $type)
        {
            $this->get_data_from_db();    
            return $this->moniter_after($xml_info);
        }
        #处理竞品监控增量线数据
        #仅仅发送数据到gearman
        $xml = simplexml_load_string($xml_info);
        $attributes= $xml->attributes();
        $product=(string)$attributes['product'];
        //echo $product."\n";
        if(!$product)
            return false;
        if('Rescan' == $product)
            return false;
        $sign = (int)$xml->upload[0]->attribute[0]->sign;
        //echo $sign."\n";
        if($sign == 0 || $sign & 2 || $sign & 8) 
            $job_handle = $this->_gm->doBackground('dig_competing_products', $xml_info);
        /***
        $sign_corp = (string)$xml->upload[0]->attribute[0]->sign_corp;
        //echo $sign_corp."\n";
        $filename = (string)$xml->upload[0]->attribute[0]->name;
        //echo $filename."\n";
        $size = (int)$xml->upload[0]->attribute[0]->filesize;
        if(!$this->check_row($sign_corp, $filename, $size))
            return false;
        //echo $size."\n";
        $path = (string)$xml->upload[0]->attribute[0]->path; 
        $get_time = (string)$xml->webserver[0]->get_time;
        if($md5 == NULL)
            $md5 = (string)$xml->key[0]->md5;
        $ip = (string)$xml->webserver[0]->client_ip;
        $mes = 'md5:'.$md5."\t".'sign_corp:'.$sign_corp."\t".'name:'.$filename."\t".'size:'.$size."\t".'path:'.$path."\ttime:".date('Y-m-d H:i:s',$get_time)."\tclient_ip:".$ip."\n";
        //echo iconv('utf-8', 'gbk', $mes)."\tclient_ip:".$ip."\n";
        //echo $mes."\n";
        mail($this->mails, "pre竞品监控", iconv('utf-8', 'gbk', $mes));
        ***/
    }

    private function binary2int($binarystr)
    {

        $intval = intval($binarystr);
        $binstr = (string)($intval);
        if(strlen($binstr)<1)
            return 0;
        return bindec($binstr);
    }

    private function check_row($sign_corp, $filename, $size)
    {
        $sign_corp = trim($sign_corp);
        $filename = trim($filename);
        $size = intval($size);
        foreach($this->data as $row)
        {

            //echo $row['action'],"\t",$row['filesize'],"\n";
            $action =intval($row['action']);
            $dsv_check = $this->binary2int($action);
            //check 第三位数据 是否为 1
            if(($dsv_check & 4) != 4)
            {
                //echo $action,"!=4\n";
                continue;
            }
            //echo $action,"==t\n";
            //

            $db_size_array = explode('-',$row['filesize']);
            if(count($db_size_array)<2)
            {
                $db_size = intval($row['filesize']);
                $size_res = 0;
                if($db_size > 0)
                {
                    if($size >= $db_size)
                        $size_res = 2;
                    else
                        $size_res = 1;
                }
            }else
            {
                list($db_size_min,$db_size_max) = $db_size_array;
                $size_res = 0;
                echo 'filesize:',$db_size_min,'-',$db_size_max,"\n";
                if($db_size_min > 0)
                {
                    if( $size >= $db_size_min && $size <=$db_size_max )
                        $size_res = 2;
                    else
                        $size_res = 1;
                }
            }
            if(empty($row['sign_corp']))
                $sign_res = 0;
            elseif($row['sign_corp'] == $sign_corp)
                $sign_res = 2;
            else
                $sign_res = 1;
            if(empty($row['filename']))
                $name_res = 0;
            else
            {
                $pos = stripos($filename, $row['filename']);
                if($pos !== false)
                    $name_res = 2;
                else
                    $name_res = 1;

            }

            echo $sign_res,$size_res,$name_res,"\n";
            $retid = $row['id'];
            if($sign_res == 2)
            {
                if($size_res != 1 && $name_res != 1)
                    return $retid;
            }

            if($sign_res == 0 && $size_res == 0 && $name_res == 2)
                return $retid;
            if($sign_res == 0 && $size_res == 2 && $name_res == 2)
                return $retid;
        }
        return false;
    }
}

?>
