<?php
// l用户绑定mj账号表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class MjAccount extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_user_mj_account')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_user_mj_account', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/MjAccountModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', 'user_id'],
                ['user_type', '用户类型','status','',[0=>'速推',1=>'网页']],
                ['channelId', '通道ID','text.edit'],
                ['instance_id', 'mj_ID'],
                ['status', '状态','text.edit'],
                ['status', '状态','status','',[0=>'添加',1=>'在线',3=>'失败',4=>'继续提交',5=>'更改参数',6=>'更改参数继续提交']],

                ['guild_id', '服务器ID'],
                ['core_size', '通道并发','text.edit'],
                ['queue_size', '排队队列大小'],
                ['timeout_minutes', '超时时间'],
                ['remix_auto_submit', '自动提交'],
                ['remark', '备注'],
                ['user_token', '用户token'],
                ['mj_bot_channel_id', '通用机器人id'],
                ['niji_bot_channel_id', 'niji机器人id'],
                ['nijiMode', 'nijimode'],
                ['user_agent', 'UA'],
                ['fail_reason', '失败原因'],
                ['fail_cnt', '失败次数'],
                ['time', '创建时间'],
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', 'user_id'],
                ['text', 'user_token', 'user_token'],
                ['select', 'user_type', '用户类型', '', '', ['0'=>'速推','1'=>'网页']],
                ['select', 'status', '状态', '', '', ['0'=>'添加','1'=>'在线','3'=>'失败','4'=>'继续提交','5'=>'更改参数','6'=>'更改参数继续提交']],
                ['text', 'channelId', '通道ID'],
                ['text', 'guild_id', '服务器ID'],
                ['text', 'core_size', '通道并发'],
                ['text', 'user_token', '用户token'],
                ['text', 'nijiMode', 'nijiMode'],
              
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}
