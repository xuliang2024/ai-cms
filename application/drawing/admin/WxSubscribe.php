<?php
//绘画记录表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class WxSubscribe extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_subscribe_template_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_subscribe_template_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('subscribe_template_list') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户id'],
                ['task_id', '任务id'],
                ['template_id','订阅id'],
                ['openid','标题'],
                ['status','状态','switch'],
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
                
            ])
            // ->hideCheckbox()
            
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButtons(['add','delete'])
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->fetch(); // 渲染页面

    }


    
 

  
}