<?php
// 用户任务制作同步jobs
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class MjJobs extends Admin {
    
    public function index() 
    {
     
        
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_user_mj_jobs')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_user_mj_jobs', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/MjJobsModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['task_id', '任务ID'],
                ['from_task_id', '变体任务ID'],
                ['collection_id', '合集ID'],
                ['discordInstanceId', '通道ID'],
                
                ['action', 'action'],
                ['status', 'status','text.edit'],
                ['prompt', 'prompt', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 20, '...');
                }],
                ['description', 'description', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 20, '...');
                }],

                ['submit_time', 'submitTime'],

                ['start_time', 'startTime'],
                ['finish_time', 'finishTime'],
                ['progress', 'progress'],

                ['image_url', 'image_url','img_url'],
                ['image_url', 'image_url'],

                ['fail_reason', 'failReason'],
                
                ['time', '创建时间'],
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '日期选择', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'time', '时间选择', '', '', ['format' => 'YYYY-MM-DD HH:mm']],
                // ['text', 'user_id', 'user_id'],
                ['select', 'user_type', '用户类型', '', '', ['0'=>'速推','1'=>'网页']],
                ['text', 'id', 'ID'],
                ['text', 'task_id', '任务ID'],
                ['text', 'from_task_id', '变体任务ID'],
                ['text', 'collection_id', '合集ID'],
                ['text', 'discordInstanceId', '通道ID'],
                ['text', 'action', 'action'],
                ['text', 'status', 'status'],
              
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
             ->addTopButton('download', [
                'title' => '导出数据',
                'class' => 'btn btn-primary js-get',
                'icon' => 'fa fa-fw fa-file-excel-o',
                'href' => '/admin.php/video/mj_jobs/download.html?' . $this->request->query()
                // 'href'  => url('download',['pid' => '__id__'])
            ])
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

            //下载
    public function download() {
        

        // $map = $this->getMap();
        
        // $data_list = DB::table('ai_activation_code_info')->where($map)->select();

         // 获取ids参数
    $ids = input('get.ids');
    // 将ids字符串分割为数组
    $ids_array = explode(',', $ids);
    
    // 查询数据库
    $data_list = DB::connect('translate')->table('ts_user_mj_jobs')->whereIn('id', $ids_array)->select();
    

        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            
            ['image_url', 100, 'image_url'],
            
            
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['导出图片数据'.date('Y-m-d H:i:s'), $cellName, $data_list]);

       

    }

}
