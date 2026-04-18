<?php
namespace app\video\home\bossky;

use think\Controller;
use think\Request;
require_once __DIR__.DIRECTORY_SEPARATOR."sby".DIRECTORY_SEPARATOR."HttpUtlis.php";

/**
 * 签约控制器
 * 提供签约相关的接口
 */
class Sign extends Controller
{
    private $httpUtils;
    
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
        $request = Request::instance();
        $params = $request->param();
        
        // 必填参数验证
        $requiredFields = ['name', 'idCard', 'CardNo', 'Mobile', 'ProviderId'];
        foreach ($requiredFields as $field) {
            if (empty($params[$field])) {
                return json(['code' => 400, 'msg' => '缺少必填参数：' . $field, 'data' => null]);
            }
        }
        
        // 构建请求参数
        $data = [
            'name' => $params['name'],
            'idCard' => $params['idCard'],
            'CardNo' => $params['CardNo'],
            'Mobile' => $params['Mobile'],
            'ProviderId' => $params['ProviderId'],
            'PaymentType' => isset($params['PaymentType']) ? $params['PaymentType'] : 0,
            'OtherParam' => isset($params['OtherParam']) ? $params['OtherParam'] : '',
        ];
        
        // 处理证件照片
        if (!empty($params['IdCardPic1'])) {
            // 如果是文件路径，则转换为二进制数据
            if (file_exists($params['IdCardPic1'])) {
                $data['IdCardPic1'] = bin2hex(file_get_contents($params['IdCardPic1']));
            } else {
                $data['IdCardPic1'] = $params['IdCardPic1']; // 直接使用传入的数据
            }
        }
        
        if (!empty($params['IdCardPic2'])) {
            // 如果是文件路径，则转换为二进制数据
            if (file_exists($params['IdCardPic2'])) {
                $data['IdCardPic2'] = bin2hex(file_get_contents($params['IdCardPic2']));
            } else {
                $data['IdCardPic2'] = $params['IdCardPic2']; // 直接使用传入的数据
            }
        }
        
        try {
            // 构建签约请求，6010为签约接口的功能码
            $request = $this->httpUtils->buildRequest('6010', $data);
            
            // 发送请求到优付平台
            $response = $this->httpUtils->sendToYouFu($request);
            
            // 处理响应
            if ($response === false) {
                return json(['code' => 500, 'msg' => '签约请求失败', 'data' => null]);
            }
            
            // 解析并返回响应结果
            $result = json_decode($response, true);
            
            return json([
                'code' => $result['resCode'] === '0000' ? 200 : 500,
                'msg' => $result['resCode'] === '0000' ? '签约请求成功' : $result['resMsg'],
                'data' => isset($result['resData']) ? $result['resData'] : null
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
        $request = Request::instance();
        $params = $request->param();
        
        // 必填参数验证
        if (empty($params['idCard']) || empty($params['ProviderId'])) {
            return json(['code' => 400, 'msg' => '缺少必填参数：身份证号或服务商ID', 'data' => null]);
        }
        
        // 构建请求参数
        $data = [
            'idCard' => $params['idCard'],
            'ProviderId' => $params['ProviderId']
        ];
        
        try {
            // 构建查询请求，6020为查询签约状态的功能码（假设）
            $request = $this->httpUtils->buildRequest('6020', $data);
            
            // 发送请求到优付平台
            $response = $this->httpUtils->sendToYouFu($request);
            
            // 处理响应
            if ($response === false) {
                return json(['code' => 500, 'msg' => '查询签约状态失败', 'data' => null]);
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
            return json(['code' => 500, 'msg' => '查询签约状态发生异常：' . $e->getMessage(), 'data' => null]);
        }
    }
} 