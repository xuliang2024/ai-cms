<?php
namespace app\api\home;

use think\Controller;
use think\Request;
require_once __DIR__.DIRECTORY_SEPARATOR."sby".DIRECTORY_SEPARATOR."HttpUtlis.php";

/**
 * 批量付款控制器
 * 提供批量付款和查询相关的接口
 */
class BatchPay extends Controller
{
    private $httpUtils;
    
    public function __construct()
    {
        parent::__construct();
        $this->httpUtils = new \HttpUtlis();
    }
    
    /**
     * 批量付款接口
     * @return \think\response\Json
     */
    public function submit()
    {
        // 获取请求数据
        $request = request();
        $params = $request->param();
        
        // 调试信息记录
        $logData = [
            'params' => $params
        ];
        file_put_contents('/tmp/batch_pay_debug.log', date('Y-m-d H:i:s') . ' - ' . json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        // 必填参数验证
        $requiredFields = ['merBatchId', 'payItems', 'taskId', 'providerId'];
        foreach ($requiredFields as $field) {
            if (empty($params[$field])) {
                return json(['code' => 400, 'msg' => '缺少必填参数：' . $field, 'data' => null]);
            }
        }
        
        // 验证payItems中的必填字段
        foreach ($params['payItems'] as $item) {
            $requiredPayFields = ['merOrderId', 'amt', 'payeeName', 'payeeAcc', 'idCard', 'mobile'];
            foreach ($requiredPayFields as $field) {
                if (empty($item[$field])) {
                    return json(['code' => 400, 'msg' => 'payItems中缺少必填参数：' . $field, 'data' => null]);
                }
            }
            
            // 验证merOrderId长度
            if (strlen($item['merOrderId']) > 32) {
                return json(['code' => 400, 'msg' => 'merOrderId长度不能超过32位', 'data' => null]);
            }
            
            // 验证金额限制
            if ($item['amt'] < 1000 || $item['amt'] > 98000000) {
                return json(['code' => 400, 'msg' => '付款金额必须在10元到9.8万元之间', 'data' => null]);
            }
            
            // 验证payeeName长度
            if (strlen($item['payeeName']) > 50) {
                return json(['code' => 400, 'msg' => 'payeeName长度不能超过50位', 'data' => null]);
            }
            
            // 验证payeeAcc长度
            if (strlen($item['payeeAcc']) > 28) {
                return json(['code' => 400, 'msg' => 'payeeAcc长度不能超过28位', 'data' => null]);
            }
            
            // 验证idCard长度
            if (strlen($item['idCard']) !== 18) {
                return json(['code' => 400, 'msg' => 'idCard长度必须为18位', 'data' => null]);
            }
            
            // 验证mobile格式
            if (!preg_match('/^(1[2,3,4,5,6,7,8,9][0-9])\d{8}$/', $item['mobile'])) {
                return json(['code' => 400, 'msg' => 'mobile格式不正确', 'data' => null]);
            }
            
            // 验证memo长度
            if (!empty($item['memo']) && strlen($item['memo']) > 20) {
                return json(['code' => 400, 'msg' => 'memo长度不能超过20位', 'data' => null]);
            }
            
            // 验证paymentType值
            if (!in_array($item['paymentType'], [0, 1, 2])) {
                return json(['code' => 400, 'msg' => 'paymentType值必须为0(银行卡)、1(支付宝)或2(微信)', 'data' => null]);
            }
            
            // 验证notifyUrl长度
            if (!empty($item['notifyUrl']) && strlen($item['notifyUrl']) > 100) {
                return json(['code' => 400, 'msg' => 'notifyUrl长度不能超过100位', 'data' => null]);
            }
            
            // 验证备注中是否包含敏感词
            if (!empty($item['memo'])) {
                $sensitiveWords = ['工资', '薪酬', '提现', '薪', '补贴', '分红', '奖金', '返现', '劳务费', '分润', '备用金', '咨询'];
                foreach ($sensitiveWords as $word) {
                    if (strpos($item['memo'], $word) !== false) {
                        return json(['code' => 400, 'msg' => '备注中不能包含敏感词：' . $word, 'data' => null]);
                    }
                }
            }
        }
        
        // 构建请求参数
        $data = [
            'merBatchId' => $params['merBatchId'],
            'payItems' => $params['payItems'],
            'taskId' => $params['taskId'],
            'providerId' => $params['providerId']
        ];
        
        // 记录最终提交数据
        file_put_contents('/tmp/batch_pay_debug.log', date('Y-m-d H:i:s') . ' - 提交数据: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        try {
            // 构建批量付款请求，6001为批量付款接口的功能码
            $request = $this->httpUtils->buildRequest('6001', $data);
            
            // 发送请求到优付平台
            $response = $this->httpUtils->sendToYouFu($request);
            file_put_contents('/tmp/batch_pay_debug.log', date('Y-m-d H:i:s') . ' - 响应结果: ' . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            // 处理响应
            if ($response === false) {
                return json(['code' => 500, 'msg' => '批量付款请求失败', 'data' => null]);
            }
            
            // 解析并返回响应结果
            $result = json_decode($response, true);
            

            return json([
                'code' => $result['resCode'] === '0000' ? 200 : 500,
                'msg' => $result['resCode'] === '0000' ? '批量付款请求成功' : $result['resMsg'],
                'data' => isset($result['resData']) ? $result['resData'] : null
            ]);
            
        } catch (\Exception $e) {
            // 异常处理
            return json(['code' => 500, 'msg' => '批量付款过程发生异常：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    /**
     * 批量付款查询接口
     * @return \think\response\Json
     */
    public function query()
    {
        // 获取请求数据
        $request = request();
        $params = $request->param();
        
        // 调试信息记录
        $logData = [
            'params' => $params
        ];
        file_put_contents('/tmp/batch_pay_query_debug.log', date('Y-m-d H:i:s') . ' - ' . json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        // 必填参数验证
        if (empty($params['merBatchId'])) {
            return json(['code' => 400, 'msg' => '缺少必填参数：merBatchId', 'data' => null]);
        }
        
        // 构建请求参数
        $data = [
            'merBatchId' => $params['merBatchId']
        ];
        
        // 如果有查询项，添加到请求参数中
        if (!empty($params['queryItems'])) {
            $data['queryItems'] = $params['queryItems'];
        }
        
        // 记录最终提交数据
        file_put_contents('/tmp/batch_pay_query_debug.log', date('Y-m-d H:i:s') . ' - 提交数据: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        try {
            // 构建查询请求，6002为批量付款查询接口的功能码
            $request = $this->httpUtils->buildRequest('6002', $data);
            
            // 发送请求到优付平台
            $response = $this->httpUtils->sendToYouFu($request);
            
            // 处理响应
            if ($response === false) {
                return json(['code' => 500, 'msg' => '批量付款查询失败', 'data' => null]);
            }
            
            // 解析并返回响应结果
            $result = json_decode($response, true);
            
            return json([
                'code' => $result['resCode'] === '0000' ? 200 : 500,
                'msg' => $result['resCode'] === '0000' ? '查询成功' : $result['resMsg'],
                'data' => isset($result['resData']) ? $result['resData'] : null
            ]);
            
        } catch (\Exception $e) {
            // 异常处理
            return json(['code' => 500, 'msg' => '批量付款查询过程发生异常：' . $e->getMessage(), 'data' => null]);
        }
    }
} 