<?php

require_once "sby".DIRECTORY_SEPARATOR."HttpUtlis.php";



$httpUtlis = new HttpUtlis();
$a =  [
	'name'=>'商户名-NO-111',
	'idCard'=>'130823192207011716',
	'CardNo'=>'6228481728406467399',
	'Mobile'=>'18832018854',
	'ProviderId'=>'30481',
	'PaymentType'=>0,
	'OtherParam'=>'透传参数',
	'IdCardPic1'=>bin2hex(file_get_contents('E:\work\test.png')),
	'IdCardPic2'=>bin2hex(file_get_contents('E:\work\test.png'))
];

$res = $httpUtlis->buildRequest('6010',$a);
$data = $httpUtlis->sendToYouFu($res);
echo "---------------\n";
echo  $data;