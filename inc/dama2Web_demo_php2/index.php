<?php
require('./libs/Dama2Web.class.php');
//1.构造函数传入打码兔用户账号密码
// tangmingming 000198
$web = new Dama2Web('tangmingming','000198');


//2.查询账号余额，返回数组
$a = $web->getBalance();
print_r($a);
echo '账号余额：'.$a['balance'];

//3.通过url打码。返回数组
/*$a=$web->decodeUrl('http://captcha.qq.com/getimage?aid=549000912&r=0.7257105156128585&uin=3056517021',42);
print_r($a);
if($a['ret'] == 0){
	echo '答案：'.$a['result'];
}*/

//同理 返回数组
//print_r($web->decodeHex('1.JPG',42));
print_r($web->decodeHex('3.jpg',200));
//print_r($web->decodeBase64('zw.jpg',61));
//print_r($web->decode('1.JPG',42));
//上报错误

/*
$b = $web->reportError($a['id']);
print_r($b);
*/


?>
