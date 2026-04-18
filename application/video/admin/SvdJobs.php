<?php
// 用户任务制作同步jobs
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class SvdJobs extends Admin {
    
    public function index() 
    {
     
        
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_svd_jobs')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_svd_jobs', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/SvdJobsModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['task_id', '任务ID'],
                ['collection_id', '合集ID'],
                ['image_url', 'image_url','img_url'],
                ['video_url','视频链接','image_video'],
                
                ['time', '创建时间'],
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'task_id', '任务ID'],
                ['text', 'collection_id', '合集ID'],
                
              
            ])
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}
