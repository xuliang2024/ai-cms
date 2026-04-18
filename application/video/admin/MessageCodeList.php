<?php
// 验证码列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class MessageCodeList extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_message_code_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_message_code_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/MessageCodeListModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', 'user_id'],
                ['ip_address', 'ip_address'],
                ['status', '状态','status','',[0=>'已发送',1=>'已验证']],
                ['user_type', '类型','status','',[0=>'速推',1=>'网页']],
                ['phone', 'phone'],
                ['code', '验证码'],
                ['code_type', '状态','status','',[0=>'注册',1=>'登录',2=>'找回密码',3=>'更改手机号']],
                ['expiration_time', '过期时间'],
                ['time', '创建时间'],
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'expiration_time', '过期时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', 'user_id'],
                ['text', 'ip_address', 'ip_address'],
                ['text', 'phone', 'phone'],
                ['select', 'status', '状态', '', '', ['0'=>'已发送','1'=>'已验证']],
              
            ])
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}

