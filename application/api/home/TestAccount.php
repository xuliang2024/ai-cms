<?php
/**
 * 测试Account控制器接口
 * 这是一个模拟请求的脚本，用于测试接口功能
 */

// 模拟查询账户余额的HTTP请求
function testBalanceApi()
{
    echo "===== 测试账户余额查询接口 =====\n";
    
    // 构建请求参数
    $url = 'http://localhost/api/Account/balance';  // 请修改为实际URL
    $params = [
        'providerId' => 55020  // 替换为实际商户ID
    ];
    
    // 发送POST请求
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo "CURL错误: " . curl_error($ch) . "\n";
    } else {
        echo "接口响应: \n";
        $result = json_decode($response, true);
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    
    curl_close($ch);
}

// 模拟查询账户交易记录的HTTP请求
function testTransactionsApi()
{
    echo "\n===== 测试账户交易记录查询接口 =====\n";
    
    // 构建请求参数
    $url = 'http://localhost/api/Account/transactions';  // 请修改为实际URL
    $params = [
        'providerId' => 55020,
        'startDate' => date('Ymd', strtotime('-30 days')),
        'endDate' => date('Ymd'),
        'page' => 1,
        'pageSize' => 20
    ];
    
    // 发送POST请求
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo "CURL错误: " . curl_error($ch) . "\n";
    } else {
        echo "接口响应: \n";
        $result = json_decode($response, true);
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    
    curl_close($ch);
}

// 执行测试
testBalanceApi();
testTransactionsApi();

echo "\n注意: 请确保服务器已启动，并且URL配置正确。\n";
echo "如果直接在命令行运行此脚本，您需要确保PHP环境已正确配置curl扩展。\n"; 