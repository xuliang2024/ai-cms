<?php
// lora模型列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class AudioTask extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_audio_task')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_audio_task', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/AudioModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', 'user_id'],
                ['tts_type', '类型','tts_type','',[0=>'微软',1=>'OpenAI']],
                ['mp3_sec', '字符'],
                ['name', 'name'],
                ['role', 'role'],
                ['style', 'style'],
                ['mp3_url', '输出音频','image_video'],
                ['status', '状态','status','',[0=>'等待',1=>'处理',2=>'完成',3=>'失败']],
                ['status', '状态','text.edit'],
                ['source_text', 'source_text', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 50, '...');
                }],
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
