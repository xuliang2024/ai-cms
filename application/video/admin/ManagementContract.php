<?php
// 管理合同表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;
use app\video\model\ManagementContractModel;
use app\video\model\ExtractListModel;


class ManagementContract extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = ManagementContractModel::where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_user_management_contract', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/ManagementContractModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID' , 'text.edit'],
                ['name', '姓名', 'text.edit'],
                ['id_card', '身份证号'],
                ['card_no', '银行卡号'],
                ['mobile', '手机号'],
                ['id_card_pic1', '身份证正面', 'img_url'],
                ['id_card_pic2', '身份证反面', 'img_url'],
                ['mer_id', '商户ID'],
                ['status', '状态', 'text.edit'],
                ['remark', '备注'],
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'user_id', '用户ID'],
                ['text', 'name', '姓名'],
                ['text', 'id_card', '身份证号'],
                ['text', 'mobile', '手机号'],
                ['text', 'mer_id', '商户ID'],
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->addTopButton('delete',['title'=>'删除本地用户']) // 批量添加顶部按钮
            ->addTopButton('add',['title'=>'手动添加签约用户'])
            ->addRightButton('edit',[
                'title' => '编辑',
                'icon' => 'fa fa-fw fa-edit',
                'href' => url('edit', ['id' => '__id__']),
                'class' => 'btn  btn-primary btn-square'
            ],false,['style'=>'primary','title' => true,'icon'=>false]) //
            ->addRightButton('custom', [
                'title' => '用户签约',
                'icon' => 'fa fa-fw fa-check',
                'href' => url('user_sign', ['id' => '__id__']),
                'class' => 'btn  btn-success ajax-get confirm'
            ],false,['style'=>'primary','title' => true,'icon'=>false])
            
            ->addRightButton('custom', [
                'title' => '用户解约',
                'icon' => 'fa fa-fw fa-close',
                'href' => url('user_cancel', ['id' => '__id__']),
                'class' => 'btn  btn-danger ajax-get confirm'
            ],false,['style'=>'primary','title' => true,'icon'=>false])

            // 查询签约状态
            ->addRightButton('custom', [
                'title' => '刷新签约状态',
                'icon' => 'fa fa-search',
                'href' => url('querySignStatus', ['id' => '__id__']),
                'class' => 'btn  btn-info ajax-get confirm'
            ],false,['style'=>'primary','title' => true,'icon'=>false])
            
            ->addRightButton('withdraw',[
                'title' => '手动提现',
                'icon'  => 'fa fa-fw fa-key',
                'class' => 'btn btn-warning btn-square',
                'href'  => url('video/management_contract/withdraw',['user_id'=>'__user_id__'])
            ],false,['style'=>'primary','title' => true,'icon'=>false])

            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
    
    public function withdraw($user_id = null)
    {
        
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $data['user_id'] = $user_id;
            $data['time'] = date('Y-m-d H:i:s');
            $r = ExtractListModel::create($data);
            if ($r) {
                // 记录行为
                $this->success('新增成功', url('/video/extract_list/index'));
                exit;
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addStatic('user_id', '用户ID：'.$user_id)
            ->addFormItems([
                ['number', 'money', '提现金额（单位：分）'],
                ['textarea', 'command', '备注信息'],
            ])
            ->fetch();
    }
    
    public function add() 
    {


       // 保存数据
       if ($this->request->isPost()) {
           // 表单数据
           $data = $this->request->post();

           $data['mer_id'] = '1742537268147748';
           $data['status'] = 0;
           $data['time'] = date('Y-m-d H:i:s');
           $r = ManagementContractModel::create($data);
           
           if ($r) {
               $this->success('新增成功', 'index');
           } else {
               $this->error('新增失败');
           }
       }
       // 显示添加页面
       return ZBuilder::make('form')
           ->addFormItems([
               ['text', 'user_id', '用户ID'],
               ['text', 'name', '姓名'],
               ['text', 'id_card', '身份证号'],
               ['text', 'card_no', '银行卡号'],
               ['text', 'mobile', '手机号'],
           ])
           ->addOssImage('id_card_pic1', '身份证正面', '', '', '', '', '', ['size' => '50,50'])
           ->addOssImage('id_card_pic2', '身份证反面', '', '', '', '', '', ['size' => '50,50'])
           ->fetch();
   }


   public function edit($id = null)
   {
       if ($id === null) $this->error('缺少参数');

       // 保存数据
       if ($this->request->isPost()) {
           // 表单数据
           $data = $this->request->post();

           $r = ManagementContractModel::where('id', $id)->update($data);
           if ($r) {
               $this->success('编辑成功', 'index');
           } else {
               $this->error('编辑失败');
           }
       }

       $info = ManagementContractModel::where('id', $id)->find();

       return ZBuilder::make('form')
       ->addFormItems([
        ['text', 'user_id', '用户ID'],
        ['text', 'name', '姓名'],
        ['text', 'id_card', '身份证号'],
        ['text', 'card_no', '银行卡号'],
        ['text', 'mobile', '手机号'],
    ])
    ->addOssImage('id_card_pic1', '身份证正面', '', '', '', '', '', ['size' => '50,50'])
    ->addOssImage('id_card_pic2', '身份证反面', '', '', '', '', '', ['size' => '50,50'])
           ->setFormData($info)
           ->fetch();
   }

    // 用户签约
    public function user_sign($id = null)
    {
        if ($id === null) {
            return $this->error('参数错误');
        }
        
        // 获取签约记录信息
        $contract = ManagementContractModel::get($id);
        if (!$contract) {
            return $this->error('签约记录不存在');
        }
        
        // 构建请求URL
        $apiUrl = 'https://ai-cms.fyshark.com/index.php/api/sign/submit';
        
        try {
            // 准备图片文件
            $files = [];
            
            // 处理身份证正面照片
            if (!empty($contract['id_card_pic1'])) {
                $pic1Content = file_get_contents($contract['id_card_pic1']);
                if ($pic1Content !== false) {
                    $tempFile1 = tempnam(sys_get_temp_dir(), 'idcard1_');
                    file_put_contents($tempFile1, $pic1Content);
                    $files['idCardPic1'] = new \CURLFile($tempFile1, 'image/jpeg', 'idcard1.jpg');
                }
            }
            
            // 处理身份证反面照片
            if (!empty($contract['id_card_pic2'])) {
                $pic2Content = file_get_contents($contract['id_card_pic2']);
                if ($pic2Content !== false) {
                    $tempFile2 = tempnam(sys_get_temp_dir(), 'idcard2_');
                    file_put_contents($tempFile2, $pic2Content);
                    $files['idCardPic2'] = new \CURLFile($tempFile2, 'image/jpeg', 'idcard2.jpg');
                }
            }
    
            // 构建请求参数
            $data = [
                'user_id' => $contract['user_id'],
                'name' => $contract['name'],
                'idCard' => $contract['id_card'],
                'CardNo' => $contract['card_no'],
                'Mobile' => $contract['mobile'],
                'ProviderId' => '55020',  // 默认值
                'PaymentType' => '0'       // 默认值
            ];
            
            // 合并数据和文件
            $postData = array_merge($data, $files);
            
            // 使用curl发送请求
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            
            // 清理临时文件
            if (isset($tempFile1)) {
                unlink($tempFile1);
            }
            if (isset($tempFile2)) {
                unlink($tempFile2);
            }
            
            if ($error) {
                return $this->error('请求失败：' . $error);
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['code']) && $result['code'] == 200) {
                // 更新合同状态
                ManagementContractModel::where('id', $id)->update([
                    'status' => 1,  // 已签约
                    'remark' => '手动提交签约成功'
                ]);
                return $this->success('签约提交成功');
            } else {
                return $this->error('签约失败：' . ($result['msg'] ?? '未知错误'));
            }
            
        } catch (\think\exception\HttpResponseException $e) { // 新增：专门捕获框架用于HTTP响应的异常
            // HTTP响应异常（由 $this->success 或 $this->error 抛出），直接重新抛出，由框架处理
            throw $e; // 关键：重新抛出，让框架接管
        }  catch (\Exception $e) {
            return $this->error('签约异常：' . $e->getMessage());
        }
    }


    // 用户解约
public function user_cancel($id = null)
{
    if ($id === null) {
        return $this->error('参数错误');
    }
    
    // 获取签约记录信息
    $contract = ManagementContractModel::get($id);
    if (!$contract) {
        return $this->error('签约记录不存在');
    }
    
    // 构建请求URL
    $apiUrl = 'https://ai-cms.fyshark.com/index.php/api/sign/cancel';
    
    try {
        // 构建请求参数
        $params = [
            'userName' => $contract['name'],
            // 'userName' => '刘毅',
            'idcardNo' => $contract['id_card']
            // 'idcardNo' => '411202199211152517'
            // providerId 可选，接口会使用默认值
        ];
        
        // 使用curl发送请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return $this->error('请求失败：' . $error);
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['code']) && $result['code'] == 200) {

            
            // 更新合同状态
            try {
                ManagementContractModel::where('id', $id)->update([
                    'status' => 5,  // 已解约
                    'remark' => $result['data']['retMsg'] ?? '手动解约成功'  // 使用API返回的消息
                ]);
                
                return $this->success('解约提交成功：' . ($result['data']['retMsg'] ?? ''));
            } catch (\think\Exception\DbException $e) {
                
                return $this->error('数据库异常：' . $e->getMessage());
            }
        } else {
            
            return $this->error('解约失败：' . ($result['msg'] ?? '未知错误'));
        }
        
    } catch (\think\exception\HttpResponseException $e) { // 新增：专门捕获框架用于HTTP响应的异常
        // HTTP响应异常（由 $this->success 或 $this->error 抛出），直接重新抛出，由框架处理
        throw $e; // 关键：重新抛出，让框架接管
    } catch (\Exception $e) {
        return $this->error('解约异常：' . $e->getMessage());
    }
}


    /**
     * 查询签约状态
     * @param int $id 记录ID
     * @return mixed
     */
    public function querySignStatus($id = null)
    {
        if ($id === null) {
            return $this->error('参数错误');
        }
        
        // 获取签约记录信息
        $contract = ManagementContractModel::get($id);
        if (!$contract) {
            return $this->error('签约记录不存在');
        }
        
        // 构建请求参数
        $params = [
            'name' => $contract['name'],
            'idCard' => $contract['id_card'],
            'mobile' => $contract['mobile']
            // 55020
        ];
        
        // 调用API进行查询
        $apiUrl = 'https://ai-cms.fyshark.com/index.php/api/sign/query';
        
        try {
            // 使用curl发送请求
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return $this->error('请求失败：' . $error);
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['code']) && $result['code'] == 200 && isset($result['data'])) {
                // 更新合同状态
                $statusMap = [
                    0 => '未签约',
                    1 => '已签约',
                    2 => '未检索到个体工商业者信息',
                    3 => '签约中',
                    4 => '签约失败',
                    5 => '已解约'
                ];
                
                // 更新数据
                $updateData = [
                    'status' => isset($result['data']['state']) ? $result['data']['state'] : $contract['status'],
                    'remark' => isset($result['data']['retMsg']) ? $result['data']['retMsg'] : $contract['remark']
                ];
                
                if (isset($result['data']['cardNo']) && !empty($result['data']['cardNo'])) {
                    $updateData['card_no'] = $result['data']['cardNo'];
                }
                
                // 记录日志
                $logInfo = '签约记录ID:'.$id.'，查询结果：'.json_encode($result['data'], JSON_UNESCAPED_UNICODE).'，更新数据：'.json_encode($updateData, JSON_UNESCAPED_UNICODE);
                file_put_contents('/tmp/contract_query.log', date('Y-m-d H:i:s') . ' - ' . $logInfo . "\n", FILE_APPEND);
                
                ManagementContractModel::where('id', $id)->update($updateData);
                
                $statusText = isset($statusMap[$updateData['status']]) ? $statusMap[$updateData['status']] : '未知状态';
                return $this->success('查询成功，签约状态：' . $statusText . '，信息：' . $updateData['remark']);
            } else {
                return $this->error('查询失败：' . ($result['msg'] ?? '未知错误'));
            }
        }  catch (\think\exception\HttpResponseException $e) { // 新增：专门捕获框架用于HTTP响应的异常
            // HTTP响应异常（由 $this->success 或 $this->error 抛出），直接重新抛出，由框架处理
            throw $e; // 关键：重新抛出，让框架接管
        } catch (\Exception $e) {
            return $this->error('查询异常：' . $e->getMessage());
        }
    }
}
