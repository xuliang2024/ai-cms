<?php
// 授权登录临时表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class AuthLoginTemp extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_auth_login_temp')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_auth_login_temp', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/AuthLoginTempModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['task_id', '任务ID'],
                ['user_id', 'user_id'],
                ['platform', '类型','status','',[1=>'抖音',2=>'快手',3=>'小红书',4=>'视频号',5=>'B站']],
                // ['platform', '平台'],
                ['msg', 'msg'],
                ['qr_code_info_url', '二维码'],
                ['qr_code_info_url', '二维码','img_url'],
                // ['status', '状态','status','',[0=>'等待',1=>'处理',2=>'完成',3=>'失败']],
                ['status', '状态','text.edit'],
               
                ['time', '创建时间'],
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'task_id', '任务ID'],
                ['text', 'user_id', 'user_id'],
                ['text', 'status', '状态'],
                ['select', 'platform', '类型', '', '', ['1'=>'抖音','2'=>'快手','3'=>'小红书','4'=>'视频号','5'=>'B站']],
              
            ])
            
            ->setRowList($data_list) // 设置表格数据
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}
