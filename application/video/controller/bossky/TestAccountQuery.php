<?php
require_once "sby".DIRECTORY_SEPARATOR."HttpUtlis.php";

/**
 * 商户查询账户可用余额
 */
echo "商户查询账户可用余额";
$httpUtlis = new HttpUtlis();
$a =  ['providerId'=>555002];
$res = $httpUtlis->buildRequest("6003",$a);
$data = $httpUtlis->sendToYouFu($res);
echo  $data;