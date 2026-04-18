<?php

require_once "sby" . DIRECTORY_SEPARATOR . "HttpUtlis.php";


$httpUtlis = new HttpUtlis();
$data = [
    'merBatchId' => date("YmdHis"),
    'taskId' => 1607707542044405927,
    'providerId' => 30481,
    'payItems' => [
        [
            'merOrderId'=> date("YmdHis"),
            'idCard' => '130823192207011716',
            'payeeAcc' => '6228481728406467399',
            'payeeName' => '崔方方',
            'amt'=> 1002,
            'mobile' => '18832018854',
            'ProviderId' => '30481',
            'paymentType' => 0,
            'memo' => '备注',
            'notifyUrl'=>'http://xxx.com/notify'
        ]
    ]

];

$res = $httpUtlis->buildRequest('6001', $data);
$data = $httpUtlis->sendToYouFu($res);
echo "---------------\n";
echo $data;