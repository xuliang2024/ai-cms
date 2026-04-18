<?php
// 提现记录
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\ExtractListModel;
class ExtractList extends Admin {
    
    public function index() 
    {
        // 查询账户余额
        $ch = curl_init('https://ai-cms.fyshark.com/index.php/api/Account/balance');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'providerId=55020');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $balance_info = json_decode($response, true);
        $balance = '未知';
        if ($balance_info && $balance_info['code'] == 200) {
            $balance = number_format($balance_info['data']['balance']/100, 2);
        }

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = ExtractListModel::where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_extract_list', $map);
        
        return ZBuilder::make('table')
            ->setPageTips("当前账户余额：{$balance} 元")  // 在表格上方显示余额
            ->setTableName('video/ExtractListModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', 'user_id'],
                ['mer_order_dd', 'mer_order_dd'],
                ['mer_order_id', 'mer_order_id'],
                ['order_no', 'order_no'],
                ['money', '提现金额（元）', 'callback', function($value){ return number_format($value/100, 2); }],
                ['command', '备注信息'],
                ['status', '状态','status','',[0=>'等待审核',1=>'通过',2=>'拒绝']],
                ['type_manual', '数据类型','status','',[0=>'真实数据',1=>'假数据']],
                ['status', '状态','text.edit'],
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', 'user_id'],
                ['text', 'status', '状态'],
                ['select', 'status', '状态','', '',[0=>'等待审核',1=>'通过',2=>'拒绝']],
                ['select', 'type_manual', '数据类型','', '',[0=>'真实数据',1=>'假数据']],
              
            ])
            ->addTopButton('add',['title'=>'新增提现记录']) // 批量添加顶部按钮
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->replaceRightButton(['status' => ['<>',0]], '', ['edit'])
            ->replaceRightButton(['status' => ['<>',0]], '', ['custom'])
            ->replaceRightButton(['status' => ['<>',0]], '', ['remove'])
            ->addRightButton('custom',[
                'title'=>'通过并付款',
                'icon'=>'fa fa-fw fa-check',
                'class'=>'btn btn-success btn-rounded',
                'href'=>url('video/extract_list/approve',['id'=>'__id__']),
            ],false, ['style'=>'primary','title' => false,'icon'=>true])

            ->addRightButton('remove',[
                'title'=>'拒绝并写入原因',
                'icon'=>'fa fa-fw fa-remove',
                'class'=>'btn btn-danger btn-rounded',
                'href'=>url('video/extract_list/remove',['id'=>'__id__']),
            ],true, ['style'=>'primary','title' => false,'icon'=>true])


            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }


     public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            $data['type_manual'] = 1;#新增的都是假数据
            $r = DB::connect('translate')->table('ts_extract_list')->insert($data);
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
                ['number', 'money', '提现金额（单位：分）'],
                ['select', 'status', '状态','',[1=>'提现通过',2=>'提现拒绝'],1],          
                ['text','command','拒绝提现说明/备注','','付款通过'],
                ['datetime', 'time', '创建时间', '', 'YYYY-MM-DD HH:mm:ss'],

            ])
            // ->addOssVideo('video_url','视频链接','')
            // ->addOssImage('url','图片链接','')
            
            ->fetch();
    }


 public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // $info = DB::table('book_extract_list')->where('id',$id)->find();

        $info = DB::connect('translate')->table('ts_extract_list')->where('id',$id)->find();
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            if ($data["status"] == 2) {//拒绝提现
            
            DB::connect('translate')->table('ts_users')->where('id',$info["user_id"])->inc('translate_may', $info["money"])->update();   

            }


            if ($data["status"] == 1) {//同意提现
            
                DB::connect('translate')->table('ts_users')->where('id',$info["user_id"])->inc('translate', $info["money"])->update(); 
            }    

            // $r = DB::table('book_extract_list')->where('id',$id)->update($data);
            $r = DB::connect('translate')->table('ts_extract_list')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        

        return ZBuilder::make('form')
              
             ->addFormItems([
              
                ['select', 'status', '状态','',[1=>'提现通过',2=>'提现拒绝']],          
                ['text','command','拒绝提现说明/备注'],

            ])
          
            ->setFormData($info)
            ->fetch();
    }

    public function approve($id = null)
    {
        if ($id === null) {
            $this->error('缺少参数');
        }

        $info = DB::connect('translate')->table('ts_extract_list')->where('id',$id)->where('status', 0)->find();
        if (!$info) {
            $this->error('提现记录不存在或已处理');
        }

        // 获取用户信息
        $user_info = DB::connect('translate')->table('ts_users')->where('id', $info['user_id'])->find();
        if (!$user_info) {
            $this->error('用户不存在');
        }

        // 获取用户银行卡信息
        $bank_info = DB::connect('translate')->table('ts_user_management_contract')
            ->where('user_id', $info['user_id'])
            ->where('status', 1)
            ->order('time desc')
            ->find();
            
        if (!$bank_info) {
            $this->error('用户未绑定银行卡信息');
        }

        // 构建付款请求参数
        $params = [
            'merBatchId' => md5(uniqid(mt_rand(), true)),
            'payItems' => [
                [
                    'merOrderId' => md5(uniqid(mt_rand(), true)),
                    'amt' => (int)($info['money'] * 0.94),
                    'payeeName' => $bank_info['name'],
                    'payeeAcc' => $bank_info['card_no'],
                    'idCard' => $bank_info['id_card'],
                    'mobile' => $bank_info['mobile'],
                    'paymentType' => 0, // 0表示银行卡
                    'memo' => '推广服务'
                ]
            ],
            'taskId' => '1610278112283460589',
            'providerId' => 55020
        ];

        // 发送付款请求
        $ch = curl_init('https://ai-cms.fyshark.com/index.php/api/batch_pay/submit');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        
        if ($result && $result['code'] == 200) {
            // 更新提现记录状态
            DB::connect('translate')->table('ts_extract_list')->where('id', $id)->update([
                'mer_order_id' => $result['data']['payResultList'][0]['merOrderId'],
                'order_no' => $result['data']['payResultList'][0]['orderNo'],
                'status' => 1,
                'command' => '付款成功'
            ]);
            
            $this->success('付款成功', 'index');
        } else {
            $this->error('付款失败：' . ($result['msg'] ?? '未知错误'));
        }
    }



    public function remove($id = null)
    {
            $info = DB::connect('translate')->table('ts_extract_list')->where('id',$id)->find();

            if (!$info) {
                $this->error('数据不存在');
            }

            // print_r('日志如下');
            // print_r($info["user_id"]);
            // print_r($info["money"]);

            // die();
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            
            // DB::connect('translate')->table('ts_users')->where('id',$info["user_id"])->inc('money', $info["money"])->update();  

            $data_fin["user_id"]= $info["user_id"];
            $data_fin["money"]= $info["money"];
            $data_fin["order_id"]= "extract_reject_" . $id;
            $data_fin["title"]= "提现拒绝，金额退回";
            $fin=DB::connect('translate')->table('ts_financial_transactions')->insert($data_fin);#用户余额记录
            if (!$fin) {
                $this->error('退回金额操作失败');
            }


            $data["status"] = 2;#拒绝提现
            $r = DB::connect('translate')->table('ts_extract_list')->where('id',$id)->update($data);
            if ($r) {
                // $this->success('已经拒绝提现', 'index');
                // $this->success('已经拒绝提现', null, ['_close_pop' => 1]);
                $this->success('已经拒绝提现', null, '_parent_reload');
            } else {
                $this->error('操作失败');
            }
        }


        return ZBuilder::make('form')
              
             ->addFormItems([
                      
                ['textarea','command','拒绝提现说明'],

            ])
          
            ->setFormData($info)
            ->fetch();
    }


}

