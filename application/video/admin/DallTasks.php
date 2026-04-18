<?php
// dall3绘画列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class DallTasks extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_dall_3_tasks')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_dall_3_tasks', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/DallTasksModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['user_id','user_id'],
                    ['task_id','task_id'],
                    ['prompt', 'prompt'],
                    ['model','model'],
                    
                    ['style', 'style'],
                    ['image_url', '图片','img_url'],
                    ['image_width', '宽度'],
                    ['image_height', '高度'],
                    ['quality','质量'],
                    ['status', '状态','status','',[0=>'等待中',1=>'处理中',2=>'完成',3=>'失败']],
                    ['time','时间'],
                    
            ])
           
            ->setSearchArea([  
                ['text', 'user_id', '用户id'],
                ['text', 'task_id', 'task_id'],
                ['daterange', 'time', '时间'],   
                ['select', 'status', '状态', '', '', [0=>'等待中',1=>'处理中',2=>'完成',3=>'失败']],
                            
               
            ])
           
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }


   



}
