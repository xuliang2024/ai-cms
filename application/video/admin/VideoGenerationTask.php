<?php
// 图生视频
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class VideoGenerationTask extends Admin {
    
    public function index() 
    {
     
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_video_generation_task')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_video_generation_task', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/VideoGenerationTaskModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', 'user_id'],
                ['cond_aug', 'cond_aug'],
                ['decoding_t', 'decoding_t'],
                ['input_image', 'input_image','img_url'],
                ['output', 'output','image_video'],
                ['video_length', 'video_length'],
                ['sizing_strategy', 'sizing_strategy'],
                ['status', '状态','text.edit'],
                ['logs', 'logs', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 50, '...');
                }],
                ['motion_bucket_id', 'motion_bucket_id'],
                ['frames_per_second', 'frames_per_second'],
                ['error', 'error'],
                ['task_id', 'task_id'],
                // ['logs', 'logs'],
                ['predict_time', 'predict_time'],
                
                ['started_at', 'started_at'],
                ['get_url', 'get_url'],
                ['cancel_url', 'cancel_url'],
                ['version', 'version'],
                ['time', '创建时间'],
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', 'user_id'],
                ['text', 'status', '状态'],
              
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}
