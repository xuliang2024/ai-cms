<?php
// 托管账号表格
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class ManagedAccounts extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_managed_accounts')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_managed_accounts', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/ManagedAccountsModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['account_name', '账户名称'],
                ['user_id', 'user_id'],
                ['name', '名字'],
                ['name_fs', '粉丝'],
                ['platform', '类型','status','',[1=>'抖音',2=>'快手',3=>'小红书',4=>'视频号',5=>'B站']],
                
                // ['login_info', '登录信息'],
                ['login_info', '登录信息', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 50, '...');
                }],
                
                ['auth_time', '授权时间'],
                
                // ['status', '状态','status','',[0=>'等待',1=>'处理',2=>'完成',3=>'失败']],
                ['status', '状态','text.edit'],
               
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
           
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', 'user_id'],
                ['text', 'status', '状态'],
                ['select', 'platform', '类型', '', '', ['1'=>'抖音','2'=>'快手','3'=>'小红书','4'=>'视频号','5'=>'B站']],
              
            ])
            ->addTopButton('add')
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }


    public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            $r = DB::connect('translate')->table('ts_managed_accounts')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')

            ->addFormItems([
                ['text', 'user_id', 'user_id'],
                ['select', 'platform', '平台', '', ['1' => '抖音', '2' => '快手', '3' => '小红书', '4' => '视频号', '5' => 'B站']],
                ['textarea', 'login_info', 'login_info'],
            ])
            ->fetch();
    }


    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $r = DB::connect('translate')->table('ts_managed_accounts')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_managed_accounts')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addFormItems([
                ['text', 'user_id', 'user_id'],
                ['select', 'platform', '平台', '', ['1' => '抖音', '2' => '快手', '3' => '小红书', '4' => '视频号', '5' => 'B站']],
                ['textarea', 'login_info', 'login_info'],
            ])
          
            ->setFormData($info)
            ->fetch();
    }




}
