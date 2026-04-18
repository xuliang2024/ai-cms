<?php
// mj用户状态表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class MjWorkStatus extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_user_work_status')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_user_work_status', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/MjWorkStatusModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', 'user_id'],
                ['user_type', '用户类型','status','',[0=>'速推',1=>'网页']],
                ['work_cnt', '作业中数量','text.edit'],
                ['waiting_cnt', '等待中数量','text.edit'],
                
                ['time', '创建时间'],
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', 'user_id'],
                ['select', 'user_type', '用户类型', '', '', ['0'=>'速推','1'=>'网页']],
              
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}
