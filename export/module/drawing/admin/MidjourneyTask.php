<?php
//绘画记录表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class MidjourneyTask extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_mj_task_infos')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_mj_task_infos', $map);
        
        return ZBuilder::make('table')
            ->setTableName('mj_task_infos') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id','用户ID'],
                ['task_id', '任务ID'],
                ['discordInstanceId', 'DCID'],
                ['imageUrl', '图片','img_url'],
                ['action', '动作'],
                ['status', '状态',['IN_PROGRESS' => '进行中', 'FAILURE' => '失败','SUCCESS'=>'完成','MODAL'=>'等待窗体','SUBMITTED'=>'提交','NOT_START'=>'等待']],
                ['progress', '进度'],
                ['promptEn', '提示词'],
                ['failReason', '失败'],

                ['time', '创建时间'],
                
            ])
            ->setSearchArea([
                ['daterange', 'time', '时间'],
                ['text', 'user_id', '用户ID'],
                ['text', 'discordInstanceId', 'dcid'],
                ['select', 'action', 'action', '', '', ['IMAGINE' => 'IMAGINE', 'VARIATION' => 'VARIATION', 'UPSCALE' => 'UPSCALE']],
                ['text', 'task_id', '任务ID'],
                
            
            ])


            // ->hideCheckbox()
             
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')

            ->fetch(); // 渲染页面

    }


    

 

  
}