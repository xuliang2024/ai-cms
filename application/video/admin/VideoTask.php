<?php
// lora模型列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;

class VideoTask extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_video_task')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_video_task', $map);
        
        return ZBuilder::make('table')
            // ->setTableName('video_task') // 设置数据表名
            ->setTableName('video/VideoModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', 'user_id'],
                ['title', 'title'],
                ['video_url', '视频','image_video'],
                ['video_sec', '时长'],
                ['finish_url', '输出视频','image_video'],
                ['lang_video_url', '输出视频','image_video'],
                ['status', '状态','status','',[0=>'等待',1=>'处理',2=>'完成',3=>'失败']],
                ['status', '状态','text.edit'],
                ['lang_type', '模版','text.edit'],
                ['voice_name', 'voice_name'],
                ['from_lang', '原语言'],
                ['to_lang', '目标语言'],
                ['time', '创建时间'],
                
            ])
             ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', 'user_id'],
                ['text', 'status', '状态'],
                ['text', 'lang_type', '模版'],
              
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}
