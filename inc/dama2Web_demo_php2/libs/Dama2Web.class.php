<?php

class Dama2Web {
	const APP_ID = '40838';
	const APP_KEY = 'ca9507e17e8d5ddf7c57cd18d8d33010';
	const HOST = 'http://api.dama2.com:7766/app/';
	
	public function __construct($username,$password) {
		$this->username = $username;
		$this->password = $password;
	}
	
	/*
		sign的计算
	*/
	private function getSign($pram='',$ck='') {
		return substr(md5(self::APP_KEY . $this->username . $pram . $ck),0,8);
	}
	/*
		pwd的计算
	*/
	private function getPwd() {
		return md5(self::APP_KEY . md5(md5($this->username) . md5($this->password)));
	}
		
	private function get($path,$param=array()) {
		$ch = curl_init();
    	$request = http_build_query($param);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_URL, self::HOST . $path . '?' .$request);
    	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);  
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	
	private function post($path,$param=array()) {
		$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, self::HOST . $path);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 		curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_TIMEOUT, 45);     	
    	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);   	
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $param);

		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	
	/*
		查询余额
	*/
	public function getBalance() {
		$param = array(
			'appID' => self::APP_ID,
			'user' => $this->username,
			'pwd' => $this->getPwd(),
			'sign' => $this->getSign(),
		);
		return json_decode($this->get('d2Balance',$param),true);
		
	}
	/*
		上传图片以图片16进制打码
		@param string file 图片路径
		@param int type 验证码类型
		@return array		
	*/
	
	public function decodeHex($file,$type) {
		$data = file_get_contents($file);
		$filedata = bin2hex($data);
		$param = array(
			'appID' => self::APP_ID,
			'user' => $this->username,
			'pwd' => $this->getPwd(),
			'fileData' => $filedata,
			'type' => $type,
			'sign' => $this->getSign($data),
		);
		$param = http_build_query($param);
		return json_decode($this->post('d2File',$param),true);
	}
	/*
		上传图片以图片base64打码
		@param string file 图片路径
		@param int type 验证码类型
		@return array		
	*/
	public function decodeBase64($file,$type) {
		$data = file_get_contents($file);
		$fileDataBase64 = base64_encode($data);
		$param = array(
			'appID' => self::APP_ID,
			'user' => $this->username,
			'pwd' => $this->getPwd(),
			'fileDataBase64' => $fileDataBase64,
			'type' => $type,
			'sign' => $this->getSign($data),
		);
		$param = http_build_query($param);
		return json_decode($this->post('d2File',$param),true);
	}	
	/*
		上传图片以图片multipart/form-data打码
		@param string file 图片路径
		@param int type 验证码类型
		@return array		
	*/
	public function decode($file,$type) {
		$data = file_get_contents($file);
		$param = array(
			'appID' => self::APP_ID,
			'user' => $this->username,
			'pwd' => $this->getPwd(),
			'文件数据' => '@' . realpath($file),
			'type' => $type,
			'sign' => $this->getSign($data),
		);
		
		return json_decode($this->post('d2File',$param),true);
	}	
	
	/*
		上传图片url打码
		@param string url 图片url地址
		@param int type 验证码类型
		@param string cookie值 [可选]
		@param string referer [可选]
		@param int len [可选]	
		@return array		
	*/
	public function decodeUrl($url,$type,$cookie='',$referer='',$len='') {
		$param = array(
			'appID' => self::APP_ID,
			'user' => $this->username,
			'pwd' => $this->getPwd(),
			'url' => urlencode($url),
			'type' => $type,
			'sign' => $this->getSign($url,$cookie),
		);
		$cookie?$param['cookie'] = urlencode($cookie):'';
		$referer?$param['referer'] = $referer:'';
		$len?$param['len'] = $len:'';
		$param = http_build_query($param);
		return json_decode($this->post('d2Url',$param),true);
	}
	
	/*
		上报错误
		@param int id  上传验证码返回的id
	*/
	public function reportError($id) {
		$param = array(
			'appID' => self::APP_ID,
			'user' => $this->username,
			'pwd' => $this->getPwd(),
			'id' => $id,
			'sign' => $this->getSign($id),
		);
		return json_decode($this->get('d2ReportError',$param),true);		
	} 
	
	
	
}






















?>