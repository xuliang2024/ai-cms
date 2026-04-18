<?php
//点券消耗明细
namespace app\drawing\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class WalletInfo extends Admin {
	
    public function index() 
    {
        
        
        $map = $this->getMap();
        $data_list = DB::table('ai_wallet_info')->where($map)
        ->order('time desc')
        ->paginate(); 

        cookie('ai_wallet_info', $map);
        
        return ZBuilder::make('table')
            // ->setPageTitle('用户管理') // 设置页面标题
            ->setTableName('wallet_info') // 设置数据表名
            // ->setSearch(['id' => 'ID', 'username' => '用户名', 'email' => '邮箱']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['user_id','用户id'],
                    ['album_id', '视频id'],
                    ['order_id', '订单号'],
                    ['title', '标题'],
                    ['pay_money', '支付金额分'],
                    ['coin', '金币'],
                    ['status', '状态'],
                    ['type', '类型'],
                    ['time', '创建时间'],
                    ['right_button', '操作', 'btn']
                    
            ])
            ->addRightButton('edit',[
                'title'=>'修改',
                'class'=>'btn btn-success btn-rounded',
            ],false,['style'=>'primary','title' => true,'icon'=>false]) // 批量添加右侧按钮
              
            ->setSearchArea([
                ['daterange', 'time', '日期', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', '用户id'],
                ['text', 'album_id', '视频id'],
                ['text', 'order_id', '订单号'],
                ['text', 'title', '标题'],
                ['text', 'status', '状态'],
               
            ])
            
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面

    }

    
    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $r = DB::table('ai_wallet_info')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::table('ai_wallet_info')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addFormItems([
                ['text', 'coin', '金币'],

            ])
          
           
            ->setFormData($info)
            ->fetch();
    }



  
}