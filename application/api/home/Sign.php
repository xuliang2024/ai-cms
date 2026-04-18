<?php
namespace app\api\home;

use think\Controller;
use think\Request;
use think\Db;
require_once __DIR__.DIRECTORY_SEPARATOR."sby".DIRECTORY_SEPARATOR."HttpUtlis.php";

/**
 * 签约控制器
 * 提供签约相关的接口
 */
class Sign extends Controller
{
    private $httpUtils;
    
    /**
     * 保存图片并返回可访问的URL
     * @param string $tempPath 临时文件路径
     * @return string 可访问的URL
     */
    private function saveImageAndGetUrl($tempPath)
    {
        try {
            // 生成UUID作为文件名
            $uuid = uniqid('idcard_', true);
            $filename = $uuid . '.png';
            
            // 设置保存路径（相对于网站根目录的public/uploads/idcard/）
            $savePath = '/www/wwwroot/ai-cms.fyshark.com/public/uploads/idcard/';
            
            // 确保目录存在
            if (!is_dir($savePath)) {
                mkdir($savePath, 0755, true);
            }
            
            // 移动文件到目标位置
            $targetPath = $savePath . $filename;
            move_uploaded_file($tempPath, $targetPath);
            
            // 返回可访问的URL
            $baseUrl = request()->domain();
            return $baseUrl . '/uploads/idcard/' . $filename;
            
        } catch (\Exception $e) {
            file_put_contents('/tmp/sign_debug.log', date('Y-m-d H:i:s') . ' - 图片保存错误: ' . $e->getMessage() . "\n", FILE_APPEND);
            return '';
        }
    }
    
    public function __construct()
    {
        parent::__construct();
        $this->httpUtils = new \HttpUtlis();
    }
    
    /**
     * 提交签约
     * @return \think\response\Json
     */
    public function submit()
    {
        // 获取请求数据
        $request = request();
        $params = $request->param();
        
        // 调试信息记录
        $logData = [
            'params' => $params,
            'files' => $request->file()
        ];
        file_put_contents('/tmp/sign_debug.log', date('Y-m-d H:i:s') . ' - ' . json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        // 文件上传处理
        $files = $request->file();
        
        // 检查是否有文件上传
        if (!empty($files['idCardPic1'])) {
            $params['idCardPic1'] = $files['idCardPic1']->getPathname();
        } else if (!empty($files['IdCardPic1'])) {
            $params['idCardPic1'] = $files['IdCardPic1']->getPathname();
        }
        
        if (!empty($files['idCardPic2'])) {
            $params['idCardPic2'] = $files['idCardPic2']->getPathname();
        } else if (!empty($files['IdCardPic2'])) {
            $params['idCardPic2'] = $files['IdCardPic2']->getPathname();
        }
        
        // 必填参数验证
        $requiredFields = ['name', 'idCard', 'cardNo', 'mobile', 'providerId' , 'idCardPic1' , 'idCardPic2' , 'user_id'];
        foreach ($requiredFields as $field) {
            // 兼容处理大小写不同的参数名
            $altField = ucfirst($field); // 首字母大写的替代字段名
            if (empty($params[$field]) && empty($params[$altField])) {
                return json(['code' => 400, 'msg' => '缺少必填参数：' . $field, 'data' => null]);
            }
            
            // 如果参数在首字母大写的形式中存在，赋值给原始参数名
            if (empty($params[$field]) && !empty($params[$altField])) {
                $params[$field] = $params[$altField];
            }
        }
        
        // 构建请求参数
        $data = [
            'name' => $params['name'],
            'idCard' => $params['idCard'],
            'cardNo' => $params['cardNo'] ?? $params['CardNo'],  // 兼容处理
            'mobile' => $params['mobile'] ?? $params['Mobile'],  // 兼容处理
            'providerId' => $params['providerId'] ?? $params['ProviderId'],  // 兼容处理
            'paymentType' => isset($params['paymentType']) ? $params['paymentType'] : 
                            (isset($params['PaymentType']) ? $params['PaymentType'] : 0),  // 兼容处理
            'otherParam' => isset($params['otherParam']) ? $params['otherParam'] : 
                           (isset($params['OtherParam']) ? $params['OtherParam'] : ''),  // 兼容处理
        ];
        
        // 处理证件照片
        if (!empty($params['idCardPic1'])) {
            try {
                $data['idCardPic1'] = bin2hex(file_get_contents($params['idCardPic1']));
            } catch (\Exception $e) {
                file_put_contents('/tmp/sign_debug.log', date('Y-m-d H:i:s') . ' - idCardPic1读取错误: ' . $e->getMessage() . "\n", FILE_APPEND);
                return json(['code' => 400, 'msg' => 'idCardPic1读取错误: ' . $e->getMessage(), 'data' => null]);
            }
        }
        
        if (!empty($params['idCardPic2'])) {
            try {
                $data['idCardPic2'] = bin2hex(file_get_contents($params['idCardPic2']));
            } catch (\Exception $e) {
                file_put_contents('/tmp/sign_debug.log', date('Y-m-d H:i:s') . ' - idCardPic2读取错误: ' . $e->getMessage() . "\n", FILE_APPEND);
                return json(['code' => 400, 'msg' => 'idCardPic2读取错误: ' . $e->getMessage(), 'data' => null]);
            }
        }
        
        // 记录最终提交数据
        file_put_contents('/tmp/sign_debug.log', date('Y-m-d H:i:s') . ' - 提交数据: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

        try {
            // 构建签约请求，6010为签约接口的功能码
            $request = $this->httpUtils->buildRequest('6010', $data);
            
            // 发送请求到优付平台
            $response = $this->httpUtils->sendToYouFu($request);
            
            // 处理响应
            if ($response === false) {
                return json(['code' => 500, 'msg' => '签约请求失败：' , 'data' => null]);
            }
            
            // 解析并返回响应结果
            $result = json_decode($response, true);
            
            // 检查响应是否为空或解析失败
            if (empty($result)) {
                return json(['code' => 500, 'msg' => '签约响应解析失败：' . $response, 'data' => null]);
            }
            
            // 保存签约信息到数据库
            if ($result['resCode'] === '0000') {
                // 保存图片并获取URL
                $idCardPic1Url = $this->saveImageAndGetUrl($params['idCardPic1']);
                $idCardPic2Url = $this->saveImageAndGetUrl($params['idCardPic2']);
                
                $contractData = [
                    'user_id' => $params['user_id'], // 这里需要根据实际情况设置用户ID
                    'name' => $params['name'],
                    'id_card' => $params['idCard'],
                    'card_no' => $params['cardNo'],
                    'mobile' => $params['mobile'],
                    'id_card_pic1' => $idCardPic1Url,
                    'id_card_pic2' => $idCardPic2Url,
                    'mer_id' => $result['merId'] ?? '',
                    'status' => 1,
                    'remark' => '',
                    'time' => date('Y-m-d H:i:s')
                ];
                
                try {
                    // 先判断是否存在,只有当不存在时才插入
                    $exists = Db::connect('translate')->table('ts_user_management_contract')->where('user_id', $params['user_id'])->find();
                    if (!$exists) {
                        // 插入
                        Db::connect('translate')->table('ts_user_management_contract')->insert($contractData);
                    }
                    
                } catch (\Exception $e) {
                    // 记录数据库保存错误，但不影响接口返回
                    file_put_contents('/tmp/sign_debug.log', date('Y-m-d H:i:s') . ' - 数据库保存错误: ' . $e->getMessage() . "\n", FILE_APPEND);
                }
            }
            
            return json([
                'code' => $result['resCode'] === '0000' ? 200 : 500,
                'msg' => $result['resCode'] === '0000' ? '签约请求成功' : $result['resMsg']
            ]);
            
        } catch (\Exception $e) {
            // 异常处理
            return json(['code' => 500, 'msg' => '签约过程发生异常：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    /**
     * 查询签约状态
     * @return \think\response\Json
     */
    public function query()
    {
        // 获取请求数据
        $request = request();
        $params = $request->param();
        
        // 记录请求日志
        file_put_contents('/tmp/sign_query_debug.log', date('Y-m-d H:i:s') . ' - 请求参数: ' . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        // 必填参数验证
        $requiredFields = ['name', 'idCard', 'mobile'];
        foreach ($requiredFields as $field) {
            // 兼容处理大小写不同的参数名
            $altField = ucfirst($field); // 首字母大写的替代字段名
            if (empty($params[$field]) && empty($params[$altField])) {
                return json(['code' => 400, 'msg' => '缺少必填参数：' . $field, 'data' => null]);
            }
            
            // 如果参数在首字母大写的形式中存在，赋值给原始参数名
            if (empty($params[$field]) && !empty($params[$altField])) {
                $params[$field] = $params[$altField];
            }
        }
        
        // 构建请求参数
        $data = [
            'name' => $params['name'],
            'idCard' => $params['idCard'],
            'mobile' => $params['mobile'],
            'providerId' => '55020'
        ];
        
        file_put_contents('/tmp/sign_query_debug.log', date('Y-m-d H:i:s') . ' - 提交数据: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        try {
            // 构建查询请求，6021为查询签约状态的功能码
            $request = $this->httpUtils->buildRequest('6011', $data);
            
            // 发送请求到优付平台
            $response = $this->httpUtils->sendToYouFu($request);
            file_put_contents('/tmp/sign_query_debug.log', date('Y-m-d H:i:s') . ' - 响应数据: ' . $response . "\n", FILE_APPEND);
            
            // 处理响应
            if ($response === false) {
                return json(['code' => 500, 'msg' => '查询签约状态失败', 'data' => null]);
            }
            
            // 记录响应数据
            file_put_contents('/tmp/sign_query_debug.log', date('Y-m-d H:i:s') . ' - 响应数据: ' . $response . "\n", FILE_APPEND);
            
            // 解析并返回响应结果
            $result = json_decode($response, true);
            file_put_contents('/tmp/sign_query_debug.log', date('Y-m-d H:i:s') . ' - 响应数据: ' . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            
            if (!$result) {
                return json(['code' => 500, 'msg' => '响应数据解析失败', 'data' => null]);
            }
            
            // 标准化返回结果
            $responseData = null;
            if (isset($result['resCode']) && $result['resCode'] === '0000' && isset($result['resData'])) {
                $responseData = [
                    'name' => $result['resData']['name'] ?? $data['name'],
                    'idCard' => $result['resData']['idCard'] ?? $data['idCard'],
                    'mobile' => $result['resData']['mobile'] ?? $data['mobile'], 
                    'providerId' => $result['resData']['providerId'] ?? $data['providerId'],
                    'state' => isset($result['resData']['state']) ? intval($result['resData']['state']) : 0,
                    'cardNo' => $result['resData']['cardNo'] ?? '',
                    'retMsg' => $result['resData']['retMsg'] ?? '',
                    'otherParam' => $result['resData']['otherParam'] ?? ''
                ];
            }
            
            return json([
                'code' => $result['resCode'] === '0000' ? 200 : 500,
                'msg' => $result['resCode'] === '0000' ? '查询成功' : ($result['resMsg'] ?? '查询失败'),
                'data' => $responseData
            ]);
            
        } catch (\Exception $e) {
            // 异常处理
            file_put_contents('/tmp/sign_query_debug.log', date('Y-m-d H:i:s') . ' - 异常: ' . $e->getMessage() . "\n", FILE_APPEND);
            return json(['code' => 500, 'msg' => '查询签约状态发生异常：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    /**
     * 解约接口
     * 用户解除已有的签约关系
     * 功能码：6036
     * @return \think\response\Json
     */
    public function cancel()
    {
        // 获取请求数据
        $request = request();
        $params = $request->param();
        
        // 调试信息记录
        $logData = [
            'params' => $params
        ];
        file_put_contents('/tmp/sign_cancel_debug.log', date('Y-m-d H:i:s') . ' - ' . json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        // 必填参数验证
        $requiredFields = ['userName', 'idcardNo'];
        foreach ($requiredFields as $field) {
            // 兼容处理大小写不同的参数名
            $altField = ucfirst($field); // 首字母大写的替代字段名
            if (empty($params[$field]) && empty($params[$altField])) {
                return json(['code' => 400, 'msg' => '缺少必填参数：' . $field, 'data' => null]);
            }
            
            // 如果参数在首字母大写的形式中存在，赋值给原始参数名
            if (empty($params[$field]) && !empty($params[$altField])) {
                $params[$field] = $params[$altField];
            }
        }
        
        // 构建请求参数
        $data = [
            'userName' => $params['userName'],
            'idcardNo' => $params['idcardNo']
        ];
        
        // 处理可选参数 providerId
        if (!empty($params['providerId'])) {
            $data['providerId'] = $params['providerId'];
        } else if (!empty($params['ProviderId'])) {
            $data['providerId'] = $params['ProviderId'];
        }
        
        // 记录最终提交数据
        file_put_contents('/tmp/sign_cancel_debug.log', date('Y-m-d H:i:s') . ' - 提交数据: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        try {
            // 构建解约请求，6036为解约接口的功能码
            $request = $this->httpUtils->buildRequest('6036', $data);
            
            // 发送请求到优付平台
            $response = $this->httpUtils->sendToYouFu($request);
            
            // 处理响应
            if ($response === false) {
                return json(['code' => 500, 'msg' => '解约请求失败', 'data' => null]);
            }
            
            // 解析并返回响应结果
            $result = json_decode($response, true);
            
            return json([
                'code' => $result['resCode'] === '0000' ? 200 : 500,
                'msg' => $result['resCode'] === '0000' ? '解约请求成功' : $result['resMsg'],
                'data' => isset($result['resData']) ? $result['resData'] : null
            ]);
            
        } catch (\Exception $e) {
            // 异常处理
            return json(['code' => 500, 'msg' => '解约过程发生异常：' . $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * 通用透传接口
     * 直接透传参数给优付平台，通过JSON参数传入功能码和请求数据
     * @return \think\response\Json
     */
    public function proxy()
    {
        // 获取请求数据
        $request = request();
        $params = $request->param();
        
        // 调试信息记录
        $logData = [
            'params' => $params,
            'files' => $request->file()
        ];
        file_put_contents('/tmp/sign_proxy_debug.log', date('Y-m-d H:i:s') . ' - ' . json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        // 密码校验
        $requestPassword = isset($params['password']) ? $params['password'] : '';
        $correctPassword = 'TzNZ0jsi47F4Q6nUpH2h';
        
        if ($requestPassword !== $correctPassword) {
            return json(['code' => 403, 'msg' => '密码验证失败，无权访问', 'data' => null]);
        }
        
        // 必填参数验证
        if (empty($params['funCode'])) {
            return json(['code' => 400, 'msg' => '缺少必填参数：funCode', 'data' => null]);
        }
        
        if (empty($params['reqData'])) {
            return json(['code' => 400, 'msg' => '缺少必填参数：reqData', 'data' => null]);
        }
        
        // 获取功能码
        $funCode = $params['funCode'];
        
        // 获取请求数据
        $reqData = is_array($params['reqData']) ? $params['reqData'] : json_decode($params['reqData'], true);
        
        if (!$reqData) {
            return json(['code' => 400, 'msg' => 'reqData参数格式错误，应为有效的JSON', 'data' => null]);
        }
        
        // 文件上传处理
        $files = $request->file();
        
        // 检查是否有文件上传并处理
        if (!empty($files) && is_array($files)) {
            foreach ($files as $key => $file) {
                try {
                    // 如果reqData中有对应的参数，则将文件内容转换为十六进制字符串
                    if (isset($reqData[$key])) {
                        $reqData[$key] = bin2hex(file_get_contents($file->getPathname()));
                    }
                } catch (\Exception $e) {
                    file_put_contents('/tmp/sign_proxy_debug.log', date('Y-m-d H:i:s') . ' - 文件处理错误: ' . $e->getMessage() . "\n", FILE_APPEND);
                    return json(['code' => 400, 'msg' => '文件处理错误: ' . $e->getMessage(), 'data' => null]);
                }
            }
        }
        
        // 记录最终提交数据
        file_put_contents('/tmp/sign_proxy_debug.log', date('Y-m-d H:i:s') . ' - 提交数据: ' . json_encode(['funCode' => $funCode, 'reqData' => $reqData], JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        try {
            // 构建请求
            $request = $this->httpUtils->buildRequest($funCode, $reqData);
            
            // 发送请求到优付平台
            $response = $this->httpUtils->sendToYouFu($request);
            
            // 处理响应
            if ($response === false) {
                return json(['code' => 500, 'msg' => '请求失败', 'data' => null]);
            }
            
            // 记录响应数据
            file_put_contents('/tmp/sign_proxy_debug.log', date('Y-m-d H:i:s') . ' - 响应数据: ' . $response . "\n", FILE_APPEND);
            
            // 直接返回上级接口的响应内容，避免双重 JSON 编码
            return response($response, 200, ['Content-Type' => 'application/json; charset=utf-8']);
            
        } catch (\Exception $e) {
            // 异常处理
            file_put_contents('/tmp/sign_proxy_debug.log', date('Y-m-d H:i:s') . ' - 异常: ' . $e->getMessage() . "\n", FILE_APPEND);
            return json(['code' => 500, 'msg' => '请求过程发生异常：' . $e->getMessage(), 'data' => null]);
        }
    }
} 