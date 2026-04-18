<?php
require_once "sby".DIRECTORY_SEPARATOR."HttpUtlis.php";

class TestLijianjin{

   public static $providerId = 40014;

   public static $activityId = 1785133628274413570;

    /**余额查询
     * @return void
     */
   function queryBalance(){
       $requestData = [
           'accountType' => 'BASIC',
           'productSubType' => 'MONEY',
           'providerId' => static ::$providerId,
       ];
       $httpUtlis = new HttpUtlis();
       $res = $httpUtlis->buildRequest('8003', $requestData);
       $data = $httpUtlis->sendToYouFu($res);
       echo "---------------\n";
       echo $data;
   }

    /**
     * 零钱发放
     * @return void
     */
    function sendMoney()
    {

        $requestData = [
            'accountType' => 'BASIC',
            'productSubType' => 'MONEY',
            'appId'=>'wx',
            'providerId' => static ::$providerId,
            'amt' => 102,
            'merOrderId'=>'订单号',
            'payeeAcc' => 'wxfcrbt',
            'payeeName' => 'xxxxx',
            'memo' => '备注',
            'activityId'=> static ::$activityId,
            'activityDeliver' => [
                      'name'=>'交付物',
                      'amount'=>102,
                      'attachment'=>'交付说明',
                      'attachmentType'=>'TEXT'
          ]
        ];
        $httpUtlis = new HttpUtlis();
        $res = $httpUtlis->buildRequest('8001', $requestData);
        $data = $httpUtlis->sendToYouFu($res);
        echo "---------------\n";
        echo $data;
    }

    /**
     * 红包发放
     * @return void
     */
    function sendRedPack()
    {

        $requestData = [
            'accountType' => 'BASIC',
            'productSubType' => 'REDPACK',
            'providerId' => static ::$providerId,
            'amt' => 1000,
            'merOrderId'=>'订单号',
            'payeeAcc' => 'xxx',
            'payeeName' => 'xxx',
            'memo' => 0,
            'redPackName' => '红包名称',
            'wishing' => '祝福语',
        ];
        $httpUtlis = new HttpUtlis();
        $res = $httpUtlis->buildRequest('8001', $requestData);
        $data = $httpUtlis->sendToYouFu($res);
        echo "---------------\n";
        echo $data;
    }

    /**
     * 查询订单状态
     * @return void
     */
    function querySendMoney(){
        $requestData = [
            'merOrderId'=>'订单号',
        ];
        $httpUtlis = new HttpUtlis();
        $res = $httpUtlis->buildRequest('8002', $requestData);
        $data = $httpUtlis->sendToYouFu($res);
        echo "---------------\n";
        echo $data;
    }

}
$lijianjin = new TestLijianjin();
$lijianjin->queryBalance();

$lijianjin->sendMoney();


