<?php
namespace app\api\home;

use think\Controller;
use think\Request;
require_once __DIR__.DIRECTORY_SEPARATOR."sby".DIRECTORY_SEPARATOR."HttpUtlis.php";

/**
 * 账户查询控制器
 * 提供账户查询相关的接口
 */
class Account extends Controller
{
    private $httpUtils;
    
    public function __construct()
    {
        parent::__construct();
        $this->httpUtils = new \HttpUtlis();
    }
    
    /**
     * 查询账户可用余额
     * @return \think\response\Json
     */
    public function balance()
    {
        // 获取请求数据
        $request = request();
        $params = $request->param();
        
        // 调试信息记录
        $logData = [
            'params' => $params
        ];
        file_put_contents('/tmp/account_balance_debug.log', date('Y-m-d H:i:s') . ' - ' . json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        // 处理参数，兼容大小写
        if (empty($params['providerId']) && !empty($params['ProviderId'])) {
            $params['providerId'] = $params['ProviderId'];
        }
        
        // 必填参数验证
        if (empty($params['providerId'])) {
            return json(['code' => 400, 'msg' => '缺少必填参数：providerId', 'data' => null]);
        }
        
        // 构建请求参数
        $data = [
            'providerId' => $params['providerId']
        ];
        
        try {
            // 构建余额查询请求，6003为账户余额查询功能码
            $request = $this->httpUtils->buildRequest('6003', $data);
            
            // 发送请求到优付平台
            $response = $this->httpUtils->sendToYouFu($request);
            
            // 处理响应
            if ($response === false) {
                return json(['code' => 500, 'msg' => '查询账户余额失败', 'data' => null]);
            }
            
            // 解析并返回响应结果
            $result = json_decode($response, true);
            
            return json([
                'code' => $result['resCode'] === '0000' ? 200 : 500,
                'msg' => $result['resCode'] === '0000' ? '查询余额成功' : $result['resMsg'],
                'data' => isset($result['resData']) ? $result['resData'] : null
            ]);
            
        } catch (\Exception $e) {
            // 异常处理
            return json(['code' => 500, 'msg' => '查询余额过程发生异常：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    /**
     * 查询账户交易记录
     * @return \think\response\Json
     */
    public function transactions()
    {
        // 获取请求数据
        $request = request();
        $params = $request->param();
        
        // 调试信息记录
        $logData = [
            'params' => $params
        ];
        file_put_contents('/tmp/account_transactions_debug.log', date('Y-m-d H:i:s') . ' - ' . json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        // 处理参数，兼容大小写
        if (empty($params['providerId']) && !empty($params['ProviderId'])) {
            $params['providerId'] = $params['ProviderId'];
        }
        
        // 必填参数验证
        if (empty($params['providerId'])) {
            return json(['code' => 400, 'msg' => '缺少必填参数：providerId', 'data' => null]);
        }
        
        // 设置默认值
        if (empty($params['startDate'])) {
            $params['startDate'] = date('Ymd', strtotime('-7 days'));
        }
        
        if (empty($params['endDate'])) {
            $params['endDate'] = date('Ymd');
        }
        
        if (empty($params['page'])) {
            $params['page'] = 1;
        }
        
        if (empty($params['pageSize'])) {
            $params['pageSize'] = 10;
        }
        
        // 构建请求参数
        $data = [
            'providerId' => $params['providerId'],
            'startDate' => $params['startDate'],
            'endDate' => $params['endDate'],
            'page' => $params['page'],
            'pageSize' => $params['pageSize']
        ];
        
        // 记录最终提交数据
        file_put_contents('/tmp/account_transactions_debug.log', date('Y-m-d H:i:s') . ' - 提交数据: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        try {
            // 构建交易记录查询请求，6004为交易记录查询功能码
            $request = $this->httpUtils->buildRequest('6004', $data);
            
            // 发送请求到优付平台
            $response = $this->httpUtils->sendToYouFu($request);
            
            // 处理响应
            if ($response === false) {
                return json(['code' => 500, 'msg' => '查询交易记录失败', 'data' => null]);
            }
            
            // 解析并返回响应结果
            $result = json_decode($response, true);
            
            return json([
                'code' => $result['resCode'] === '0000' ? 200 : 500,
                'msg' => $result['resCode'] === '0000' ? '查询交易记录成功' : $result['resMsg'],
                'data' => isset($result['resData']) ? $result['resData'] : null
            ]);
            
        } catch (\Exception $e) {
            // 异常处理
            return json(['code' => 500, 'msg' => '查询交易记录过程发生异常：' . $e->getMessage(), 'data' => null]);
        }
    }
} 