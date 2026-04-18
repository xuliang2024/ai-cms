<?php
//用户信息
namespace app\ai\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class ChatList extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_chat_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_chat_list', $map);
        
        
        return ZBuilder::make('table')
            ->setTableName('chat_list') // 设置数据表名
            ->addColumns([ // 批量添加列
                    // ['id', 'ID'],
                    ['time', '时间'],
                    ['user_id', '用户ID'],
                    ['chat_id', '会话ID'],
                    ['role', '类型','status','',['user'=>'用户','assistant'=>'回答']],
                    ['content', '内容'],
                    ['role', '角色'],
                    ['right_button', '操作', 'btn'],
            ])
            ->hideCheckbox()
            ->setSearchArea([            
                ['text', 'user_id', '会话id'],
            ])
            ->setColumnWidth('content', 300)
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面

    }

  
}