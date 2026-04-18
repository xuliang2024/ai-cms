<?php
// 声音库管理
namespace app\heygem\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\heygem\model\VoiceLibrary as VoiceLibraryModel;

class VoiceLibrary extends Admin {
    
    /**
     * 声音库列表
     */
    public function index() 
    {
        $map = $this->getMap();
        // 查询数据
        $data_list = VoiceLibraryModel::where($map)
            ->order('sort desc, time desc')
            ->paginate();

        cookie('voice_library', $map);
        
        return ZBuilder::make('table')
            ->setTableName('heygem/VoiceLibrary') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['voice_name', '声音名称'],
                ['audio_url', '音频链接', 'image_video'],
                ['audio_content', '内容' , 'callback', function($value) {
                    return mb_substr($value, 0, 30) . '...';
                }],
                ['language', '语言'],
                ['sex', '性别', 'callback', function($value) {
                    if ($value == 'male') return '男';
                    if ($value == 'female') return '女';
                    return $value;
                }],
                ['is_public', '是否公开', 'switch'],
                ['sort', '排序', 'text.edit'],
                ['time', '创建时间'],
                ['updated_at', '更新时间'],
                ['speaker_label', '说话人标签'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'voice_name', '声音名称'],
                ['text', 'user_id', '用户ID'],
                ['text', 'language', '语言'],
                ['select', 'sex', '性别', '', [
                    'male' => '男',
                    'female' => '女'
                ]],
                ['select', 'is_public', '是否公开', '', [
                    1 => '是',
                    0 => '否'
                ]]
            ])
            ->addRightButtons(['edit', 'delete']) // 添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
} 