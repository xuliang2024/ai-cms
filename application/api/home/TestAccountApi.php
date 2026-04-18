<?php

require_once "AccountApi.php";

/**
 * 账户查询接口测试
 */
echo "===== 账户查询接口测试 =====\n";

// 创建API实例
$accountApi = new AccountApi();

// 测试查询账户余额
echo "\n===== 测试查询账户余额 =====\n";
$providerId = 55020; // 可以根据需要修改商户ID
$balanceResult = $accountApi->queryBalance($providerId);
echo "查询结果：\n";
echo $balanceResult;
echo "\n";

// 测试查询账户交易记录
echo "\n===== 测试查询账户交易记录 =====\n";
$startDate = date('Ymd', strtotime('-30 days')); // 查询最近30天的交易
$endDate = date('Ymd');
$page = 1;
$pageSize = 20;

$txnResult = $accountApi->queryTransactions($providerId, $startDate, $endDate, $page, $pageSize);
echo "查询结果：\n";
echo $txnResult;
echo "\n"; 