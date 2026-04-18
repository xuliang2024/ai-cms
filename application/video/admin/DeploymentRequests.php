<?php
// 部署表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class DeploymentRequests extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_deployment_requests')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_deployment_requests', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/DeploymentRequestsModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['name', '部署名称'],
                ['deployment_type', '部署类型'],
                ['replica_num', '容器副本数量'],
                ['parallelism_num', 'job运行数量'],
                ['reuse_container', '复用已停止容器'],

                ['region_sign', '容器调度标识'],
                ['cuda_v', 'cuda版本'],
                ['gpu_name_set', 'gpu型号'],
                ['gpu_num', '所需gpu数量'],
                ['memory_size_from', '内存起始值'],
                ['memory_size_to', '内存结束值'],

                ['cpu_num_from', 'CPU起始值'],
                ['cpu_num_to', 'CPU结束值'],
                ['price_from', '价格起始值'],
                ['price_to', '价格结束值'],
                ['status', '状态'],
                ['image_uuid', '镜像UUID'],


                // ['status', '状态','status','',[0=>'下架',2=>'上架']],

                // ['source_text', 'source_text', 'callback', function($source_text) {
                //     // 限制字符串长度为50个字符
                //     return mb_strimwidth($source_text, 0, 50, '...');
                // }],
                ['cmd', '启动容器命令'],
                ['time', '创建时间'],
                ['created_at', '镜像创建时间'],
                ['updated_at', '镜像更新时间'],
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', '用户ID'],
                ['text', 'image_uuid', 'image_uuid'],
                ['text', 'name', '部署名称'],
                ['text', 'status', '状态'],
              
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}
