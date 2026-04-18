<?php
// mj转移后的矢量表2
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class MjImageMaterial2 extends Admin {
    
    public function index() 
    {
     
        
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_mj_image_material_v2')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_mj_image_material_v2', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/MjImageMaterial2Model',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', 'user_id'],
                ['mj_jobs_id', 'mj任务表ID'],
                ['task_id', '任务ID'],
                ['from_task_id', '变体任务ID'],
                ['collection_id', '合集ID'],
                ['discordInstanceId', '通道ID'],
                
                ['action', 'action'],
                ['status', 'status','text.edit'],
                // ['prompt', 'prompt', 'callback', function($source_text) {
                //     // 限制字符串长度为50个字符
                //     return mb_strimwidth($source_text, 0, 20, '...');
                // }],
                ['prompt', 'prompt', 'callback', function ($val, $data) {
                    $val = sprintf(self::$ellipsisElement, $val, $val);
                    return $val;
                    }, '__data__'],
                ['description', 'description', 'callback', function ($val, $data) {
                    $val = sprintf(self::$ellipsisElement, $val, $val);
                    return $val;
                    }, '__data__'],

                // ['description', 'description', 'callback', function($source_text) {
                //     // 限制字符串长度为50个字符
                //     return mb_strimwidth($source_text, 0, 20, '...');
                // }],

                ['submit_time', 'submitTime'],

                ['start_time', 'startTime'],
                ['finish_time', 'finishTime'],
                ['progress', 'progress'],

                ['image_url', 'image_url','img_url'],

                ['fail_reason', 'failReason'],
                
                ['time', '创建时间'],
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                // ['text', 'user_id', 'user_id'],
                ['select', 'user_type', '用户类型', '', '', ['0'=>'速推','1'=>'网页']],
                ['text', 'id', 'ID'],
                ['text', 'mj_jobs_id', 'mj任务表ID'],
                ['text', 'task_id', '任务ID'],
                ['text', 'from_task_id', '变体任务ID'],
                ['text', 'collection_id', '合集ID'],
                ['text', 'discordInstanceId', '通道ID'],
                ['text', 'action', 'action'],
                ['text', 'status', 'status'],
              
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}
