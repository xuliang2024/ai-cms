<?php
// 容器表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class CombinedResponses extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_combined_responses')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_combined_responses', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/CombinedResponsesModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['uuid', '容器uuid'],
                ['deployment_uuid', '部署uuid'],
                ['machine_id', '主机uuid'],

                ['gpu_name', 'gpu型号'],
                ['gpu_num', 'gpu数量'],

                ['cpu_num', 'cpu数量'],
                ['memory_size', '内存大小'],
                ['image_uuid', '镜像uuid'],

                ['price', '基准价格'],
                ['ssh_command', 'ssh登录指令'],
                ['root_password', 'ssh密码'],

                ['service_url', '服务地址'],
                ['proxy_host', '服务host(废弃)'],
                ['custom_port', '服务端口号'],

                ['status', '状态'],


                // ['status', '状态','status','',[0=>'下架',2=>'上架']],

                // ['source_text', 'source_text', 'callback', function($source_text) {
                //     // 限制字符串长度为50个字符
                //     return mb_strimwidth($source_text, 0, 50, '...');
                // }],
                ['started_at', '开始运行时间'],
                ['stopped_at', '停止时间'],
                
                ['created_at', '镜像创建时间'],
                ['updated_at', '镜像更新时间'],
                ['time', '创建时间'],
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', '用户ID'],
                ['text', 'image_uuid', 'image_uuid'],
                ['text', 'uuid', '容器uuid'],
                ['text', 'status', '状态'],
              
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}
