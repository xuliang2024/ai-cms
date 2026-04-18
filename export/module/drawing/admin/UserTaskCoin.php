<?php
//金币领取统计
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class UserTaskCoin extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        $map[]=["p_user_id","=",get_pid()];
        $data_list = DB::table('ai_user_task_coin')->where($map)
        ->order('user_id desc')
        ->paginate();
        
        cookie('ai_user_task_coin', $map);
        
        
        return ZBuilder::make('table')
            ->setTableName('user_task_coin') // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['dayid', '日期'],
                    ['user_id', '用户ID'],
                    ['task_id', '任务ID'],
                    ['channel_name', '渠道名字'],
                    ['source_name', '推广标识'],
                    ['coin', '派发金币数量'],
                    ['title', '标题'],
                    ['appid', 'appid'],
                    ['time', '时间'],      
                    // ['right_button', '操作', 'btn'],
            ])
            ->hideCheckbox()
            //->addTopButton('add',['title'=>'新增'])
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->setRowList($data_list) // 设置表格数据
            ->setSearchArea([
                ['daterange', 'dayid', '时间'],   
                ['text', 'channel_name', '渠道商'],   
                ['text', 'source_name', '推广ID'],   
                ['text', 'user_id', '用户ID'],   
                ['text', 'task_id', '任务ID'],   
                ['text', 'title', '任务标题'],   
            ])
            ->setHeight('auto')
            ->fetch(); // 渲染页面

    }


   
  
}