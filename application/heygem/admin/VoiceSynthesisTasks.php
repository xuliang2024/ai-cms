<?php
// 语音合成任务管理
namespace app\heygem\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\heygem\model\VoiceSynthesisTasks as VoiceSynthesisTasksModel;

class VoiceSynthesisTasks extends Admin {
    
    /**
     * 语音合成任务列表
     */
    public function index() 
    {
        $map = $this->getMap();
        // 查询数据
        $data_list = VoiceSynthesisTasksModel::where($map)
            ->order('time desc')
            ->paginate();

        cookie('voice_synthesis_tasks', $map);
        
        return ZBuilder::make('table')
            ->setTableName('heygem/VoiceSynthesisTasks') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['tts_text', '合成文本', 'callback', function($value) {
                    return mb_substr($value, 0, 30) . '...';
                }],
                ['from_voice_id', '声音库ID'],
                ['digital_id', '数字人ID'],
                ['mode', '合成模式'],
                ['need_lip_sync', '需要口型同步'],
                ['audio_url', '音频链接', 'image_video'],
                ['status', '状态','text.edit'],
                ['error_msg', '错误信息'],
                ['time', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'user_id', '用户ID'],
                ['text', 'from_voice_id', '声音库ID'],
                ['text', 'digital_id', '数字人ID'],
                ['text', 'mode', '合成模式'],
                ['select', 'need_lip_sync', '需要口型同步', '', [
                    1 => '是',
                    0 => '否'
                ]],
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