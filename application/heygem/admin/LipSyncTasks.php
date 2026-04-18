<?php
// 对口型任务管理
namespace app\heygem\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\heygem\model\LipSyncTasks as LipSyncTasksModel;

class LipSyncTasks extends Admin {
    
    /**
     * 对口型任务列表
     */
    public function index() 
    {
        $map = $this->getMap();
        // 查询数据
        $data_list = LipSyncTasksModel::where($map)
            ->order('time desc')
            ->paginate();

        cookie('lip_sync_tasks', $map);
        
        return ZBuilder::make('table')
            ->setTableName('heygem/LipSyncTasks') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['video_url', '原始视频', 'image_video'],
                ['audio_url', '音频', 'image_video'],
                ['output_video_url', '合成视频', 'image_video'],
                ['from_digital_id', '数字人ID'],
                ['status', '状态','text.edit'],
                ['error_msg', '错误信息'],
                ['time', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'user_id', '用户ID'],
                ['text', 'from_digital_id', '数字人ID'],
                ['select', 'status', '状态', '', [
                    0 => '等待中',
                    1 => '处理中',
                    2 => '完成',
                    7 => '失败'
                ]]
            ])
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
    
    
} 