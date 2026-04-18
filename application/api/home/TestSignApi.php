<?php

require_once "sby".DIRECTORY_SEPARATOR."HttpUtlis.php";

/**
 * 测试签约接口
 */
class TestSignApi
{
    /**
     * 发起签约请求
     */
    public function testSign()
    {
        // 构建签约请求参数
        $data = [
            'name' => '商户名-测试-001',
            'idCard' => '130823192207011716',
            'CardNo' => '6228481728406467399',
            'Mobile' => '18832018854',
            'ProviderId' => '30481',
            'PaymentType' => 0,
            'OtherParam' => '测试签约透传参数',
        ];
        
        // 处理证件照片（如果有）
        // 使用本地测试图片路径 
        $idCardPicPath = '/path/to/your/test/image.jpg'; // 请修改为实际路径
        if (file_exists($idCardPicPath)) {
            $data['IdCardPic1'] = bin2hex(file_get_contents($idCardPicPath));
            $data['IdCardPic2'] = bin2hex(file_get_contents($idCardPicPath));
        }
        
        // 创建HttpUtlis实例
        $httpUtils = new HttpUtlis();
        
        // 构建请求
        $request = $httpUtils->buildRequest('6010', $data);
        
        // 发送请求
        $response = $httpUtils->sendToYouFu($request);
        
        // 输出结果
        echo "签约请求结果：\n";
        echo $response;
        echo "\n";
        
        return $response;
    }
    
    /**
     * 查询签约状态
     */
    public function testQuery()
    {
        // 构建查询请求参数
        $data = [
            'idCard' => '130823192207011716',
            'ProviderId' => '30481'
        ];
        
        // 创建HttpUtlis实例
        $httpUtils = new HttpUtlis();
        
        // 构建请求（假设查询功能码为6020）
        $request = $httpUtils->buildRequest('6020', $data);
        
        // 发送请求
        $response = $httpUtils->sendToYouFu($request);
        
        // 输出结果
        echo "查询签约状态结果：\n";
        echo $response;
        echo "\n";
        
        return $response;
    }
}

// 执行测试
$test = new TestSignApi();

// 测试签约
echo "=== 测试签约接口 ===\n";
$test->testSign();

// 测试查询
echo "\n=== 测试查询签约状态接口 ===\n";
$test->testQuery(); 