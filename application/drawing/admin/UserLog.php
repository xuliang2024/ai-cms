<?php
//用户日志
namespace app\drawing\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class UserLog extends Admin {
	
    public function index() 
    {
        $order = $this->getOrder('time desc');
        $map = $this->getMap();
        
        $data_list = DB::table('ai_user_log')->where($map)
        ->order($order)
        ->paginate();

        cookie('ai_user_log', $map);
        
        $contro_right_btn = ['edit'];
        return ZBuilder::make('table')
            ->setTableName('user_log') // 设置数据表名
            ->addColumns([ // 批量添加列
                    // ['id', 'ID'],
                    ['dayid', 'dayid'],
                    ['tag','tag'],
                    ['title', 'title'],
                    ['user_id','用户'],
                    ['time', '创建时间'],
                   
            ])
            ->hideCheckbox()
            ->setSearchArea([  
                ['text', 'user_id', '用户', '', '', ''],
                ['daterange', 'dayid', '时间'],   
                ['text', 'title', 'title', '', '', ''],
                ['text', 'tag', 'tag', '', '', ''],
            ])
            ->addRightButtons($contro_right_btn) // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面

    }


  
}