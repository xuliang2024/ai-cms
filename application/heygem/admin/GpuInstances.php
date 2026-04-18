<?php
// GPU实例管理
namespace app\heygem\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\heygem\model\GpuInstances as GpuInstancesModel;

class GpuInstances extends Admin {
    
    /**
     * GPU实例列表
     */
    public function index() 
    {
        $map = $this->getMap();
        // 查询数据
        $data_list = GpuInstancesModel::where($map)
            ->order('create_timestamp desc')
            ->paginate();

        cookie('gpu_instances', $map);
        
        return ZBuilder::make('table')
            ->setTableName('heygem/GpuInstances') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['name', '实例名称'],
                ['data_center_name', '数据中心'],
                ['gpu_model', 'GPU型号'],
                ['gpu_used', 'GPU数量'],
                ['cpu_model', 'CPU型号'],
                ['cpu_core_count', 'CPU核心数'],
                ['memory_size', '内存大小(GB)', 'callback', function($value) {
                    return round($value / (1024 * 1024 * 1024), 2);
                }],
                ['system_disk_size', '系统盘(GB)', 'callback', function($value) {
                    return round($value / (1024 * 1024 * 1024), 2);
                }],
                ['price_per_hour', '每小时价格'],
                ['status', '实例状态'],
                ['create_timestamp', '创建时间', 'datetime', '', 'Y-m-d H:i:s'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'id', '实例ID'],
                ['text', 'name', '实例名称'],
                ['text', 'data_center_name', '数据中心'],
                ['text', 'gpu_model', 'GPU型号'],
                ['select', 'status', '状态', '', [
                    'running' => '运行中',
                    'shutdown' => '已关闭',
                    'creating' => '创建中',
                    'error' => '错误'
                ]]
            ])

            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }


    
} 